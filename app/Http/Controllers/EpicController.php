<?php

namespace App\Http\Controllers;

use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EpicController extends Controller
{
    public function index(Request $request)
    {
        $entries = array();
        $items = MondayItem::where('board_id', '17480895')
            ->get();

        $i = 0;
        foreach ($items as $item) {

            $entry = new \stdClass();
            $entry->name = $item->name;

            $totalHours = $item->timeTrackings->sum(fn($t
            ) => \Carbon\Carbon::parse($t->started_at)->diffInMinutes(\Carbon\Carbon::parse($t->ended_at))
            );

            $entry->hours = ceil($totalHours * 4) / 4;
            $entry->user = $item->assignedUsers->isEmpty() ? '' : $item->assignedUsers[0]->name;
            $entries[] = $entry;
            $i++;
        }

        // Fetch users list for admin
        $users = collect();

        return view('admin.epic', [
            'trackings' => $entries,
            'users' => $users
        ]);
    }
}
