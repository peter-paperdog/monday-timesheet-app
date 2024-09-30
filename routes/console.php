<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*Schedule::command('weekly:notification')->weeklyOn(5, '15:30')->timezone('Europe/London');
Schedule::command('weekly:allsummary')->weeklyOn(5, '18:00')->timezone('Europe/London');
Schedule::command('weekly:usersummary')->weeklyOn(5, '18:00')->timezone('Europe/London');*/
Schedule::command('weekly:notification')->everyMinute();
Schedule::command('weekly:allsummary')->everyMinute();
Schedule::command('weekly:usersummary')->everyMinute();
