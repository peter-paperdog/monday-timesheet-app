<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\UserBoard;
use App\Services\MondayService;
use Illuminate\Console\Command;

class SyncMondayUserBoardWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-user-board-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync board webhooks for all user boards';

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
        $this->info("Starting user board webhook sync...");

        foreach (UserBoard::all() as $userBoard) {
            $this->info($userBoard->name . "...");
            $this->mondayService->setupWebhooksForUserBoard($userBoard);
        }

        $this->info("Finished user board webhook sync...");
        return Command::SUCCESS;
    }
}
