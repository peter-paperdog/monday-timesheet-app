<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Models\MondayTimeTracking;
use App\Mail\WeeklyTimesheetEmail;

class SendWeeklyTimesheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send-weekly-timesheets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly timesheet PDFs to all users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startOfWeek = Carbon::now()->subWeek()->startOfWeek(); // Last Monday
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Last Sunday
        $this->info("Sending " . $startOfWeek->format('Y-m-d') . " weekly timesheet PDFs to the users.");

        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            $this->error("Processing {$user->name}....");
            // Fetch user's time tracking data
            $timeTrackings = MondayTimeTracking::where('user_id', $user->id)
                ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
                ->with(['item.group', 'item.board'])
                ->orderBy('started_at')
                ->get();

            // Skip if no records found
            if ($timeTrackings->isEmpty()) {
                $this->error("No records found. Skip processing.");
                continue;
            }

            // Group data for PDF
            $groupedData = $timeTrackings->groupBy([
                function ($entry) {
                    return Carbon::parse($entry->started_at)->format('Y-m-d (l)'); // Date format
                },
                'item.board.name',   // Group by Board Name
                'item.group.name',   // Group by Group Name
                'item.name',         // Group by Task Name
            ]);

            $storagePath = storage_path('app/timesheets');
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0777, true); // Create directory if it doesn't exist
            }

// Define the file path
            $filePath = $storagePath . "/timesheet_{$user->id}_{$startOfWeek->format('Y-m-d')}.pdf";

// Generate and save the PDF
            $pdf = Pdf::loadView('pdf.timesheet', compact('groupedData', 'startOfWeek', 'endOfWeek', 'user'));
            $pdf->save($filePath);

            // Send email
            Mail::to($user->email)->send(new WeeklyTimesheetEmail($user, $startOfWeek, $filePath));
            $this->info('E-mail sent to ' . $user->name . ' (' . $user->email . ')');

            // Optionally delete PDF after sending
            unlink($filePath);
        }

        $this->info('Weekly timesheets sent successfully!');
    }
}
