<?php

namespace App\Console\Commands;

use App\Http\Controllers\TimesheetController;
use App\Mail\allUserSummaryEmail;
use App\Mail\userSummaryEmail;
use App\Services\MondayService;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class weeklyUserPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:usersummary';

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

        $mondayService = new MondayService();
        $usersService = new UserService($mondayService);
        $users = $usersService->getUsers();
        $allTimeTrackingItems = $mondayService->getTimeTrackingItems();
        $exceptions = [
            'petra@paperdog.com', 'szonja@paperdog.com', 'oliver@paperdog.com', 'amo@paperdog.com',
            'morwenna@paperdog.com', 'gabriella@paperdog.com'
        ];

        foreach ($users as $user) {
            //if (!in_array($user['email'], $exceptions)) {
            if ($user['email'] == 'bence@paperdog.com') {
                $User = $usersService->getUserBy('email', $user['email']);
                $TimesheetController = new TimesheetController();
                $pdf = $TimesheetController->downloadUserTimeSheet($User, $allTimeTrackingItems);
                info("Weekly summary sent to ".$user['email'].' '.now());
                Mail::to($user['email'])->send(new userSummaryEmail($pdf));
            }
        }
    }
}