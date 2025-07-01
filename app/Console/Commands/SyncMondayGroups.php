<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Group;
use App\Models\Project;
use App\Services\MondayService;
use Illuminate\Console\Command;

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
        /** @var \App\Services\MondayService $mondayService */
        $mondayService = app(MondayService::class);

        foreach (Project::all() as $project) {
            if(empty($project->time_board_id)){
                continue;
            }
            $groups = $mondayService->getGroups($project->time_board_id);

            foreach ($groups as $group) {
                if ($group['id'] === "topics") {
                    continue;
                }
                Group::updateOrCreate(
                    ['id' => $group['id']],
                    [
                        'name' => $group['title'],
                        'project_id' => $project->id
                    ]
                );
            }
        }
    }
}
