<?php

namespace App\Console\Commands;

use App\Models\MondayBoard;
use App\Models\MondayItem;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com items with the database';


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

        $this->info("Found {$activeBoards->count()} active boards. Fetching item data...");

        $updatedCount = 0;

        foreach ($activeBoards as $activeBoard) {
            $this->info("Processing board '{$activeBoard->name}' (#{$activeBoard->id})");

            // Fetch time tracking data for this board
            $items = $this->mondayService->getItems($activeBoard->id);

            if (empty($items)) {
                $this->warn("No item data found for board '{$activeBoard->name}' (#{$activeBoard->id})");
                continue;
            }

            $this->info("Found " . count($items) . " items for board '{$activeBoard->name}' (#{$activeBoard->id})");

            foreach ($items as $itemData) {

                MondayItem::updateOrCreate(
                    ['id' => $itemData['id']],
                    [
                        'name' => $itemData['name'],
                        'board_id' => $activeBoard->id
                    ]
                );
                $updatedCount++;
            }
            $this->info("Updated " . count($items) . " items for board '{$activeBoard->name}' (#{$activeBoard->id})");
        }

        $this->info("Sync complete. Updated {$updatedCount} items.");
    }
}
