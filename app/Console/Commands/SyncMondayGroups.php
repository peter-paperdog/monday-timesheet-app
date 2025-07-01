<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Group;
use App\Models\Project;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMondayGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com groups with the database.';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalSynced = 0;

        /** @var \App\Services\MondayService $mondayService */
        $mondayService = app(MondayService::class);

        foreach (Project::all() as $project) {
            if (empty($project->time_board_id)) {
                Log::info("Project ID {$project->id} skipped: no time_board_id.");
                continue;
            }

            $this->info("Fetching groups for project {$project->name} (ID:{$project->id})");

            $groups = $mondayService->getGroups($project->time_board_id);

            if (empty($groups)) {
                $this->warn("No groups found for board {$project->name} (ID:{$project->id}) time board (ID: {$project->time_board_id}");
                continue;
            }

            foreach ($groups as $group) {
                Group::updateOrCreate(
                    ['id' => $project->id . "_" . $group['id']],
                    [
                        'name' => $group['title'],
                        'project_id' => $project->id
                    ]
                );
                $totalSynced++;
            }
        }
        $this->info("Group sync completed. Total synced: {$totalSynced}");
        Log::info("Group sync completed. Total synced: {$totalSynced}");
    }
}
