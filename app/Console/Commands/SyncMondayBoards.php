<?php

namespace App\Console\Commands;

use App\Models\MondayBoard;
use App\Services\MondayService;
use DateTime;
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

        $boards = $this->mondayService->getBoards(); // Assume this method exists

        foreach ($boards as $boardData) {
            $board = MondayBoard::updateOrCreate(
                ['id' => $boardData['id']],
                [
                    'id' => $boardData['id'],
                    'name' => str_replace('Subitems of ', '', $boardData['name']),
                    'type' => $boardData['type']
                ]
            );

            $this->info("Board '{$board->name}' synced with Monday ID: {$board->id}");
        }

        //check activity
        $fromDate = Carbon::now()->subDays(1)->toIso8601String(); // Last 1 day
        $toDate = Carbon::now()->toIso8601String(); // Current time

        $items = $this->mondayService->getLastActivities($fromDate, $toDate);

        $filteredItems = array_filter($items, function ($item) {
            return !empty($item['activity_logs']);
        });

        $results = array_map(function ($item) {
            $highestCreatedAt = max(array_column($item['activity_logs'], 'created_at'));

            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'activity_at' => gmdate("Y-m-d\TH:i:s\Z", $highestCreatedAt / 10000000)
            ];
        }, $filteredItems);

        $results = array_values($results);

        foreach ($results as $boardData) {
            $board = MondayBoard::where('id', $boardData['id'])->first();

            // Ensure the board exists before updating
            if ($board && !empty($boardData['activity_logs'])) {
                $board->update(['activity_at' => Carbon::createFromTimestamp($boardData['activity_at'])]);
                $this->info("Board '{$board->name}' updated with activity_at: {$board->activity_at}");
            }

            $this->info("Board '{$board->name}' activity: {$board->activity_at}");
        }


        $this->info('Board synchronization complete.');
    }
}
