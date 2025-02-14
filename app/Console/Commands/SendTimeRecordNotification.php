<?php

namespace App\Console\Commands;

use App\Mail\TimeRecordNotificationMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTimeRecordNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send-time-record-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the weekly reminder email to all users to log their time on Monday';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending weekly timesheet reminders...');

        $users = User::whereNotNull('email')->get(); // Get all users with an email

        foreach ($users as $user) {
            Mail::to($user->email)->send(new TimeRecordNotificationMail($user));
            $this->info("Reminder sent to {$user->email}");
        }

        $this->info('Weekly reminder emails sent successfully.');
    }
}
