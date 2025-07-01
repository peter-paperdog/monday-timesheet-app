<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\UserBoard;
use App\Services\MondayService;
use Illuminate\Console\Command;

class SyncMondayUserTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-user-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com user tasks with the database';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching user tasks from Monday.com...');

        foreach (UserBoard::all() as $user_board) {
            $this->info("User board: {$user_board->name} ({$user_board->id}))");

            $items = $this->mondayService->getItems($user_board->id);
            $this->info("Processing ".count($items) . " items");

            foreach ($items as $item) {
                $task = Task::firstOrNew(['id' => $item['id']]);
                $task->forceFill([
                    'name' => $item['name'] ?? '',
                    'group_id' => $item['group']['id'] ?? null,
                    'taskable_id' => $user_board->id,
                    'taskable_type' => \App\Models\UserBoard::class,
                ]);
                $task->save();
            }
        }

        $this->info('User tasks synchronization complete.');
    }
}
