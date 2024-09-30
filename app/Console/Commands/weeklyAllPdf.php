<?php

namespace App\Console\Commands;

use App\Mail\allUserSummaryEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class weeklyAllPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:allsummary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Weekly summary with all users data sent to Morwenna ".now());
        //Mail::to('morwenna@paperdog.com')->send(new allUserSummaryEmail());
        Mail::to('bence@paperdog.com')->send(new allUserSummaryEmail());
    }
}
