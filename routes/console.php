<?php

use Illuminate\Support\Facades\Schedule;

app()->singleton(Schedule::class, function (Schedule $schedule) {

// Run every hour between 6 AM and 12 PM (excluding weekends)
    $schedule->command('sync:monday-users')
        ->hourly()
        ->between('6:00', '12:00')
        ->weekdays()
        ->timezone('Europe/London');

    $schedule->command('sync:monday-boards')
        ->hourly()
        ->between('6:00', '12:00')
        ->weekdays()
        ->timezone('Europe/London');

    $schedule->command('sync:active-board-time-tracking')
        ->hourly()
        ->between('6:00', '12:00')
        ->weekdays()
        ->timezone('Europe/London');

    $schedule->command('sync:items')
        ->hourly()
        ->between('6:00', '12:00')
        ->weekdays()
        ->timezone('Europe/London');
});
