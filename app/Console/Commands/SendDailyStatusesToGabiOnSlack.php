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

class SendDailyStatusesToGabiOnSlack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'slack:send-daily-statuses-to-gabi';

    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Send everyones daily office statuses to Gabi on slack.';

    /**
     * Execute the console command.
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $slackService = new SlackService();

        $users = User::all();

        $message = "A mai státuszok:".PHP_EOL;

        foreach ($users as $user) {
            $todaySchedule = $user->schedules()
                ->whereDate('date', now()->toDateString())
                ->first();
            if ($todaySchedule) {
                $message.= $user->name . ': ' . $todaySchedule->status . PHP_EOL;
            }
        }

        $slackService->sendPrivateMessage('U02TACXJY67', $message);

        $this->info('Daily office statuses sent to Gabi successfully!');
    }
}
