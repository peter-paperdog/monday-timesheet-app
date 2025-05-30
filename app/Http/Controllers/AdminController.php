<?php

namespace App\Http\Controllers;

use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\User;
use App\Services\MondayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{

    public function index(Request $request)
    {
// Get the selected week's start date or default to current Monday
        $selectedDate = $request->input('weekStartDate') ?: Carbon::now()->startOfWeek()->toDateString();
        $startOfWeek = Carbon::parse($selectedDate)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Get Sunday of that week

        // Fetch total tracked time for each user in the selected week
// Get all users
        $allUsers = User::orderBy('name', 'asc')->get();

// Get users with logged time
        $userTimeRecords = MondayTimeTracking::whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->join('users', 'monday_time_trackings.user_id', '=', 'users.id')
            ->selectRaw('users.id as user_id, users.name as user_name, SUM(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) as total_minutes')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_minutes')
            ->get();

// Convert to associative array for easy merging
        $loggedUsers = $userTimeRecords->pluck('total_minutes', 'user_id')->toArray();

// Final list: Merge logged and non-logged users
        $finalUserRecords = $allUsers->map(function ($user) use ($loggedUsers) {
            return (object)[
                'user_id' => $user->id,
                'user_name' => $user->name,
                'total_minutes' => $loggedUsers[$user->id] ?? 0, // Set 0 if no time logged
            ];
        })->sortByDesc('total_minutes');

        // Fetch total tracked time for each board in the selected week
        $boardWeeklyTotals = MondayTimeTracking::whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->join('monday_items as item', 'monday_time_trackings.item_id', '=', 'item.id')
            ->join('monday_boards as board', 'item.board_id', '=', 'board.id')
            ->selectRaw('board.id as board_id, board.name as board_name, SUM(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) as total_minutes')
            ->groupBy('board.id', 'board.name')
            ->orderByDesc('total_minutes')
            ->get();

        return view('admin.index', [
            'userWeeklyTotals' => $finalUserRecords,
            'boardWeeklyTotals' => $boardWeeklyTotals,
            'selectedDate' => $startOfWeek->toDateString(),
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'users' => User::orderBy('name', 'asc')->get()
        ]);
    }

    public function record(Request $request, MondayService $mondayService)
    {
        // Ensure at least one task was selected
        if (!$request->has('selected_tasks')) {
            return redirect()->back()->with('error', 'No tasks selected.');
        }
        // Get the selected task IDs
        $taskIds = $request->input('selected_tasks');
        $columns = $mondayService->getTimeTrackingColumns($taskIds);

        $timeTrackingColumns = array_map(function ($item) {
            return array_column(
                array_filter($item['column_values'], fn($col) => isset($col['id'], $col['column']['title'])),
                'column', 'id'
            );
        }, array_column($columns, null, 'id'));


        // Fetch the corresponding tasks from the database
        $tasks = MondayItem::whereIn('id', $taskIds)
            ->with(['board', 'group'])
            ->get();

        return view('admin.record', [
            'tasks' => $tasks,
            'timeTrackingColumns' => $timeTrackingColumns
        ]);
    }
}
