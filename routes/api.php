<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TimeTrackingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\InvoicingController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\WebhookController;
use App\Models\Invoice;
use Illuminate\Http\Request;
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
    Route::get('/currencies', [CurrencyController::class, 'index']);
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{id}', [ClientController::class, 'show']);
    Route::get('/clients/{client}/contacts', [ContactController::class, 'indexByClient']);
    Route::get('/clients/{client}/projects', [ProjectController::class, 'indexByClient']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::get('/projects/{project}/groups', [GroupController::class, 'index']);
    Route::get('/projects/{project}/groups/{id}', [GroupController::class, 'show']);
    Route::get('/projects/{project}/groups/{id}/tasks', [TaskController::class, 'indexByGroup']);
    Route::get('/projects/{project}/tasks', [TaskController::class, 'indexByProject']);
    Route::get('/projects/{project}/tasks/{id}', [TaskController::class, 'show']);
    Route::get('/projects/{project}/groups/{group}/tasks/{task}', [TaskController::class, 'showFromGroup']);
    Route::get('/projects/{project}/tasks/{id}/timetrackings', [TimeTrackingController::class, 'indexByTask']);

    Route::get('/contacts', [ContactController::class, 'index']);

    Route::get('/tasks', [TaskController::class, 'index']);

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
    Route::post('/tasks', [InvoicingController::class, 'tasks']);
    Route::post('/invoices', [InvoicingController::class, 'store']);
    Route::get('/invoices', [InvoicingController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoicingController::class, 'show']);
    Route::delete('/invoices/{id}', [InvoicingController::class, 'destroy']);
});

// ---------------Public routes--------------------
//google login
Route::post('/auth/google-login', [GoogleAuthController::class, 'login']);
//Slack answer processing
Route::post('slack/office-answer', [OfficeController::class, 'slackAnswer']);

Route::post('/webhook_monday/{event}', [WebhookController::class, 'handle']);
