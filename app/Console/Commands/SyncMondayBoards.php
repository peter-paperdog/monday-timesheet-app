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
        $startTime = microtime(true);
        $this->info('Fetching clients into board from Monday.com...');
        $folders = $this->mondayService->getFolders();

        $this->info('Updating '.count($folders->clients).' clients.'.PHP_EOL);

        foreach ($folders->clients as $client) {
            $board = MondayBoard::updateOrCreate(
                ['id' => $client->id],
                [
                    'id' => $client->id,
                    'name' => str_replace('Subitems of ', '', $client->name),
                    'type' => 'folder'
                ]
            );
            $board->touch();

            $this->info("Processing client '{$board->name}' ({$board->id})");
        }

        $this->info('Adding projects for clients.');

        $g = 0;
        foreach ($folders->projects as $project) {
            if ($project->name !== "Subitems") {
                $MondayGroup = MondayGroup::updateOrCreate(
                    ['id' => $project->id],
                    [
                        'id' => $project->id,
                        'name' => $project->name,
                        'board_id' => $project->client_id
                    ]
                );
                $g++;
                $MondayGroup->touch();
            }
        }
        if ($g === 0) {
            $this->warn("No project found.");
        } else {
            $this->info("Added ".$g." proejcts.");
        }

        $boards = $this->mondayService->getBoardsFromNewStructure();
        $this->info('Get '.count($boards).' boards for get items.'.PHP_EOL);

        foreach ($boards as $board) {
            if (is_null($board['board_folder_id'])){
                continue;
            }
            $parent_id = $this->mondayService->getFolderParentId($board['board_folder_id']);
            $board_id = null;
            $group_id = null;

            if (is_null($parent_id)){
                $board_id = $board['board_folder_id'];
            }else{
                $board_id = $parent_id;
                $group_id = $board['board_folder_id'];
            }

            $items = $this->mondayService->getItems($board['id']);

            if (empty($items)) {
                $this->warn("No item data found for board '{$board['name']}' (#{$board['id']})");
            } else {
                $this->info("Found ".count($items)." task items for board '{$board['name']}' (#{$board['id']})");
            }


            foreach ($items as $itemData) {
                $MondayItem = MondayItem::updateOrCreate(
                    ['id' => $itemData['id']],
                    [
                        'name' => $itemData['name'],
                        'board_id' => $board_id,
                        'group_id' => $group_id,
                        'parent_id' => null
                    ]
                );
                $MondayItem->touch();
            }

            // Fetch time tracking data for this board
            $items = $this->mondayService->getTimeTrackingItems($board['id']);

            if (empty($items)) {
                $this->warn("No time tracking data found for board '{$board['name']}' ({$board['id']})");
            } else {
                $this->info("Found ".count($items)." time tracking items for board '{$board['name']}' ({$board['id']})");
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
            $this->info("Successfully updated board '{$board['name']}' ({$board['id']})".PHP_EOL.PHP_EOL);
        }
        $totalTime = round(microtime(true) - $startTime, 2);

        $this->info('Monday synchronization complete in '.$totalTime.' seconds.');
        SyncStatus::recordSync('monday-boards'); // Record sync time
    }
}
