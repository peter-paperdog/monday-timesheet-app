<?php

namespace App\Console\Commands;

use App\Models\MondayBoard;
use App\Services\MondayService;
use Illuminate\Console\Command;

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

        $this->info('Board synchronization complete.');
    }
}
