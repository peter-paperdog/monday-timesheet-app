<?php

namespace App\Console\Commands;

use App\Services\SlackService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Models\MondayTimeTracking;
use App\Mail\WeeklyTimesheetEmail;

class SendDailyStatusToHungariansOnSlack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'slack:send-daily-status-to-hungarians';

    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Send daily office status to users on slack.';

    /**
     * Execute the console command.
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $slackService = new SlackService();
        $exception = [
            "oliver@paperdog.com",
            "morwenna@paperdog.com",
            "mark@paperdog.com",
            "amo@paperdog.com",
            'peter@paperdog.com',
            'gabriella@paperdog.com',
        ];

        $users = User::where('location', 'Hungary')
            ->whereNotIn('email', $exception)
            ->get();

        foreach ($users as $user) {
            $todaySchedule = $user->schedules()
                ->whereDate('date', now()->toDateString())
                ->first();

            if ($todaySchedule) {
                $question = "A mai státuszod ".$todaySchedule->status."? Ha nem módosítsd a gombok megnyomásával!";
                $slackService->sendInteractiveMessage($user->slack_id, $question);
            }
        }

        $this->info('Daily office status sent successfully!');
    }
}
