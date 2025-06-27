<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSchedule;
use App\Services\GoogleSheetsService;
use App\Services\SlackService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function schedule(Request $request): View
    {
        $selectedDate = $request->input('weekStartDate') ?: Carbon::now()->startOfWeek()->toDateString();
        $startOfWeek = Carbon::parse($selectedDate)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(4); // Monday - Friday

        // Fetch all schedules and eager load users
        $schedules = UserSchedule::whereBetween('user_schedules.date', [$startOfWeek, $endOfWeek])
            ->join('users', 'user_schedules.user_id', '=', 'users.id') // Join users table
            ->select('user_schedules.*', 'users.name', 'users.location') // Select all schedule fields + username
            ->orderBy('users.location', 'asc') // Order by location
            ->orderBy('users.name', 'asc') // Order by username
            ->get();

        // Transform data into a structured format
        $structuredData = [];
        $locations = [];

        $countryToFlag = [
            'hungary' => 'hu',
            'united kingdom' => 'gb',
            'spain' => 'es',
            'canada' => 'ca',
        ];

        foreach ($schedules as $schedule) {
            $username = $schedule->user->name ?? 'Unknown';
            $locations[$schedule->user->name] = $countryToFlag[strtolower($schedule->user->location)] ?? 'unknown';

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

        return view('office-schedule', compact('structuredData', 'startOfWeek', 'endOfWeek', 'locations'));
    }

    public function slackAnswer(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = json_decode($request->input('payload'), true);

        if (!isset($payload['actions'][0])) {
            return response()->json(['error' => 'Invalid interaction data'], 400);
        }

        $selectedOption = str_replace('_', ' ', $payload['actions'][0]['value']);
        $responseUrl = $payload['response_url'];
        $user = User::where('slack_id', $payload['user']['id'])->first();

        Log::info("{$user->name} selected: {$selectedOption}");

        $tsDate = Carbon::createFromTimestamp((int) $payload['message']['ts'])->toDateString();

        $user->schedules()
            ->where('date', $tsDate)
            ->update(['status' => $selectedOption]);

        $googlesheetservice = app(GoogleSheetsService::class);

        $googlesheetservice->updateHUOfficeSchedule($user->email, $tsDate, $selectedOption);

        $slackService = new SlackService();
        $slackService->updateSlackMessage($responseUrl, $selectedOption);

        return response()->json(['success' => true]);
    }
}
