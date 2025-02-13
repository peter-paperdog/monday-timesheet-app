<?php

namespace App\Console\Commands;

use App\Models\MondayBoard;
use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\User;
use App\Services\MondayService;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

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
        $this->info('Fetching users from Monday.com...');
        $users = $this->mondayService->getUsers();

        foreach ($users as $userData) {

            $user = User::updateOrCreate(
                ['id' => $userData['id']],
                [
                    'id' => $userData['id'],
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make(str()->random(12)), // Set a random password for new users
                ]
            );

            $this->info("User {$user->name} synced with Monday ID: {$user->id}");
        }

        $this->info('User synchronization complete.');
        
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


            $this->info("Processing board '{$board->name}' ({$board->id})");

            $items = $this->mondayService->getItems($board->id);

            if (empty($items)) {
                $this->warn("No item data found for board '{$board->name}' (#{$board->id})");
                continue;
            }

            $this->info("Found " . count($items) . " items for board '{$board->name}' (#{$board->id})");

            foreach ($items as $itemData) {
                MondayItem::updateOrCreate(
                    ['id' => $itemData['id']],
                    [
                        'name' => $itemData['name'],
                        'board_id' => $board->id
                    ]
                );
            }
            $this->info("Updated " . count($items) . " items for board '{$board->name}' (#{$board->id})");

            // Fetch time tracking data for this board
            $items = $this->mondayService->getTimeTrackingItems($board->id);

            if (empty($items)) {
                $this->warn("No time tracking data found for board '{$board->name}' ({$board->id})");
                continue;
            }

            $this->info("Found " . count($items) . " items for board '{$board->name}' ({$board->id})");

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
            }
            $this->info("Updated " . count($items) . " items for board '{$board->name}' ({$board->id})");
            $this->info("Board successfully synced with Monday.");
        }

        $this->info('Monday synchronization complete.');
    }
}
