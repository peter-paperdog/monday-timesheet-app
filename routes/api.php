<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\InvoicingController;
use App\Http\Controllers\OfficeController;
use App\Models\User;
use App\Services\GoogleSheetsService;
use App\Services\SlackService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::options('{any}', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', 'https://invoicing.paperdog.com')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->header('Access-Control-Allow-Credentials', 'true');
})->where('any', '.*');


//---protected routes, but do not increase token lifetime---
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/auth/status', function (Request $request) {
        return response()->json([], 204);
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'apiLogout']);
});

//---------------protected routes, increase token lifetime---------------
Route::middleware(['auth:sanctum', 'refresh-token'])->group(function () {
    //return with logged-in user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    //return with dropdown inits
    Route::get('/init', [InvoicingController::class, 'init']);

    Route::post('/tasks', [InvoicingController::class, 'tasks']);
});
Route::get('/invoice/generate', [InvoicingController::class, 'generate']);

// ---------------Public routes--------------------
//google login
Route::post('/auth/google-login', [GoogleAuthController::class, 'login']);
//Slack answer processing
Route::post('slack/office-answer', [OfficeController::class, 'slackAnswer']);

