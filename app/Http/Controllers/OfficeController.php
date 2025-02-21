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
        $selectedDate = $request->input('weekStartDate') ?: Carbon::now()->startOfWeek()->toDateString();
        $startOfWeek = Carbon::parse($selectedDate)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(4); // Monday - Friday

        // Fetch all schedules and eager load users
        $schedules = UserSchedule::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->with('user') // Ensure user relation is loaded
            ->get();

        // Transform data into a structured format
        $structuredData = [];

        foreach ($schedules as $schedule) {
            $username = $schedule->user->name ?? 'Unknown';

            if (!isset($structuredData[$username])) {
                $structuredData[$username] = [
                    'Monday' => '-',
                    'Tuesday' => '-',
                    'Wednesday' => '-',
                    'Thursday' => '-',
                    'Friday' => '-',
                ];
            }

            // Get the weekday from the date
            $dayOfWeek = Carbon::parse($schedule->date)->format('l'); // e.g., "Monday"

            // Assign the status
            $structuredData[$username][$dayOfWeek] = $schedule->status;
        }

        return view('office-schedule', compact('structuredData', 'startOfWeek', 'endOfWeek'));
    }
}
