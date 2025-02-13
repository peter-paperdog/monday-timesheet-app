<?php

namespace App\Http\Controllers;

use App\Services\MondayService;
use App\Services\UserService;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimesheetController extends Controller
{
    protected $mondayService;
    // protected $cacheTTL = 600; // Cache time-to-live in seconds (10 minutes)

    /*public function __construct(MondayService $mondayService)
    {
        $this->mondayService = $mondayService;
    }*/

    public function __construct()
    {

    }

    public function generateTimesheet(Request $request)
    {
        $date = new \DateTime();

        $startOfWeek = (clone $date)->modify('Monday last week');
        $endOfWeek = (clone $date)->modify('Sunday this week');

        $usersObj = $this->initializeUsers($startOfWeek, $endOfWeek);

        $this->processBoards($usersObj, $startOfWeek, $endOfWeek);

        $this->cleanUpUserData($usersObj);

        return $this->generatePDF($usersObj);
    }

    protected function initializeUsers($startOfWeek, $endOfWeek)
    {
        /*$users = Cache::remember('users', $this->cacheTTL, function () {
            return $this->mondayService->getUsers();
        });*/

        $users = $this->mondayService->getUsers();

        $usersObj = new \stdClass();
        foreach ($users as $user) {
            $usersObj->{$user['id']} = $user;
            $usersObj->{$user['id']}['days'] = [];
            $usersObj->{$user['id']}['totalSeconds'] = 0;
            $usersObj->{$user['id']}['taskTotalSeconds'] = [];

            for ($i = 1; $i <= 7; $i++) {
                $currentDay = (clone $startOfWeek)->modify('+'.($i - 1).' days');
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
        /*$boards = Cache::remember('boards_'.$startOfWeek->format('YW'), $this->cacheTTL, function () {
            return $this->mondayService->getBoards();
        });*/
        $boards = $this->mondayService->getBoards();

        foreach ($boards as $board) {
            $board_id = $board['id'];
            /*$boardData = Cache::remember('board_'.$startOfWeek->format('YW').'_'.$board_id, $this->cacheTTL,
                function () use ($board_id) {
                    return $this->mondayService->getTimeTrackingDataForBoard($board_id);
                });*/

            $boardData = $this->mondayService->getTimeTrackingDataForBoard($board_id);

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
                    $this->processTimeRecord($history_item, $group_name, $item_name, $boardData, $usersObj,
                        $startOfWeek, $endOfWeek);
                }
            }
        }
    }

    protected function processTimeRecord(
        $history_item,
        $group_name,
        $item_name,
        $boardData,
        &$usersObj,
        $startOfWeek,
        $endOfWeek
    ) {
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

        $taskName = $group_name.' - '.$item_name;
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
                    $usersObj->{$userId}['days'][$dayIndex]['boards'][$boardId]['name'] = str_replace("Subitems of ",
                        "", $boardData['name']);
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

    public function viewUserSheet(Request $request): View
    {


        return view('dashboard', [
            'user' => $request->user(),
         //   'data' => $data,
         //   'users' => $users,
         //   'userMail' => $userMail,
        ]);

        $mondayService = new MondayService();

        $userMail = Auth::user()->email;
        if (isset($request->email)) {
            $userMail = $request->email;
        }
        $usersService = new UserService($mondayService);

        $User = $usersService->getUserBy('email', $userMail);

        $startOfWeek = new DateTime();
        if (isset($request->weekStartDate)) {
            $startOfWeek = new DateTime($request->weekStartDate);
        }

        $startOfWeek->modify('Monday last week');

        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('Sunday this week');

        $timeTrackingItems = $User->getTimeTrackingItemsBetween($startOfWeek, $endOfWeek);

        $total = 0;
        $days = [];

        if (!empty($timeTrackingItems)) {
            $itemIds = array_column($timeTrackingItems, 'item_id');

            //$items = $mondayService->getItems($itemIds);
            $items = [];
            foreach ($itemIds as $itemId){
                $item = $mondayService->getItems([$itemId]);
                $items[$item[0]] = $item[1];
            }

            foreach ($timeTrackingItems as $history) {
                $started_at = new DateTime($history['started_at']);
                $ended_at = new DateTime($history['ended_at']);
                $interval = $started_at->diff($ended_at);
                $duration = $interval->h * 3600 + $interval->i * 60 + $interval->s;

                $day_nr = $started_at->format('N');
                $item_id = $history['item_id'];

                $item = $items[$item_id];
                $item_name = $item['name'];

                if (!isset($days[$day_nr])) {
                    $days[$day_nr] = [
                        'date' => $started_at->format('d/m/Y'),
                        'day' => $started_at->format('l'),
                        'time' => 0,
                        'boards' => []
                    ];
                }

                $board_id = $item['board']['id'];
                $board_name = str_replace('Subitems of ', '', $item['board']['name']);
                $board_group = $item['group']['title'];

                if (!isset($days[$day_nr]['boards'][$board_id])) {
                    $days[$day_nr]['boards'][$board_id] = [
                        'name' => $board_name,
                        'duration' => 0,
                        'groups' => []
                    ];
                }
                if (!isset($days[$day_nr]['boards'][$board_id]['groups'][$board_group])) {
                    $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'] = [];
                    $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['duration'] = 0;
                }

                if (!isset($days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id])) {
                    $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id] = [
                        'item_name' => $item_name,
                        'duration' => 0,
                    ];
                }


                $total += $duration;
                $days[$day_nr]['time'] += $duration;
                $days[$day_nr]['boards'][$board_id]['duration'] += $duration;
                $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['duration'] += $duration;
                $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id]['duration'] += $duration;
            }
            ksort($days);
        }
        $data = [
            'name' => $User->getName(),
            'email' => $User->getEmail(),
            'days' => $days,
            'time' => $total,
            'startOfWeek' => $startOfWeek->format('d/m/Y'),
            'endOfWeek' => $endOfWeek->format('d/m/Y')
        ];


        if (Auth::user()->admin) {
            $users = $usersService->getUsers();
            usort($users, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        } else {
            $users = array();
            $users[] = Auth::user();
        }


        return view('dashboard', [
            'user' => $request->user(),
            'data' => $data,
            'users' => $users,
            'userMail' => $userMail,
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

    public function downloadUserSheetCsv(Request $request)
    {
        $decodedData = json_decode($request->data);

        $data = [
            'name' => $decodedData->name,
            'email' => $decodedData->email,
            'days' => $decodedData->days,
            'time' => $decodedData->time,
            'startOfWeek' => $decodedData->startOfWeek,
            'endOfWeek' => $decodedData->endOfWeek,
        ];

        // Prepare CSV headers
        $csvHeaders = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . str_replace("/", "_", "{$data['startOfWeek']}-{$data['endOfWeek']}_timesheet_{$data['name']}.csv") . '"',
        ];

        // Create CSV content
        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, ['Date', 'Day', 'Project/Task', 'Group', 'Item', 'Hours'],";");

            // Add data for each day
            foreach ($data['days'] as $day) {
                foreach ($day->boards as $board) {
                    foreach ($board->groups as $groupName => $group) {
                        foreach ($group->items as $item) {
                            fputcsv($file, [
                                $day->date,
                                $day->day,
                                $board->name,
                                $groupName,
                                $item->item_name,
                                round($item->duration / 3600, 2),
                            ],";");
                        }
                    }

                    // Add board total row
                    fputcsv($file, [
                        $day->date,
                        $day->day,
                        $board->name,
                        '',
                        'Total for Board',
                        round($board->duration / 3600, 2),
                    ],";");
                }

                // Add day total row
                fputcsv($file, [
                    $day->date,
                    $day->day,
                    '',
                    '',
                    'Total for Day',
                    round($day->time / 3600, 2),
                ],";");
            }

            // Add weekly total row
            fputcsv($file, ['', '', '', '', 'Total for Week', round($data['time'] / 3600, 2)],";");

            fclose($file);
        };

        return response()->stream($callback, 200, $csvHeaders);
    }

    private function getDailyData($timeTrackingItems){
        $mondayService = new MondayService();

        $total = 0;
        $days = [];
        if (!empty($timeTrackingItems)) {
            $itemIds = array_column($timeTrackingItems, 'item_id');

            $items = [];
            foreach ($itemIds as $itemId){
                $item = $mondayService->getItems([$itemId]);
                $items[$item[0]] = $item[1];
            }

            foreach ($timeTrackingItems as $history) {
                $started_at = new DateTime($history['started_at']);
                $ended_at = new DateTime($history['ended_at']);
                $interval = $started_at->diff($ended_at);
                $duration = $interval->h * 3600 + $interval->i * 60 + $interval->s;

                $day_nr = $started_at->format('N');
                $item_id = $history['item_id'];

                if (isset($items[$item_id])) {
                    $item = $items[$item_id];
                    $item_name = $item['name'];

                    if (!isset($days[$day_nr])) {
                        $days[$day_nr] = [
                            'date' => $started_at->format('d/m/Y'),
                            'day' => $started_at->format('l'),
                            'time' => 0,
                            'boards' => []
                        ];
                    }

                    $board_id = $item['board']['id'];
                    $board_name = str_replace('Subitems of ', '', $item['board']['name']);
                    $board_group = $item['group']['title'];

                    if (!isset($days[$day_nr]['boards'][$board_id])) {
                        $days[$day_nr]['boards'][$board_id] = [
                            'name' => $board_name,
                            'duration' => 0,
                            'groups' => []
                        ];
                    }
                    if (!isset($days[$day_nr]['boards'][$board_id]['groups'][$board_group])) {
                        $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'] = [];
                        $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['duration'] = 0;
                    }

                    if (!isset($days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id])) {
                        $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id] = [
                            'item_name' => $item_name,
                            'duration' => 0,
                        ];
                    }


                    $total += $duration;
                    $days[$day_nr]['time'] += $duration;
                    $days[$day_nr]['boards'][$board_id]['duration'] += $duration;
                    $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['duration'] += $duration;
                    $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id]['duration'] += $duration;
                }
            }
            ksort($days);
        }

        return [
            'days' =>$days,
            'total' => $total
        ];
    }
    public function downloadAllTimeSheet(): string
    {
        $combinedHtml = '';
        $mondayService = new MondayService();
        $usersService = new UserService($mondayService);

        $allTimeTrackingItems = $mondayService->getTimeTrackingItems();

        $users = $usersService->getUsers();

        $exceptions = [
            'petra@paperdog.com', 'szonja@paperdog.com', 'oliver@paperdog.com', 'amo@paperdog.com',
            'morwenna@paperdog.com', 'jason@paperdog.com', 'kata@paperdg.com', 'gabriel@paperdg.com',
        ];

        foreach ($users as $user) {
            if (!in_array($user['email'], $exceptions)) {

                $User = $usersService->getUserBy('email', $user['email']);

                $startOfWeek = new DateTime();
                $startOfWeek->modify('Monday last week');

                $endOfWeek = clone $startOfWeek;
                $endOfWeek->modify('Sunday this week');

                $dailyData = $this->getDailyData($allTimeTrackingItems);

                $data = [
                    'name' => $User->getName(),
                    'email' => $User->getEmail(),
                    'days' => $dailyData['days'],
                    'time' => $dailyData['total'],
                    'startOfWeek' => $startOfWeek->format('d/m/Y'),
                    'endOfWeek' => $endOfWeek->format('d/m/Y')
                ];

                // Append the view content for this user, adding a page break after each user
                $html = view('allusertimesheet', [
                    'data' => $data,
                    'printedDate' => (new DateTime())->setTimezone(new DateTimeZone('Europe/London'))->format('d/m/Y H:i:s')
                ])->render();
                $combinedHtml .= $html;
            }

        }

        // Generate the PDF from the concatenated HTML
        $pdf = Pdf::loadHTML($combinedHtml)
            ->setPaper('a4', 'portrait');

        // Display the  PDF in the browser
        //return $pdf->stream('test.pdf');
        return $pdf->output();
    }

    public function getTimeTrackingItems($TimeTrackingItems, $userId)
    {
        return array_filter($TimeTrackingItems, function ($item) use ($userId) {
            return $item['started_user_id'] === $userId;
        });
    }

    public function getTimeTrackingItemsBetween(DateTime $start, DateTime $end, $TimeTrackingItems, $userId)
    {

        // Fetch all time tracking items for the user
        $items = $this->getTimeTrackingItems($TimeTrackingItems, $userId);

        // Filter the items based on the provided start and end dates
        $filteredItems = array_filter($items, function ($item) use ($start, $end) {
            // Convert the 'started_at' and 'ended_at' to DateTime objects
            $startedAt = new DateTime($item['started_at']);
            $endedAt = new DateTime($item['ended_at']);
            // Check if the item falls within the specified date range
            return $startedAt >= $start && $endedAt <= $end;
        });

        return $filteredItems;
    }

    public function downloadUserTimeSheet($User, $allTimeTrackingItems): string
    {
        //Cache::clear();
        $mondayService = new MondayService();
        $startOfWeek = new DateTime();
        $startOfWeek->modify('Monday last week');

        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('Sunday this week');

        $timeTrackingItems = $this->getTimeTrackingItemsBetween($startOfWeek, $endOfWeek,
            $allTimeTrackingItems, $User->getId());

        $total = 0;
        $days = [];
        if (!empty($timeTrackingItems)) {
            $itemIds = array_column($timeTrackingItems, 'item_id');

            //$items = $mondayService->getItems($itemIds);
            $items = [];
            foreach ($itemIds as $itemId){
                $item = $mondayService->getItems([$itemId]);
                $items[$item[0]] = $item[1];
            }

            foreach ($timeTrackingItems as $history) {
                $started_at = new DateTime($history['started_at']);
                $ended_at = new DateTime($history['ended_at']);
                $interval = $started_at->diff($ended_at);
                $duration = $interval->h * 3600 + $interval->i * 60 + $interval->s;

                $day_nr = $started_at->format('N');
                $item_id = $history['item_id'];

                if (isset($items[$item_id])) {
                    $item = $items[$item_id];
                    $item_name = $item['name'];

                    if (!isset($days[$day_nr])) {
                        $days[$day_nr] = [
                            'date' => $started_at->format('d/m/Y'),
                            'day' => $started_at->format('l'),
                            'time' => 0,
                            'boards' => []
                        ];
                    }

                    $board_id = $item['board']['id'];
                    $board_name = str_replace('Subitems of ', '', $item['board']['name']);
                    $board_group = $item['group']['title'];

                    if (!isset($days[$day_nr]['boards'][$board_id])) {
                        $days[$day_nr]['boards'][$board_id] = [
                            'name' => $board_name,
                            'duration' => 0,
                            'groups' => []
                        ];
                    }
                    if (!isset($days[$day_nr]['boards'][$board_id]['groups'][$board_group])) {
                        $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'] = [];
                        $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['duration'] = 0;
                    }

                    if (!isset($days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id])) {
                        $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id] = [
                            'item_name' => $item_name,
                            'duration' => 0,
                        ];
                    }


                    $total += $duration;
                    $days[$day_nr]['time'] += $duration;
                    $days[$day_nr]['boards'][$board_id]['duration'] += $duration;
                    $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['duration'] += $duration;
                    $days[$day_nr]['boards'][$board_id]['groups'][$board_group]['items'][$item_id]['duration'] += $duration;
                }
            }
            ksort($days);
        }
        $data = [
            'name' => $User->getName(),
            'email' => $User->getEmail(),
            'days' => $days,
            'time' => $total,
            'startOfWeek' => $startOfWeek->format('d/m/Y'),
            'endOfWeek' => $endOfWeek->format('d/m/Y')
        ];


        //return response()->json($data);

        // Append the view content for this user, adding a page break after each user
        $html = view('allusertimesheet', [
            'data' => $data,
            'printedDate' => (new DateTime())->setTimezone(new DateTimeZone('Europe/London'))->format('d/m/Y H:i:s')
        ])->render();


        // Generate the PDF from the concatenated HTML
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        // Display the  PDF in the browser
        //return $pdf->stream('test.pdf');
        return $pdf->output();
    }
}
