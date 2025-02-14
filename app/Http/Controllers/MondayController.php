<?php

namespace App\Http\Controllers;

use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\User;
use App\Services\MondayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MondayController extends Controller
{
    public function recordtime(Request $request, MondayService $mondayService)
    {
        $start_times = $request->input('start_time');
        $end_times = $request->input('end_time');
        $date = $request->input('date');
        $time_tracking_columns = $request->input('time_tracking_column');


        // Extract task IDs from submitted data
        $taskIds = array_keys($start_times);

        // Fetch the Monday Items with their Board IDs
        $tasks = MondayItem::whereIn('id', $taskIds)
            ->with('board')  // Ensure the board relation is loaded
            ->get()
            ->keyBy('id');  // Make sure results are indexed by task ID

        $responses = [];

        // Loop through each task and send the API request
        foreach ($tasks as $taskId => $task) {
            if (!isset($task->board)) {
                continue; // Skip if board is missing
            }

            $boardId = $task->board->id;
            $columnId = $time_tracking_columns[$taskId] ?? null;
            $startTime = $start_times[$taskId] ?? null;
            $endTime = $end_times[$taskId] ?? null;

            if ($columnId && $startTime && $endTime) {
                // Convert start/end time to UNIX timestamp
                $startTimestamp = Carbon::parse("{$date} {$startTime}", 'UTC')->timestamp;
                $endTimestamp = Carbon::parse("{$date} {$endTime}", 'UTC')->timestamp;

                // Call the Monday API
                $response = $mondayService->updateTimeTracking($boardId, $taskId, $columnId, $startTimestamp, $endTimestamp);

                // Store response for debugging
                $responses[] = [
                    'task_id' => $taskId,
                    'board_id' => $boardId,
                    'column_id' => $columnId,
                    'start' => $startTimestamp,
                    'end' => $endTimestamp,
                    'response' => $response
                ];
            }
        }
        // as per 14Feb 2025 (by Peter)
        // "error_code": "InvalidColumnTypeException",
        // "message": "This column type is not supported yet in the API",
        var_dump($responses);die();


        return view('admin.recorded', [
            'tasks' => $tasks,
            'start_times' => $start_times,
            'end_times' => $end_times,
            'date' => $date,
            'time_tracking_columns' => $time_tracking_columns
        ]);
    }
}
