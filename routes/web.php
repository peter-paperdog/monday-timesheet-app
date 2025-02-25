<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SociaLoginController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TimesheetController;
use App\Models\MondayTimeTracking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [TimesheetController::class, 'dashboard'])->name('dashboard');
    Route::get('/timesheets', [TimesheetController::class, 'timesheets'])->name('timesheets');
    Route::get('/timesheets/calendar', [TimesheetController::class, 'calendar'])->name('timesheets.calendar');
    Route::get('/office-schedule', [OfficeController::class, 'schedule'])->name('office-schedule');

    Route::post('download', [TimesheetController::class, 'downloadUserSheet'])
        ->name('download.sheet');
    Route::post('downloadcsv', [TimesheetController::class, 'downloadUserSheetCsv'])
        ->name('download.sheetcsv');

    Route::get('/sync-assignments', [SyncController::class, 'syncMondayAssignments'])->name('sync.assignments');
    Route::get('/sync-boards', [SyncController::class, 'syncMondayBoards'])->name('sync.boards');

    Route::get('/download/timesheet/pdf/{userId}/{weekStartDate}', [TimesheetController::class, 'timesheetPDF'])->name('timesheet.download.PDF');



    Route::get('/timesheet-events', function (Request $request) {

        // Convert dates
        $startDate = Carbon::parse($request->query('start', now()->startOfWeek()))->setTimezone('UTC');
        $endDate = Carbon::parse($request->query('end', now()->endOfWeek()))->setTimezone('UTC');
        $userId = Auth::id(); // Ensure Auth facade is used

        // Get events
        $events = MondayTimeTracking::where('user_id', $userId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->with('item') // Ensure relation is loaded
            ->get()
            ->map(function ($entry) {
                return [
                    'title' => optional($entry->item)->name ?? 'Unnamed Task',
                    'start' => $entry->started_at->toIso8601String(),
                    'end' => optional($entry->ended_at)->toIso8601String() ?? now()->toIso8601String(),
                    'color' => '#007bff',
                ];
            });

        // Debugging response
        return response()->json($events);
    });
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/download/timesheets/pdf/{weekStartDate}', [TimesheetController::class, 'timesheetsPDF'])->name('timesheet.download.PDFs');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
});

Route::get('/login/google', [SociaLoginController::class, 'redirectToProvider'])->name('google.login');
Route::get('/login/google/callback', [SociaLoginController::class, 'handleProviderCallback']);

require __DIR__ . '/auth.php';
