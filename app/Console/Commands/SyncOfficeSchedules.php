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
                    'user_id' => $item['user_id'],
                    'date'    => $item['date'],
                    'status'  => $item['status'],
                ];
            }, $data);

            // Fetch existing schedules
            $existingSchedules = UserSchedule::whereIn('user_id', array_column($data, 'user_id'))
                ->whereIn('date', array_column($data, 'date'))
                ->with('user') // Fetch user details
                ->get()
                ->keyBy(fn($item) => $item->user_id . '_' . $item->date)
                ->toArray();

            // Find schedules that need to be included in the email
            $changedSchedules = [];
            foreach ($data as $newSchedule) {
                $key = $newSchedule['user_id'] . '_' . $newSchedule['date'];

                // Get user details
                $user = User::find($newSchedule['user_id']);

                // If user exists, add name and email
                $newSchedule['user_name'] = $user ? $user->name : 'Unknown User';
                $newSchedule['user_email'] = $user ? $user->email : null;

                // Schedule does not exist in the database (new record)
                if (!isset($existingSchedules[$key])) {
                    $newSchedule['old_status'] = 'N/A'; // No previous record
                    $changedSchedules[] = $newSchedule;
                }
                // Schedule exists but status has changed
                elseif (isset($existingSchedules[$key]) && $existingSchedules[$key]['status'] !== $newSchedule['status']) {
                    $newSchedule['old_status'] = $existingSchedules[$key]['status'] ?? 'N/A';
                    $changedSchedules[] = $newSchedule;
                }
            }

            // Send email if relevant changes occurred
            if (!empty($changedSchedules)) {
                Mail::to('peter@paperdog.com')->send(new ScheduleUpdatedMail($changedSchedules));
                $this->info('Email notification sent for updated schedules.');
            }

            // Insert/update database records
            UserSchedule::upsert($data, ['user_id', 'date'], ['status']);

            $this->info('Schedules synchronized successfully.');
        } catch (\Exception $e) {
            $this->error('Error syncing office schedules: ' . $e->getMessage());
        }
    }
}
