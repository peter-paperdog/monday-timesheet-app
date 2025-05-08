<?php

namespace App\Console\Commands;

use App\Models\SyncStatus;
use App\Models\User;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class SyncMondayUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com users with the database';

    /**
     * List of admin emails.
     *
     * @var array
     */
    private array $adminEmails = [
        'amo@paperdog.com',
        'morwenna@paperdog.com',
        'peter@paperdog.com',
        'oliver@paperdog.com',
        'mark@paperdog.com',
        'gabriella@paperdog.com',
        'bence@paperdog.com',
        'blanka@paperdog.com',
    ];

    /**
     * List of exception emails.
     *
     * @var array
     */
    private array $exceptionEmails = [
        'hannah@paperdog.com',
    ];

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
            if (!in_array($userData['email'], $this->exceptionEmails)) {
                // Check if the user's email is in the admin list
                $isAdmin = in_array($userData['email'], $this->adminEmails);


                $user = User::updateOrCreate(
                    ['id' => $userData['id']],
                    [
                        'id' => $userData['id'],
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'location' => $userData['location'],
                        'admin' => $isAdmin,
                        'password' => Hash::make(str()->random(12)), // Set a random password for new users
                    ]
                );

                $this->info("User {$user->name} synced with Monday ID: {$user->id}");
            }
        }

        $this->info('User synchronization complete.');
        SyncStatus::recordSync('monday-users'); // Record sync time

        Artisan::call('sync:users-slack-ids');
    }
}
