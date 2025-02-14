<?php

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
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Schedule tasks to run every hour between 6 AM and 12 PM (weekdays only)
        $schedule->command('sync:monday-users')->daily()->weekdays();
        $schedule->command('sync:monday-boards')->hourly()->between('08:00', '22:00')->weekdays();
        $schedule->command('sync:monday-assignments')->everyTenMinutes()->between('08:00', '22:00')->weekdays();


        /*
         * Fridays UK 3pm notification
         * Mondays UK 9am user pdf
         * Mondays UK 3pm user pdf
         * Mondays UK 5pm admin pdfs
         */

        // Send weekly timesheet PDFs to all users.
        $schedule->command('email:send-weekly-timesheets')->weeklyOn(1, '09:15');
        $schedule->command('email:send-weekly-timesheets')->weeklyOn(1, '15:15');
    })
    ->create();
