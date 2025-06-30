<?php

namespace App\Http\Controllers;

use App\Models\MondayTimeTracking;
use App\Models\Task;
use App\Models\User;
use App\Services\MondayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $projects = $this->mondayService->getProjectBoard();
        $project_nr = sizeof($projects);

        Log::channel('webhook')->info("New project number will be: {$project_nr}.");

        $response = $this->mondayService->setProjectNumber($eventData['pulseId'], $project_nr);
        Log::channel('webhook')->info(var_export($response, true));

        return $this->webhookChallengeResponse($request);
    }

    private function handleCreateItem(Request $request)
    {
        Log::channel('webhook')->info(__METHOD__);
        $eventData = $request->input('event');

        if ($eventData['boardId'] === 9370542454) {
            return $this->handleProjectNumberBoard($request);
        }

        $project_id = $this->mondayService->getProjectIdForItem($eventData['pulseId']);

        // Create new task from webhook data
        $task = new Task([
            'id' => $eventData['pulseId'],
            'project_id' => $project_id,
            'name' => $eventData['pulseName'],
            'group_id' => $eventData['groupId']
        ]);
        $task->save();

        Log::channel('webhook')->info("Created Task from webhook", ['task_id' => $task->id]);

        return $this->webhookChallengeResponse($request);
    }

    private function handleCreateProjectButton(Request $request)
    {
        Log::channel('webhook')->info(__METHOD__);
        /** @var \App\Services\MondayService $mondayService */
        $mondayService = app(MondayService::class);

        $projectName = $mondayService->getProjectBoardLastItemProjectName();

        $found = false;
        $attempts = 0;
        $maxAttempts = 30;

        while (!$found && $attempts < $maxAttempts) {
            $attempts++;
            Log::channel('webhook')->info("Attempt {$attempts} of {$maxAttempts}: searching for '[Project number & name]' board...");

            $boards = $mondayService->getBoardsCreatedWithNewProjectButtonPress();

            $folderId = collect($boards)
                ->first(fn($board) => !is_null($board['board_folder_id']))['board_folder_id'];

            foreach ($boards as $board) {
                $newName = $board['name'];
                if (Str::contains($newName, 'PDYY_XXXX')) {
                    $newName = str_replace('PDYY_XXXX', $projectName, $newName);
                }

                if (Str::contains($newName, '[Project number & name]')) {
                    $newName = str_replace('[Project number & name]', $projectName, $newName);
                }

                if ($newName !== $board['name']) {
                    $mondayService->setBoardName($board['id'], $newName);
                    Log::channel('webhook')->info("☑️ Found board on attempt {$attempts}. Renamed '{$board['name']}' to '{$newName}' (board_id: {$board['id']})");
                    $found = true;
                }
            }

            if (!$found) {
                Log::channel('webhook')->info("Board not found on attempt {$attempts}, sleeping 1 second...");
                sleep(1);
            } else {
                Log::channel('webhook')->info("Rename folder to '{$projectName}' (folder_id: {$folderId})");
                $mondayService->updateFolder($folderId, $projectName);
                Log::channel('webhook')->info("DONE ✅");
            }
        }
    }

    private function handleChangeColumnValue(Request $request)
    {
        Log::channel('webhook')->info(__METHOD__);
        $eventData = $request->input('event');

        //create project button clicked
        if ($eventData["columnId"] === "button_mkrwhp23") {
            $this->handleCreateProjectButton($request);
        } else if (
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
