<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SociaLoginController;
use App\Http\Controllers\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    Route::get('/dashboard', [TimesheetController::class, 'viewUserSheet'])->name('sheets.search');
});

Route::get('/login/google', [SociaLoginController::class, 'redirectToProvider']);
Route::get('/login/google/callback', [SociaLoginController::class, 'handleProviderCallback']);

require __DIR__.'/auth.php';
