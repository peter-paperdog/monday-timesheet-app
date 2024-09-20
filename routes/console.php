<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('weekly:notification')->weeklyOn(5, '15:30')->timezone('Europe/London');
//Schedule::command('weekly:notification')->everyMinute();
