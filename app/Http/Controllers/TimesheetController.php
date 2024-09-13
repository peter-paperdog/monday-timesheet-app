<?php

namespace App\Http\Controllers;

use App\Services\MondayService;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    protected $mondayService;
    protected $cacheTTL = 600; // Cache time-to-live in seconds (10 minutes)
    public function __construct(MondayService $mondayService)
    {
        $this->mondayService = $mondayService;
    }

    public function generateTimesheet(Request $request)
    {
        $date = new \DateTime();

        $startOfWeek = (clone $date)->modify('Monday this week');
        $endOfWeek = (clone $date)->modify('Sunday this week');

        $usersObj = $this->initializeUsers($startOfWeek, $endOfWeek);

        $this->processBoards($usersObj, $startOfWeek, $endOfWeek);

        $this->cleanUpUserData($usersObj);

        return $this->generatePDF($usersObj);
    }
    protected function initializeUsers($startOfWeek, $endOfWeek)
    {
        $users = Cache::remember('users', $this->cacheTTL, function () {
            return $this->mondayService->getUsers();
        });

        $usersObj = new \stdClass();
        foreach ($users as $user) {
            $usersObj->{$user['id']} = $user;
            $usersObj->{$user['id']}['days'] = [];
            $usersObj->{$user['id']}['totalSeconds'] = 0;
            $usersObj->{$user['id']}['taskTotalSeconds'] = [];

            for ($i = 1; $i <= 7; $i++) {
                $currentDay = (clone $startOfWeek)->modify('+' . ($i - 1) . ' days');
                $usersObj->{$user['id']}['days'][$i] = [
                    'boards' => [],
                    'date' => [
                        'dayNr' => $i,
                        'dayStr' => $currentDay->format('l'),
                        'date' => $currentDay->format('d/m/Y'),
                    ],
                ];
            }
        }

        return $usersObj;
    }


    protected function processBoards(&$usersObj, $startOfWeek, $endOfWeek)
    {
        $boards = Cache::remember('boards_' . $startOfWeek->format('YW'), $this->cacheTTL, function () {
            return $this->mondayService->getBoards();
        });

        foreach ($boards as $board) {
            $board_id = $board['id'];
            $boardData = Cache::remember('board_' . $startOfWeek->format('YW') . '_' . $board_id, $this->cacheTTL, function () use ($board_id) {
                return $this->mondayService->getTimeTrackingDataForBoard($board_id);
            });

            foreach ($boardData['items_page']['items'] as $item) {
                $this->processItem($item, $boardData, $usersObj, $startOfWeek, $endOfWeek);
            }
        }
    }

    protected function processItem($item, $boardData, &$usersObj, $startOfWeek, $endOfWeek)
    {
        $item_id = $item['id'];
        $item_name = $item['name'];
        $group_name = $item['group']['title'];

        foreach ($item['column_values'] as $column_value) {
            if (!empty($column_value)) {
                foreach ($column_value['history'] as $history_item) {
                    var_dump($history_item);
                    $this->processTimeRecord($history_item, $group_name, $item_name, $boardData, $usersObj, $startOfWeek, $endOfWeek);
                }
            }
        }
    }

    protected function processTimeRecord($history_item, $group_name, $item_name, $boardData, &$usersObj, $startOfWeek, $endOfWeek)
    {
        $time_record = new \stdClass();
        $time_record->started_at = $history_item['started_at'];
        $time_record->ended_at = $history_item['ended_at'];

        $startedAt = new \DateTime($time_record->started_at);
        $endedAt = new \DateTime($time_record->ended_at);

        if (is_null($time_record->ended_at) || $endedAt < $startOfWeek || $endedAt > $endOfWeek) {
            return;
        }

        $interval = $startedAt->diff($endedAt);
        $time_record->totalSeconds = $interval->h * 3600 + $interval->i * 60 + $interval->s;
        $userId = $history_item['started_user_id'];
        $usersObj->{$userId}['totalSeconds'] += $time_record->totalSeconds;

        // Ensure the board data contains an ID
        if (!isset($boardData['id'])) {
            return;
        }

        $boardId = $boardData['id'];

        $boardObj = &$usersObj->{$userId}['days'][$endedAt->format('N')]['boards'][$boardId];
        if (!isset($boardObj)) {
            $boardObj = [
                'name' => $boardData['name'] ?? '',  // Ensure name is set, or default to an empty string
                'tasks' => []
            ];
        }

        $taskName = $group_name . ' - ' . $item_name;
        if (!isset($boardObj['tasks'][$taskName])) {
            $boardObj['tasks'][$taskName] = [
                'name' => $taskName,
                'time_records' => []
            ];
        }

        $boardObj['tasks'][$taskName]['time_records'][] = $time_record;

        if (!isset($usersObj->{$userId}['taskTotalSeconds'][$taskName])) {
            $usersObj->{$userId}['taskTotalSeconds'][$taskName] = 0;
        }
        $usersObj->{$userId}['taskTotalSeconds'][$taskName] += $time_record->totalSeconds;
    }

    protected function cleanUpUserData(&$usersObj)
    {
        foreach ($usersObj as $userId => $user) {
            foreach ($user['days'] as $dayIndex => $dayData) {
                foreach ($dayData['boards'] as $boardId => $boardData) {
                    $usersObj->{$userId}['days'][$dayIndex]['boards'][$boardId]['name'] = str_replace("Subitems of ", "", $boardData['name']);
                }
            }
        }
    }

    protected function generatePDF($usersObj)
    {
        $html = '';
        foreach ($usersObj as $userId => $userData) {
            if ($userData['totalSeconds'] === 0) {
                continue;
            }
            $html .= view('timesheet', ['userData' => $userData])->render();
        }

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->stream('timesheet.pdf');
    }
}
