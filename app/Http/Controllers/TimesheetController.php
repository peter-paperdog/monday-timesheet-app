<?php

namespace App\Http\Controllers;

use App\Models\MondayBoard;
use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\SyncStatus;
use App\Models\User;
use App\Services\MondayService;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimesheetController extends Controller
{

    private function getLastUpdated($type)
    {
        $syncStatuses = SyncStatus::pluck('last_synced_at', 'type');
        return isset($syncStatuses[$type]) ? Carbon::parse($syncStatuses[$type])->diffForHumans() : 'Never';
    }

    public function timesheetPDF($userId, $weekStartDate){
        $startOfWeek = Carbon::parse($weekStartDate)->startOfWeek(); // Ensure it's Monday
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week

        $user = User::findOrFail($userId); // Ensure user exists

        // Fetch time tracking records for the selected user & week
        $timeTrackings = MondayTimeTracking::where('user_id', $userId)
            ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->with(['item.group', 'item.board'])
            ->orderBy('started_at')
            ->get();

        // Group by Day → Board → Group → Task
        $groupedData = $timeTrackings->groupBy([
            function ($entry) {
                return Carbon::parse($entry->started_at)->format('Y-m-d (l)'); // Group by Date
            },
            'item.board.name',   // Group by Board Name
            'item.group.name',   // Group by Group Name
            'item.name',         // Group by Task Name
        ]);

        $pdf = Pdf::loadView('pdf.timesheet', compact('groupedData', 'startOfWeek', 'endOfWeek', 'user'));
        return $pdf->stream("timesheet_{$user->name}_{$startOfWeek->format('Y-m-d')}.pdf");
    }

    public function dashboard(Request $request): View
    {
        $selectedUserId = $request->input('user_id', Auth::id()); // Default to logged-in user


        return view('dashboard', [
            'items' =>  MondayItem::whereHas('assignedUsers', function ($query) use ($selectedUserId) {
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
        $selectedUserId = $request->input('user_id', Auth::id()); // Default to logged-in user

        // Get the selected week's start date or default to current Monday
        $selectedDate = $request->input('weekStartDate') ?: Carbon::now()->startOfWeek()->toDateString();
        $startOfWeek = Carbon::parse($selectedDate)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week


        // Fetch time tracking records for the selected user & week
        $timeTrackings = MondayTimeTracking::where('user_id', $selectedUserId)
            ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->with(['item.group', 'item.board'])
            ->orderBy('started_at')
            ->get();

        // Group by Day → Board → Group → Task
        $groupedData = $timeTrackings->groupBy([
            function ($entry) {
                return Carbon::parse($entry->started_at)->format('Y-m-d (l)'); // Group by Date
            },
            'item.board.name',   // Group by Board Name
            'item.group.name',   // Group by Group Name
            'item.name',         // Group by Task Name
        ]);


        return view('timesheets', [
            'groupedData' => $groupedData,
            'selectedUserId' => $selectedUserId,
            'selectedDate' => $startOfWeek->toDateString(),
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'lastupdated' => $this->getLastUpdated('monday-boards'),
            'users' => User::orderBy('name', 'asc')->get()
        ]);
    }

    public function downloadUserSheet(Request $request)
    {
        $decodedData = json_decode($request->data);

        $data = [
            'name' => $decodedData->name,
            'email' => $decodedData->email,
            'days' => $decodedData->days,
            'time' => $decodedData->time,
            'startOfWeek' => $decodedData->startOfWeek,
            'endOfWeek' => $decodedData->endOfWeek
        ];

        // Append the view content for this user, adding a page break after each user
        $html = view('timesheet', [
            'data' => $data,
            'printedDate' => (new DateTime())->setTimezone(new DateTimeZone('Europe/London'))->format('d/m/Y H:i:s')
        ])->render();

        // Generate the PDF from the concatenated HTML
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        // Display the  PDF in the browser
        return $pdf->stream(str_replace("/", "_",
            "$decodedData->startOfWeek.'-'.$decodedData->endOfWeek.'_timesheet_'.$decodedData->name.'.pdf'"));
    }
}
