<?php

namespace App\Http\Controllers;

use App\Models\MondayBoard;
use App\Models\MondayTimeTracking;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimesheetController extends Controller
{

    public function dashboard(Request $request): View
    {
        return view('dashboard');
    }

    public function timesheets(Request $request): View
    {
        $selectedUserId = $request->input('user_id', Auth::id()); // Default to logged-in user

        // Get selected date from request, or default to current week's Monday
        $selectedDate = $request->input('weekStartDate', Carbon::now()->startOfWeek()->toDateString());
        $startOfWeek = Carbon::parse($selectedDate)->startOfWeek(); // Ensure it starts on Monday
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week

        // Fetch the most recent `updated_at` from boards
        $oldestUpdatedBoard = MondayBoard::orderBy('updated_at', 'asc')->value('updated_at');

        // Convert `updated_at` to human-readable format (e.g., "45 minutes ago")
        $lastupdated = $oldestUpdatedBoard
            ? (int) Carbon::parse($oldestUpdatedBoard)->diffInMinutes(Carbon::now()) . ' minutes ago'
            : 'Never updated';

        $timeTrackings = MondayTimeTracking::where('user_id', $selectedUserId)
            ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->orderBy('started_at', 'desc')
            ->get();

        return view('timesheets', [
            'timeTrackings' => $timeTrackings,
            'selectedUserId' => $selectedUserId,
            'selectedDate' => $selectedDate,
            'lastupdated' => $lastupdated,
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
