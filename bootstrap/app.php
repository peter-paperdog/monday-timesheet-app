<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
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
        $schedule->command('sync:monday-boards')->hourly()->between('6:05', '12:05')->weekdays();

        // Debugging: Log when the scheduler runs
        \Log::info("Scheduler executed at " . now());
    })
    ->create();
