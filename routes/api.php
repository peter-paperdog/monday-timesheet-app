<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\GoogleAuthController;
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


// ---------------Publikus routes--------------------
Route::post('/auth/google-login', [GoogleAuthController::class, 'login']);

//---------------olyan műveletek,amik nem frissítik a token élettartamát, de bejelentkezést követelnek---------------
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/auth/status', function (Request $request) {
        return response()->json([], 204);
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'apiLogout']);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/init', function (Request $request, MondayService $mondayService) {
    $data = $mondayService->getFolders();
    $clients = $data->clients;
    $projects = $data->projects;
    $folders = $data->folders;

    return response()->json([
        "clients" => $data->clients,
        "projects" => $data->projects,
        "folders" => $data->folders
    ]);
});


Route::post('slack/office-answer', function (Request $request) {

    $payload = json_decode($request->input('payload'), true);

    if (!isset($payload['actions'][0])) {
        return response()->json(['error' => 'Invalid interaction data'], 400);
    }

    $selectedOption = str_replace('_', ' ', $payload['actions'][0]['value']);
    $responseUrl = $payload['response_url'];
    $user = User::where('slack_id', $payload['user']['id'])->first();

    Log::info("{$user->name} selected: {$selectedOption}");

    $tsDate = Carbon::createFromTimestamp((int)$payload['message']['ts'])->toDateString();

    $user->schedules()
        ->where('date', $tsDate)
        ->update(['status' => $selectedOption]);

    $googlesheetservice = app(GoogleSheetsService::class);

    $googlesheetservice->updateHUOfficeSchedule($user->email, $tsDate, $selectedOption);

    $slackService = new SlackService();
    $slackService->updateSlackMessage($responseUrl, $selectedOption);

    return response()->json(['success' => true]);
});
