<?php

use App\Models\User;
use App\Services\GoogleSheetsService;
use App\Services\SlackService;
use Carbon\Carbon;
use Google\Client;

use Google\Service\Sheets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


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
