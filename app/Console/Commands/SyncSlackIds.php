<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SlackService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SyncSlackIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:users-slack-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize users slack ID-s with the database';

    public function __construct(private readonly SlackService $slackService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @throws ConnectionException
     */
    public function handle()
    {
        $this->info('Fetching users slack ID from Slack API...');

        try {
            $slackUsers = $this->slackService->getUserList();
        } catch (ConnectionException $e) {
            $this->error("Failed to connect to Slack API: " . $e->getMessage());
            return 1;
        }

        if ($slackUsers['success']) {
            foreach ($slackUsers['response']['members'] as $userData) {
                if (!empty($userData['profile']['email'])) {
                    $email = trim(strtolower($userData['profile']['email']));

                    try {
                        $user = User::where('email', $email)->firstOrFail();

                        if ($user->slack_id !== $userData['id']) {
                            $user->update(['slack_id' => $userData['id']]);
                            $this->info("Slack ID updated for user: {$email}");
                        }

                    } catch (ModelNotFoundException $e) {
                        $this->warn("User not found for email: {$email}");
                    } catch (\Exception $e) {
                        $this->error("Update failed for email: {$email}. Error: " . $e->getMessage());
                        Log::error("Slack ID update error for {$email}: " . $e->getMessage());
                    }
                }
            }
        } else {
            $this->error("Cannot synchronize users' Slack IDs at the moment.");
        }

        $this->info('User Slack ID synchronization complete.');
    }
}
