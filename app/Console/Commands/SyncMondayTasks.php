<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Group;
use App\Models\Project;
use App\Models\Task;
use App\Services\MondayService;
use Illuminate\Console\Command;

class SyncMondayTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com tasks with the database.';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing Tasks...');

        foreach (Project::all() as $project) {
            if (is_null($project->time_board_id)) {
                continue;
            }

            $this->info("Syncing project: " . $project->name);
            $tasks = $this->mondayService->getTasks($project->time_board_id);
            $i = 0;

            foreach ($tasks as $taskData) {
                $i++;

                Task::updateOrCreate(
                    ['id' => $taskData['id']],
                    [
                        'name' => $taskData['name'] ?? '',
                        'group_id' => $taskData['group']['id'] ?? null,
                        'taskable_id' => $project->id,
                        'taskable_type' => Project::class,
                    ]
                );
            }

            $this->info("Processed $i tasks for project '{$project->name}'.");
        }

        $this->info('All project tasks synced.');
    }
}
