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
        $pdfs = array();
        $exceptions = [
            'petra@paperdog.com', 'szonja@paperdog.com', 'oliver@paperdog.com', 'amo@paperdog.com',
            'morwenna@paperdog.com', 'jason@paperdog.com',
        ];

        foreach ($users as $user) {
            if (!in_array($user['email'], $exceptions)) {
                $User = $usersService->getUserBy('email', $user['email']);
                $TimesheetController = new TimesheetController();
                $pdfs[$user['email']] = $TimesheetController->downloadUserTimeSheet($User, $allTimeTrackingItems);
            }
        }

        foreach ($pdfs as $email => $pdf) {
            $pdfAttachment = new userSummaryEmail($pdf);
            Mail::to($email)->send($pdfAttachment);
        }
    }
}
