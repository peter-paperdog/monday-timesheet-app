<?php

namespace App\Http\Controllers;

use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MondayController extends Controller
{
    public function recordtime(Request $request)
    {
        $start_times = $request->input('start_time');
        $end_times = $request->input('end_time');
        $date = $request->input('date');

        return view('admin.recorded', [
            'start_times' => $start_times,
            'end_times' => $end_times,
            'date' => $date
        ]);
    }
}
