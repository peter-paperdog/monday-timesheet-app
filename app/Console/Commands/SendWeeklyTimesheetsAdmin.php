<?php

namespace App\Console\Commands;

use App\Mail\WeeklyTimesheetsAdminMail;
use App\Models\MondayTimeTracking;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class SendWeeklyTimesheetsAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send-weekly-timesheets-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly timesheets of all users to the admin.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startOfWeek = Carbon::now()->subWeek()->startOfWeek(); // Get Monday of this week
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of this week

        $this->info("Sending " . $startOfWeek->format('Y-m-d') . " weekly timesheet PDFs of the users to admins.");

        // Define admin recipients
        $recipients = [
            'peter@paperdog.com',
            'gabriella@paperdog.com',
            'mark@paperdog.com',
            'morwenna@paperdog.com'
        ];

        // Get all users and sort admins last
        $users = User::orderByRaw("
            CASE
                WHEN email IN ('" . implode("','", $recipients) . "', 'peter@paperdog.com') THEN 1
                ELSE 0
            END, name ASC
        ")->get();

        // Initialize Mpdf for merging all PDFs
        $mpdf = new Mpdf();

        foreach ($users as $index => $user) {
            // Fetch time tracking data
            $timeTrackings = MondayTimeTracking::where('user_id', $user->id)
                ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
                ->with(['item.group', 'item.board'])
                ->orderBy('started_at')
                ->get();

            if ($timeTrackings->isEmpty()) {
                continue; // Skip users with no data
            }

            // Group data for PDF
            $groupedData = $timeTrackings->groupBy([
                function ($entry) {
                    return Carbon::parse($entry->started_at)->format('Y-m-d (l)');
                },
                'item.board.name',
                'item.group.name',
                'item.name',
            ]);

            // Generate individual user PDF
            $pdf = Pdf::loadView('pdf.timesheet', compact('groupedData', 'startOfWeek', 'endOfWeek', 'user'));
            $pdfPath = storage_path("app/timesheets/timesheet_{$user->id}_{$startOfWeek->format('Y-m-d')}.pdf");

            // Save temporarily
            $pdf->save($pdfPath);

            // Add to Mpdf merger
            $pageCount = $mpdf->SetSourceFile($pdfPath);
            if ($index > 0) {
                $mpdf->AddPage();
            }
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $mpdf->ImportPage($i);
                $mpdf->UseTemplate($tplId);
                if ($i < $pageCount) {
                    $mpdf->AddPage();
                }
            }

            // Delete temporary file
            unlink($pdfPath);
        }

        // Save the final merged PDF
        $finalPdfPath = storage_path("app/timesheets/timesheet_all_users_{$startOfWeek->format('Y-m-d')}.pdf");
        $mpdf->Output($finalPdfPath, Destination::FILE);

        // Send Email with PDF attachment
        Mail::to($recipients)->send(new WeeklyTimesheetsAdminMail($finalPdfPath, $startOfWeek));

        // Delete final PDF after sending
        Storage::delete($finalPdfPath);

        $this->info("Weekly timesheets PDF sent to admins.");
    }
}
