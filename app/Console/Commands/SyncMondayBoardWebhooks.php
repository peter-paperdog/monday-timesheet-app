<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\MondayService;
use Illuminate\Console\Command;

class SyncMondayProjectWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-project-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync board webhooks for all projects in the workspace';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
        $this->webhookCallbackUrl = env('MONDAY_WEBHOOK_CALLBACK');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting board webhook sync...");

        foreach (Project::all() as $project) {
            $this->info($project->name . "...");
            $this->mondayService->setupWebhooksForProject($project);
        }

        $this->info("Finished board webhook sync...");
        return Command::SUCCESS;
    }
}
