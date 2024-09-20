<?php

namespace App\Console\Commands;

use App\Mail\attentionEmail;
use App\Services\MondayService;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class weeklyNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("weeklyNotification running at ".now());
        $mondayService = new MondayService();
        $usersService = new UserService($mondayService);

        $users = $usersService->getUsers();

        $exceptions = ['petra@paperdog.com', 'szonja@paperdog.com'];

        foreach ($users as $user) {
            if (!in_array($user['email'], $exceptions)) {
                Mail::to($user['email'])->send(new attentionEmail($user['name']));
            }
        }


    }
}
