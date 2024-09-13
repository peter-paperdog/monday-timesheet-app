<?php

use App\Services\MondayService;
use App\Services\UserService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    Cache::clear();
    $mondayService = new MondayService();
    $usersService = new UserService($mondayService);

    $User = $usersService->getUserBy('email', 'xxxx@xxxx.com');

    $startOfWeek = new DateTime('2024-08-28');
    $startOfWeek->modify('Monday this week');

    $endOfWeek = clone $startOfWeek;
    $endOfWeek->modify('Sunday this week');

    $timeTrackingItems = $User->getTimeTrackingItemsBetween($startOfWeek, $endOfWeek);
    $itemIds = array_column($timeTrackingItems, 'item_id');

    $items = $mondayService->getItems($itemIds);

    $total = 0;
    $days = [];

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
    $html = view('timesheet', [
        'data' => $data,
        'printedDate' => (new DateTime())->setTimezone(new DateTimeZone('Europe/London'))->format('d/m/Y H:i:s')
    ])->render();

    // Generate the PDF from the concatenated HTML
    $pdf = Pdf::loadHTML($html)
        ->setPaper('a4', 'portrait');

    // Display the  PDF in the browser
    return $pdf->stream($startOfWeek->format('Ymd') . '-' . $endOfWeek->format('Ymd') . '_timesheet_' . $User->getName() . '.pdf');
});
