<?php

namespace App\Console\Commands;

use App\Models\UserSchedule;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;

class SyncOfficeSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:office-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all office schedules from Google Sheets';

    private GoogleSheetsService $sheetsService;

    public function __construct(GoogleSheetsService $sheetsService)
    {
        parent::__construct();
        $this->sheetsService = $sheetsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching office schedules...');
        try {
            $data = $this->sheetsService->getOfficeSchedules();

            if (empty($data)) {
                $this->warn('No data received.');
                return;
            }

            $data = array_map(function ($item) {
                // Return only the columns that exist in user_schedules
                return [
                    'user_id' => $item['user_id'],
                    'date'    => $item['date'],
                    'status'  => $item['status'],
                ];
            }, $data);

            // Insert into database
            UserSchedule::upsert($data, ['user_id', 'date'], ['status']);

            $this->info('Schedules synchronized successfully.');
        } catch (\Exception $e) {
            $this->error('Error syncing office schedules: ' . $e->getMessage());
        }
    }
}
