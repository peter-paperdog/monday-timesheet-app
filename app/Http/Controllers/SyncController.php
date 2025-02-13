<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SyncController extends Controller
{

    public function syncMondayAssignments(Request $request)
    {
        // Run sync commands
        Artisan::call('sync:monday-assignments');

        // Reload the page
        return redirect()->back();
    }
    public function syncMondayBoards(Request $request)
    {
        // Run sync commands
        Artisan::call('sync:monday-boards');

        // Reload the page
        return redirect()->back();
    }
}
