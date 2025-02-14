<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\MondayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SociaLoginController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [TimesheetController::class, 'dashboard'])->name('dashboard');
    Route::get('/timesheets', [TimesheetController::class, 'timesheets'])->name('timesheets');

    Route::post('download', [TimesheetController::class, 'downloadUserSheet'])
        ->name('download.sheet');
    Route::post('downloadcsv', [TimesheetController::class, 'downloadUserSheetCsv'])
        ->name('download.sheetcsv');

    Route::get('/sync-assignments', [SyncController::class, 'syncMondayAssignments'])->name('sync.assignments');
    Route::get('/sync-boards', [SyncController::class, 'syncMondayBoards'])->name('sync.boards');

    Route::get('/download/timesheet/pdf/{userId}/{weekStartDate}', [TimesheetController::class, 'timesheetPDF'])->name('timesheet.download.PDF');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/download/timesheets/pdf/{weekStartDate}', [TimesheetController::class, 'timesheetsPDF'])->name('timesheet.download.PDFs');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin/record', [AdminController::class, 'record'])->name('admin.record');
    Route::post('/admin/monday/record', [MondayController::class, 'recordtime'])->name('admin.monday.recordtime');
});

Route::get('/login/google', [SociaLoginController::class, 'redirectToProvider'])->name('google.login');
Route::get('/login/google/callback', [SociaLoginController::class, 'handleProviderCallback']);

require __DIR__.'/auth.php';
