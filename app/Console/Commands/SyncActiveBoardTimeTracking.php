<?php

namespace App\Console\Commands;

use App\Models\MondayBoard;
use App\Models\MondayTimeTracking;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncActiveBoardTimeTracking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:active-board-time-tracking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and sync time tracking data for active boards in the last 7 days';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching active boards from the last 7 days...');

        // Fetch boards with activity in the last 7 days
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $activeBoards = MondayBoard::where('activity_at', '>=', $sevenDaysAgo)->get();

        if ($activeBoards->isEmpty()) {
            $this->info('No active boards found.');
            return;
        }

        $this->info("Found {$activeBoards->count()} active boards. Fetching time tracking data...");

        $updatedCount = 0;

        foreach ($activeBoards as $activeBoard) {
            $this->info("Processing board ID: {$activeBoard->id}");

            // Fetch time tracking data for this board
            $items = $this->mondayService->getTimeTrackingItems($activeBoard->id);

            if (empty($items)) {
                $this->warn("No time tracking data found for Board ID: {$activeBoard->id}");
                continue;
            }

            $this->info("Found " . count($items) . " items for Board ID: {$activeBoard->id}");


            foreach ($items as $trackingData) {
                MondayTimeTracking::updateOrCreate(
                    ['id' => $trackingData['id']],
                    [
                        'item_id' => $trackingData['item_id'],
                        'user_id' => $trackingData['started_user_id'],
                        'started_at' => Carbon::parse($trackingData['started_at'])->toDateTimeString(),
                        'ended_at' => !empty($trackingData['ended_at']) ? Carbon::parse($trackingData['ended_at'])->toDateTimeString() : null,
                    ]
                );
                $updatedCount++;
            }
            $this->info("Updated " . count($items) . " items for Board ID: {$activeBoard->id}");
        }

        $this->info("Sync complete. Updated {$updatedCount} time tracking records.");
    }
}
