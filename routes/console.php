<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//all user notify
Schedule::command('weekly:notification')->weeklyOn(5, '15:30')->timezone('Europe/London');

//Wenna mail
Schedule::command('weekly:allsummary')->weeklyOn(1, '18:00')->timezone('Europe/London');

//all user summary mails
Schedule::command('weekly:usersummary')->weeklyOn(1, '09:00')->timezone('Europe/London');
Schedule::command('weekly:usersummary')->weeklyOn(1, '15:00')->timezone('Europe/London');
