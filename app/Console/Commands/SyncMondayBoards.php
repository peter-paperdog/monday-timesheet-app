<?php

namespace App\Console\Commands;

use App\Models\MondayBoard;
use App\Models\MondayGroup;
use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\SyncStatus;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncMondayBoards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-boards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com boards with the database';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching boards from Monday.com...');
        $boards = $this->mondayService->getBoards();
        $this->info('Updating ' . count($boards) . ' items.' . PHP_EOL);

        foreach ($boards as $boardData) {
            $board = MondayBoard::updateOrCreate(
                ['id' => $boardData['id']],
                [
                    'id' => $boardData['id'],
                    'name' => str_replace('Subitems of ', '', $boardData['name']),
                    'type' => $boardData['type']
                ]
            );
            $board->touch();

            $this->info("Processing board '{$board->name}' ({$board->id})");

            $groups = $this->mondayService->getGroups($board->id);
            $this->info('Adding groups for board.');

            $g = 0;
            foreach ($groups as $groupData) {
                if ($groupData['title'] !== "Subitems") {
                    $MondayGroup = MondayGroup::updateOrCreate(
                        ['id' => $board->id . '_' . $groupData['id']],
                        [
                            'id' => $board->id . '_' . $groupData['id'],
                            'name' => $groupData['title'],
                            'board_id' => $board->id
                        ]
                    );
                    $g++;
                    $MondayGroup->touch();
                }
            }
            if ($g === 0) {
                $this->warn("No group found.");
            } else {
                $this->info("Added " . $g . " groups.");
            }

            $items = $this->mondayService->getItems($board->id);

            if (empty($items)) {
                $this->warn("No item data found for board '{$board->name}' (#{$board->id})");
            } else {
                $this->info("Found " . count($items) . " task items for board '{$board->name}' (#{$board->id})");
            }

            foreach ($items as $itemData) {
                $MondayItem = MondayItem::updateOrCreate(
                    ['id' => $itemData['id']],
                    [
                        'name' => $itemData['name'],
                        'board_id' => $board->id,
                        'group_id' => $itemData['group']['id'] ? ($board->id . '_' . $itemData['group']['id']) : null,
                        'parent_id' => $itemData['parent_item']['id'] ?? null
                    ]
                );
                $MondayItem->touch();
            }

            // Fetch time tracking data for this board
            $items = $this->mondayService->getTimeTrackingItems($board->id);

            if (empty($items)) {
                $this->warn("No time tracking data found for board '{$board->name}' ({$board->id})");
            } else {
                $this->info("Found " . count($items) . " time tracking items for board '{$board->name}' ({$board->id})");
            }

            foreach ($items as $trackingData) {
                $MondayTimeTracking = MondayTimeTracking::updateOrCreate(
                    ['id' => $trackingData['id']],
                    [
                        'item_id' => $trackingData['item_id'],
                        'user_id' => $trackingData['started_user_id'],
                        'started_at' => Carbon::parse($trackingData['started_at'])->toDateTimeString(),
                        'ended_at' => !empty($trackingData['ended_at']) ? Carbon::parse($trackingData['ended_at'])->toDateTimeString() : null,
                    ]
                );
                $MondayTimeTracking->touch();
            }
            $this->info("Successfully updated board '{$board->name}' ({$board->id})" . PHP_EOL . PHP_EOL);
        }

        $this->info('Monday synchronization complete.');
        SyncStatus::recordSync('monday-boards'); // Record sync time
    }
}
