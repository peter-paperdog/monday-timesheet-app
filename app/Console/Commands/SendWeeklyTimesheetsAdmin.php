<?php

namespace App\Console\Commands;

use App\Mail\WeeklyTimesheetsAdminMail;
use App\Models\MondayTimeTracking;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
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
            'morwenna@paperdog.com',
            'oliver@paperdog.com',
            'amo@paperdog.com',
            'mark@paperdog.com',
            'gabriella@paperdog.com',
            'peter@paperdog.com',
        ];

        // Get all users and sort admins last
        $users = User::orderByRaw("
        CASE
            WHEN email IN ('" . implode("','", $recipients) . "') THEN 1
            ELSE 0
        END, name ASC
    ")->get();

        // Initialize Mpdf for merging all PDFs
        $mpdf = new Mpdf();

        // **User Total Report**
        $userTimeRecords = MondayTimeTracking::whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->join('users', 'monday_time_trackings.user_id', '=', 'users.id')
            ->selectRaw('users.id as user_id, users.name as user_name, SUM(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) as total_minutes')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_minutes')
            ->get();

        $loggedUsers = $userTimeRecords->pluck('total_minutes', 'user_id')->toArray();

        $userWeeklyTotals = $users->map(function ($user) use ($loggedUsers) {
            return (object)[
                'user_id' => $user->id,
                'user_name' => $user->name,
                'total_minutes' => $loggedUsers[$user->id] ?? 0,
            ];
        })->sortByDesc('total_minutes');

        $userTotalsPdf = Pdf::loadView('pdf.usertotal', compact('userWeeklyTotals', 'startOfWeek', 'endOfWeek'));
        $userTotalsPath = storage_path("app/timesheets/user_totals_{$startOfWeek->format('Y-m-d')}.pdf");
        $userTotalsPdf->save($userTotalsPath);
        $mpdf->SetSourceFile($userTotalsPath);
        $mpdf->AddPage();
        $tplId = $mpdf->ImportPage(1);
        $mpdf->UseTemplate($tplId);

        // **Board Total Report**
        $boardWeeklyTotals = MondayTimeTracking::whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->join('monday_items as item', 'monday_time_trackings.item_id', '=', 'item.id')
            ->join('monday_boards as board', 'item.board_id', '=', 'board.id')
            ->selectRaw('board.id as board_id, board.name as board_name, SUM(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) as total_minutes')
            ->groupBy('board.id', 'board.name')
            ->orderByDesc('total_minutes')
            ->get();

        $boardTotalsPdf = Pdf::loadView('pdf.boardtotal', compact('boardWeeklyTotals', 'startOfWeek', 'endOfWeek'));
        $boardTotalsPath = storage_path("app/timesheets/board_totals_{$startOfWeek->format('Y-m-d')}.pdf");
        $boardTotalsPdf->save($boardTotalsPath);
        $mpdf->SetSourceFile($boardTotalsPath);
        $mpdf->AddPage();
        $tplId = $mpdf->ImportPage(1);
        $mpdf->UseTemplate($tplId);

        // **User-Specific Timesheets**
        foreach ($users as $index => $user) {
            $timeTrackings = MondayTimeTracking::where('user_id', $user->id)
                ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
                ->with(['item.group', 'item.board'])
                ->orderBy('started_at')
                ->get();

            if ($timeTrackings->isEmpty()) {
                continue; // Skip users with no time logged
            }

            $groupedData = $timeTrackings->groupBy([
                function ($entry) {
                    return Carbon::parse($entry->started_at)->format('Y-m-d (l)');
                },
                'item.board.name',
                'item.group.name',
                'item.name',
            ]);

            $userPdf = Pdf::loadView('pdf.timesheet', compact('groupedData', 'startOfWeek', 'endOfWeek', 'user'));
            $userPdfPath = storage_path("app/timesheets/timesheet_{$user->id}_{$startOfWeek->format('Y-m-d')}.pdf");

            // Save PDF
            $userPdf->save($userPdfPath);

            // Merge into Mpdf
            $pageCount = $mpdf->SetSourceFile($userPdfPath);
            $mpdf->AddPage();
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $mpdf->ImportPage($i);
                $mpdf->UseTemplate($tplId);
                if ($i < $pageCount) {
                    $mpdf->AddPage();
                }
            }
            // Cleanup temporary user file
            unlink($userPdfPath);
        }

        // Save the final merged PDF
        $finalPdfPath = storage_path("app/timesheets/timesheet_all_users_{$startOfWeek->format('Y-m-d')}.pdf");
        $mpdf->Output($finalPdfPath, Destination::FILE);

        // Cleanup temp files
        unlink($userTotalsPath);
        unlink($boardTotalsPath);

        // Send Email with PDF attachment
        Mail::to($recipients)->send(new WeeklyTimesheetsAdminMail($finalPdfPath, $startOfWeek));

        $this->info("Weekly timesheets PDF sent to admins.");
    }
}
