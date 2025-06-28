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

    public function __construct(private MondayService $mondayService)
    {

    }

    private function webhookChallengeResponse(Request $request)
    {
        Log::channel('webhook')->info(__METHOD__);
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
            Log::channel('webhook')->info("Calling handler method: {$method}");
            return $this->{$method}($request);
        }

        Log::channel('webhook')->warning("No handler method found for event: {$event}");
        return $this->webhookChallengeResponse($request);
    }

    private function handleProjectNumberBoard(Request $request)
    {
        Log::channel('webhook')->info(__METHOD__);
        $eventData = $request->input('event');
        Log::channel('webhook')->info("New project created: {$eventData['pulseName']}.");

        $board = $this->mondayService->getBoard($eventData['boardId']);
        $projects = $board['groups'][0]['items_page']['items'];
        Log::channel('webhook')->info(var_export($projects, true));

        $project_nr = sizeof($projects) + 1;

        Log::channel('webhook')->info("New project number will be: {$project_nr}.");

        return $this->webhookChallengeResponse($request);
    }

    private function handleCreateItem(Request $request)
    {
        Log::channel('webhook')->info(__METHOD__);
        $eventData = $request->input('event');

        if ($eventData['boardId'] === 9370542454) {
            return $this->handleProjectNumberBoard($request);
        }

        return $this->webhookChallengeResponse($request);
    }

    private function handleChangeColumnValue(Request $request)
    {
        Log::channel('webhook')->info(__METHOD__);
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

        return $this->webhookChallengeResponse($request);
    }
}
