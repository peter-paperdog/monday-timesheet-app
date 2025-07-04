<?php

namespace App\Http\Controllers;

use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\SyncStatus;
use App\Models\User;
use App\Models\UserSchedule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class TimesheetController extends Controller
{

    private function getLastUpdated($type)
    {
        $syncStatuses = SyncStatus::pluck('last_synced_at', 'type');
        return isset($syncStatuses[$type]) ? Carbon::parse($syncStatuses[$type])->diffForHumans() : 'Never';
    }

    public function timesheetPDF($userId, $weekStartDate)
    {
        $startOfWeek = Carbon::parse($weekStartDate)->startOfWeek(); // Ensure it's Monday
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week

        $userId = auth()->user()->admin ? $userId : auth()->id();

        $user = User::findOrFail($userId); // Ensure user exists

        // Fetch time tracking records for the selected user & week
        $timeTrackings = MondayTimeTracking::where('user_id', $userId)
            ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->with(['item.group', 'item.board'])
            ->orderBy('started_at')
            ->get();

        // SUBITEM GROUP FIX ITT
        $timeTrackings->each(function ($tracking) {
            if (!$tracking->item->group && $tracking->item->parent) {
                $tracking->item->group = $tracking->item->parent->group;
            }
        });

        // Group by Day → Board → Group → Task
        $groupedData = $timeTrackings->groupBy([
            function ($entry) {
                return Carbon::parse($entry->started_at)->format('Y-m-d (l)'); // Group by Date
            },
            'item.board.name',   // Group by Board Name
            'item.group.name',   // Group by Group Name
            'item.name',         // Group by Task Name
        ]);

        // Fetch office schedules for the user for the same week
        $officeSchedules = UserSchedule::where('user_id', $userId)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->pluck('status', 'date');

        $pdf = Pdf::loadView('pdf.timesheet', compact('officeSchedules', 'groupedData', 'startOfWeek', 'endOfWeek', 'user'));
        return $pdf->stream("timesheet_{$user->name}_{$startOfWeek->format('Y-m-d')}.pdf");
    }

    public function timesheetsPDF($weekStartDate)
    {
        $startOfWeek = Carbon::parse($weekStartDate)->startOfWeek(); // Ensure it's Monday
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week

        $users = User::orderByRaw("
        CASE
            WHEN email IN ('amo@paperdog.com', 'mark@paperdog.com', 'morwenna@paperdog.com', 'oliver@paperdog.com', 'peter@paperdog.com') THEN 1
            ELSE 0
        END, name ASC
    ")->get();

        // Initialize Mpdf instance for merging
        $mpdf = new Mpdf();

        foreach ($users as $index => $user) {
            // Fetch all time tracking records for the user at once to minimize queries
            $timeTrackings = MondayTimeTracking::where('user_id', $user->id)
                ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
                ->with(['item.group', 'item.board'])
                ->orderBy('started_at')
                ->get();

            // SUBITEM GROUP FIX ITT
            $timeTrackings->each(function ($tracking) {
                if (!$tracking->item->group && $tracking->item->parent) {
                    $tracking->item->group = $tracking->item->parent->group;
                }
            });

            if ($timeTrackings->isEmpty()) {
                continue; // Skip users with no time tracking data
            }

            // Group by Day → Board → Group → Task
            $groupedData = $timeTrackings->groupBy([
                function ($entry) {
                    return Carbon::parse($entry->started_at)->format('Y-m-d (l)');
                },
                'item.board.name',
                'item.group.name',
                'item.name',
            ]);

            // Fetch office schedules for the user for the same week
            $officeSchedules = UserSchedule::where('user_id', $user->id)
                ->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->pluck('status', 'date');

            // Generate individual PDF for the user
            $pdf = Pdf::loadView('pdf.timesheet', compact('officeSchedules', 'groupedData', 'startOfWeek', 'endOfWeek', 'user'));
            $pdfPath = storage_path("app/timesheets/timesheet_{$user->id}_{$startOfWeek->format('Y-m-d')}.pdf");

            // Save the PDF temporarily
            $pdf->save($pdfPath);

            // Add to Mpdf merger
            $pageCount = $mpdf->SetSourceFile($pdfPath);

            // Ensure a new page is added between users
            if ($index > 0) {
                $mpdf->AddPage();
            }

            // Import all pages of the user's PDF
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $mpdf->ImportPage($i);
                $mpdf->UseTemplate($tplId);
                if ($i < $pageCount) {
                    $mpdf->AddPage();
                }
            }

            // Delete temporary file to free up space
            unlink($pdfPath);
        }

        // Return the merged PDF as a response
        return response($mpdf->Output("timesheet_all_users_{$startOfWeek->format('Y-m-d')}.pdf", Destination::INLINE))
            ->header('Content-Type', 'application/pdf');
    }

    public function dashboard(Request $request): View
    {
        $selectedUserId = auth()->user()->admin ? $request->input('user_id', auth()->id()) : auth()->id();

        return view('dashboard', [
            'items' => MondayItem::whereHas('assignedUsers', function ($query) use ($selectedUserId) {
                $query->where('users.id', $selectedUserId);
            })
                ->with([
                    'board:id,name',    // Preload board details
                    'parent:id,name',   // Preload parent details
                    'group:id,name'     // Preload group details
                ])
                ->select([
                    'monday_items.id',
                    'monday_items.name',
                    'monday_items.board_id',
                    'monday_items.parent_id',
                    'monday_items.group_id'
                ])
                ->join('monday_boards', 'monday_items.board_id', '=', 'monday_boards.id') // Join boards
                ->leftJoin('monday_groups', 'monday_items.group_id', '=', 'monday_groups.id') // Left join groups (handles null group_id)
                ->orderBy('monday_boards.name', 'asc') // Order by board name
                ->get(),
            'lastupdated' => $this->getLastUpdated('monday-assignments'),
            'selectedUserId' => $selectedUserId,
            'users' => User::orderBy('name', 'asc')->get()
        ]);
    }

    public function timesheets(Request $request): View
    {
        $selectedUserId = auth()->user()->admin ? $request->input('user_id', auth()->id()) : auth()->id();

        // Get the selected week's start date or default to current Monday
        $selectedDate = $request->input('weekStartDate') ?: Carbon::now()->startOfWeek()->toDateString();
        $startOfWeek = Carbon::parse($selectedDate)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week


        // Fetch time tracking records for the selected user & week
        $timeTrackings = MondayTimeTracking::where('user_id', $selectedUserId)
            ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->with(['item.group', 'item.board', 'item.parent.group'])
            ->orderBy('started_at')
            ->get();

        // Fill missing group from parent if it's a subitem
        $timeTrackings->each(function ($tracking) {
            if ($tracking->item && !$tracking->item->group && $tracking->item->parent) {
                $tracking->item->group = $tracking->item->parent->group;
            }
        });

        // Group by Day → Board → Group → Task
        $groupedData = $timeTrackings->groupBy([
            function ($entry) {
                return Carbon::parse($entry->started_at)->format('Y-m-d (l)'); // Group by Date
            },
            'item.board.name',   // Group by Board Name
            'item.group.name',   // Group by Group Name
            'item.name',         // Group by Task Name
        ]);

        // Fetch office schedules for the user for the same week
        $officeSchedules = UserSchedule::where('user_id', $selectedUserId)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->pluck('status', 'date'); // Get as key-value pair [date => status]

        return view('timesheets', [
            'officeSchedules' => $officeSchedules,
            'groupedData' => $groupedData,
            'selectedUserId' => $selectedUserId,
            'selectedDate' => $startOfWeek->toDateString(),
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'lastupdated' => $this->getLastUpdated('monday-boards'),
            'users' => User::orderBy('name', 'asc')->get()
        ]);
    }

    public function calendar(Request $request)
    {
        $selectedUserId = auth()->user()->admin ? $request->input('user_id', auth()->id()) : auth()->id();

        return view('calendar', [
            'users' => User::orderBy('name', 'asc')->get(),
            'selectedUserId' => $selectedUserId,
            'lastupdated' => $this->getLastUpdated('monday-boards')
        ]);
    }

    public function timetracking(Request $request)
    {
        // Determine selected user (if admin), otherwise use current user
        $selectedUserId = auth()->user()->admin
            ? $request->input('user_id', auth()->id())
            : auth()->id();

        // Parse date range with fallback to last 7 days
        $startDate = $request->input('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : now()->subDays(6)->startOfDay();

        $endDate = $request->input('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : now()->endOfDay();

        // Fetch time tracking entries with item and board relations
        $entries = MondayTimeTracking::where('user_id', $selectedUserId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->with(['item.board', 'item.group', 'item.parent'])
            ->orderBy('started_at', 'desc')
            ->get();

        // Fetch users list for admin
        $users = auth()->user()->admin ? User::orderBy('name')->get() : collect();

        return view('timetracking', [
            'trackings' => $entries,
            'selectedUserId' => $selectedUserId,
            'start' => $startDate,
            'end' => $endDate,
            'users' => $users
        ]);
    }


    public function downloadTimeTrackingExcel(Request $request)
    {
        $userId = auth()->user()->admin ? $request->input('user_id') : auth()->id();
        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();

        $entries = MondayTimeTracking::where('user_id', $userId)
            ->whereBetween('started_at', [$start, $end])
            ->with(['user', 'item.board'])
            ->orderBy('started_at', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->fromArray([
            'Date', 'User', 'Board', 'Task', 'Start Time', 'End Time', 'Duration (minutes)'
        ], null, 'A1');

        // Rows
        $row = 2;
        foreach ($entries as $entry) {
            $startAt = $entry->started_at;
            $endAt = $entry->ended_at;
            $duration = $startAt && $endAt ? $startAt->diffInMinutes($endAt) : null;

            $sheet->fromArray([
                $startAt?->toDateString(),
                $entry->user->name ?? '-',
                $entry->item->board->name ?? '-',
                $entry->item->name ?? '-',
                $startAt?->format('H:i'),
                $endAt?->format('H:i'),
                $duration,
            ], null, 'A' . $row++);
        }

        $writer = new Xlsx($spreadsheet);

        // Stream the file
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'time_tracking_report.xlsx');
    }
}
