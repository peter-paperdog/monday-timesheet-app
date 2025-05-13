<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\SzamlazzController;
use App\Models\User;
use App\Services\GoogleSheetsService;
use App\Services\MondayService;
use App\Services\SlackService;
use Carbon\Carbon;
use Google\Client;

use Google\Service\Sheets;
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
    Route::get('/init', function (Request $request, MondayService $mondayService) {
        $data = $mondayService->getFolders();
        $clients = $data->clients;
        $projects = $data->projects;
        $folders = $data->folders;

        return response()->json([
            "clients" => $data->clients,
            "projects" => $data->projects,
            "boards" => $data->folders
        ]);
    });

    Route::get('/tasks', function (Request $request, MondayService $mondayService) {
    });
});

// ---------------Public routes--------------------
//google login
Route::post('/auth/google-login', [GoogleAuthController::class, 'login']);
//slack answer processing
Route::post('slack/office-answer', function (Request $request) {

    $payload = json_decode($request->input('payload'), true);

    if (!isset($payload['actions'][0])) {
        return response()->json(['error' => 'Invalid interaction data'], 400);
    }

    $selectedOption = str_replace('_', ' ', $payload['actions'][0]['value']);
    $responseUrl = $payload['response_url'];
    $user = User::where('slack_id', $payload['user']['id'])->first();

    Log::info("{$user->name} selected: {$selectedOption}");

    $tsDate = Carbon::createFromTimestamp((int) $payload['message']['ts'])->toDateString();

    $user->schedules()
        ->where('date', $tsDate)
        ->update(['status' => $selectedOption]);

    $googlesheetservice = app(GoogleSheetsService::class);

    $googlesheetservice->updateHUOfficeSchedule($user->email, $tsDate, $selectedOption);

    $slackService = new SlackService();
    $slackService->updateSlackMessage($responseUrl, $selectedOption);

    return response()->json(['success' => true]);
});
