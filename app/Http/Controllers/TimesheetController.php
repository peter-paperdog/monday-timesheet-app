<?php

namespace App\Http\Controllers;

use App\Models\MondayTimeTracking;
use App\Models\User;
use App\Services\MondayService;
use App\Services\UserService;
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
        $user = Auth::User();

        // Get selected date from request, or default to current week's Monday
        $selectedDate = $request->input('weekStartDate', Carbon::now()->startOfWeek()->toDateString());
        $startOfWeek = Carbon::parse($selectedDate)->startOfWeek(); // Ensure it starts on Monday
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week

        $timeTrackings = MondayTimeTracking::where('user_id', $user->id)
            ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->orderBy('started_at', 'desc')
            ->get();

        return view('timesheets', [
            'user' => $user,
            'timeTrackings' => $timeTrackings,
            'selectedDate' => $selectedDate,
            'users' => User::all()
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
