<?php

namespace App\Console\Commands;

use App\Mail\ScheduleUpdatedMail;
use App\Models\User;
use App\Models\UserSchedule;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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
                    'user_id' => (int) $item['user_id'],
                    'date'    => $item['date'],
                    'status'  => $item['status'],
                ];
            }, $data);

            // 🔹 STEP 1: Fetch unique existing schedules from DB
            $existingSchedules = UserSchedule::whereIn('user_id', array_column($data, 'user_id'))
                ->whereIn('date', array_column($data, 'date'))
                ->get()
                ->unique(fn($item) => $item->user_id . '_' . $item->date) // Ensure uniqueness
                ->keyBy(fn($item) => $item->user_id . '_' . $item->date)
                ->toArray();

            // 🔹 STEP 2: Identify real changes
            $changedSchedules = [];
            foreach ($data as $newSchedule) {
                $key = $newSchedule['user_id'] . '_' . $newSchedule['date'];

                // Ensure user exists before accessing properties
                $user = User::find($newSchedule['user_id']);

                // ✅ Ensure 'user_name' and 'user_email' are always set
                $newSchedule['user_name'] = $user ? $user->name : 'Unknown User';
                $newSchedule['user_email'] = $user ? $user->email : 'N/A';

                // Check for new records or changes
                if (!isset($existingSchedules[$key])) {
                    $newSchedule['old_status'] = 'N/A';
                    $changedSchedules[] = $newSchedule;
                } elseif ($existingSchedules[$key]['status'] !== $newSchedule['status']) {
                    $newSchedule['old_status'] = $existingSchedules[$key]['status'];
                    $changedSchedules[] = $newSchedule;
                }
            }

            // 🔹 STEP 3: Debug existing records if issues persist
            if (empty($changedSchedules)) {
                $this->info('No real changes detected.');
            } else {
                $this->info('Changes detected: ' . json_encode($changedSchedules, JSON_PRETTY_PRINT));
            }

            // 🔹 STEP 4: Update DB AFTER sending email
            if (!empty($changedSchedules)) {
                Mail::to('peter@paperdog.com')->send(new ScheduleUpdatedMail($changedSchedules));
                $this->info('Email notification sent.');
            }

            // ✅ Fix: Ensure `upsert()` properly updates (Matching by `user_id` + `date`)
            UserSchedule::upsert($data, ['user_id', 'date'], ['status']);

            $this->info('Schedules synchronized successfully.');
        } catch (\Exception $e) {
            $this->error('Error syncing office schedules: ' . $e->getMessage());
        }
    }
}
