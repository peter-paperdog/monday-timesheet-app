<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
/*
//all user notify
Schedule::command('weekly:notification')->weeklyOn(5, '15:30')->timezone('Europe/London');

//Wenna mail
Schedule::command('weekly:allsummary')->weeklyOn(1, '17:57')->timezone('Europe/London');

//all user summary mails
Schedule::command('weekly:usersummary')->weeklyOn(1, '09:00')->timezone('Europe/London');
Schedule::command('weekly:usersummary')->weeklyOn(1, '15:15')->timezone('Europe/London');
*/

// Sync Monday users
Schedule::command('sync:monday-users')->dailyAt('00:00')->timezone('Europe/London');

// Sync Monday boards
Schedule::command('sync:monday-boards')->dailyAt('00:01')->timezone('Europe/London');

Schedule::command('sync:active-board-time-tracking')->dailyAt('00:05')->timezone('Europe/London');
