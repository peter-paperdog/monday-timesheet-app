<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\RefreshToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'refresh-token' => RefreshToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Schedule tasks to run every hour between 6 AM and 12 PM (weekdays only)
        $schedule->command('sync:monday-users')->daily()->at("00:20");
        $schedule->command('sync:monday-tasks')->daily()->at("00:05");
        $schedule->command('sync:monday-boards')->everyFifteenMinutes()->between('08:00', '23:00');
        $schedule->command('sync:monday-folders')->hourly()->between('08:05', '23:05');
        $schedule->command('sync:monday-groups')->hourly()->between('08:10', '23:10');
        $schedule->command('sync:monday-assignments')->everyTenMinutes()->between('08:00', '23:00');

        $schedule->command('sync:office-schedules')->hourly()->between('08:00', '22:00');

        $schedule->command('sync:monday-contact-board')->hourly()->between('08:00', '22:00');
        $schedule->command('sync:monday-board-webhooks')->everyMinute();


        /*
         * Fridays UK 3pm notification
         * Mondays UK 9am user pdf
         * Mondays UK 3pm user pdf
         * Mondays UK 5pm admin pdfs
         */

        // Send weekly timesheet PDFs to all users.
        $schedule->command('email:send-weekly-timesheets')->weeklyOn(1, '09:15');
        $schedule->command('email:send-weekly-timesheets')->weeklyOn(1, '15:15');
        $schedule->command('email:send-time-record-notification')->weeklyOn(5, '15:00');
        $schedule->command('email:send-weekly-timesheets-admin')->weeklyOn(1, '17:00');

        /*
         * All weekday UK 9am notification to UK users
         * All weekday UK 8am notification to HUN users
         */

        // Send daily status on Slack to all user
        $schedule->command('slack:send-daily-status-to-hungarians')->weekdays()->at('08:00');

        // Send daily statuses on Slack to Gabi
        $schedule->command('slack:send-daily-statuses-to-gabi')->weekdays()->at('10:00');
    })
    ->create();
