<?php

namespace App\Http\Controllers;

use App\Models\MondayTimeTracking;
use App\Models\User;
use App\Services\MondayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private function webhookChallengeResponse(Request $request){
        if ($request->has('challenge')) {
            return response()->json(['challenge' => $request->input('challenge')]);
        }
        return response()->json(['status' => 'no challenge'], 204);
    }
    public function handle(Request $request, string $event)
    {
        Log::channel('webhook')->info("Webhook received for event: {$event} \n" . json_encode($request->all(), JSON_PRETTY_PRINT));

        $method = 'handle' . str_replace(' ', '', ucwords(str_replace('_', ' ', $event)));

        if (method_exists($this, $method)) {
            $this->{$method}($request);
        } else {
            Log::channel('webhook')->warning("No handler found for event: {$event}");
        }

        $this->webhookChallengeResponse();
    }
    private function handleProjectNumberBoard(Request $request, string $eventData){
        $eventData->boardId;
        $eventData->pulseId;
        $eventData->pulseName;
        Log::channel('webhook')->info("New project created: {$eventData->pulseName}.");
        $this->webhookChallengeResponse();
    }

    private function handleCreateItem(Request $request){
        $eventData = $request->input('event');

        if($eventData->boardId === 9370542454){
            $this->handleProjectNumberBoard($eventData);
        }
    }

    private function handleChangeColumnValue(Request $request)
    {
        $eventData = $request->input('event');

        if (
            isset($eventData['columnType']) &&
            $eventData['columnType'] === 'duration' &&
            isset($eventData['pulseId'])
        ) {
            /** @var \App\Services\MondayService $mondayService */
            $mondayService = app(MondayService::class);

            $itemId = $eventData['pulseId'];
            $trackingItems = $mondayService->getTimeTrackingItemsForItem($itemId);

            $newIds = array_column($trackingItems, 'id');
            $existingIds = MondayTimeTracking::where('item_id', $itemId)->pluck('id')->toArray();
            $toDelete = array_diff($existingIds, $newIds);

            if (!empty($toDelete)) {
                MondayTimeTracking::whereIn('id', $toDelete)->delete();
                Log::info("Deleted " . count($toDelete) . " outdated time tracking records for item {$itemId}");
            }

            foreach ($trackingItems as $trackingData) {
                MondayTimeTracking::updateOrCreate(
                    ['id' => $trackingData['id']],
                    [
                        'item_id' => $trackingData['item_id'],
                        'user_id' => $trackingData['started_user_id'],
                        'started_at' => Carbon::parse($trackingData['started_at'])->toDateTimeString(),
                        'ended_at' => !empty($trackingData['ended_at']) ? Carbon::parse($trackingData['ended_at'])->toDateTimeString() : null,
                    ]
                );
            }

            $userName = User::find($eventData['userId'])?->name ?? 'Unknown';
            Log::info("Time updated: {$eventData['pulseName']} (ID: {$eventData['pulseId']}), by {$userName} ({$eventData['userId']})");
        }
    }
}
