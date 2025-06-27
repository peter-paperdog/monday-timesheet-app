<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\MondayBoard;
use App\Models\MondayGroup;
use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\Project;
use App\Models\SyncStatus;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncMondayFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-folders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com folders with the database.';

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

        $folders = $mondayService->getFolders();

        foreach($folders->clients as $client) {
            Client::updateOrCreate(
                ['id' => $client->id],
                ['name' => $client->name]
            );
        }
        foreach($folders->projects as $project) {
            Project::updateOrCreate(
                ['id' => $project->id],
                [
                    'name' => $project->name,
                    'client_id' => $project->client_id
                ]
            );
        }
    }
}
