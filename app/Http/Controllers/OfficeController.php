<?php

namespace App\Http\Controllers;

use App\Models\UserSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function schedule(Request $request): View
    {
        // Get the selected week's start date or default to current Monday
        $selectedDate = $request->input('weekStartDate') ?: Carbon::now()->startOfWeek()->toDateString();
        $startOfWeek = Carbon::parse($selectedDate)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week

        // Fetch schedules and join with users table
        $schedules = UserSchedule::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->join('users', 'user_schedules.user_id', '=', 'users.id') // Ensure users table exists
            ->select('user_schedules.*', 'users.name as username') // Select username instead of user_id
            ->orderBy('date')
            ->get()
            ->groupBy('date'); // Group by date for easy display

        return view('office-schedule', [
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'schedules' => $schedules
        ]);
    }
}
