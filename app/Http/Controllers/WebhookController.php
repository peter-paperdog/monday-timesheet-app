<?php

namespace App\Http\Controllers;

use App\Models\MondayTimeTracking;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\UserBoard;
use App\Services\MondayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    private $_username = 'unknown';

    public function __construct(private MondayService $mondayService)
    {

    }

    private function webhookChallengeResponse(Request $request)
    {
        Log::channel('webhook')->debug(__METHOD__);
        if ($request->has('challenge')) {
            return response()->json(['challenge' => $request->input('challenge')]);
        }
        return response()->json(['status' => 'no challenge'], 204);
    }

    public function handle(Request $request, string $event)
    {
        $eventData = $request->input('event');
        Log::channel('webhook')->debug(json_encode($eventData, JSON_PRETTY_PRINT));

        if (isset($eventData['userId'])) {
            static $userCache = [];
            $userId = $eventData['userId'];

            if (!isset($userCache[$userId])) {
                $userCache[$userId] = User::find($userId);
            }

            $this->_username = $userCache[$userId]?->name ?? 'unknown';
        }
        Log::channel('webhook')->info("Webhook received by {$this->_username} for event: {$event}");
        $method = 'handle' . str_replace(' ', '', ucwords(str_replace('_', ' ', $event)));

        if (method_exists($this, $method)) {
            Log::channel('webhook')->info("Calling handler method: {$method}");
            return $this->{$method}($request);
        }

        return $this->webhookChallengeResponse($request);
    }

    private function handleProjectNumberBoard(Request $request)
    {
        Log::channel('webhook')->debug(__METHOD__ . " by {$this->_username} ");
        $eventData = $request->input('event');
        Log::channel('webhook')->info("New project created: {$eventData['pulseName']}.");

        $projects = $this->mondayService->getProjectBoard();
        $project_nr = sizeof($projects);

        Log::channel('webhook')->info("New project number will be: {$project_nr}.");

        $response = $this->mondayService->setProjectNumber($eventData['pulseId'], $project_nr);
        Log::channel('webhook')->info(var_export($response, true));

        return $this->webhookChallengeResponse($request);
    }

    private function handleItemDeleted(Request $request)
    {
        Log::channel('webhook')->debug(__METHOD__ . " by {$this->_username} ");
        $eventData = $request->input('event');
        $itemId = $eventData['itemId'];

        Log::channel('webhook')->info('Item deleted event received', ['itemId' => $itemId]);

        $task = Task::find($itemId);

        if ($task) {
            $task->delete();
            Log::channel('webhook')->info("Task with ID {$itemId} deleted.");
        } else {
            Log::channel('webhook')->info("Task with ID {$itemId} not found.");
        }

        return $this->webhookChallengeResponse($request);
    }

    private function handleCreateItem(Request $request)
    {
        Log::channel('webhook')->debug(__METHOD__ . " by {$this->_username} ");
        $eventData = $request->input('event');

        if ($eventData['boardId'] === 9370542454) {
            return $this->handleProjectNumberBoard($request);
        }

        $existingTask = Task::find($eventData['pulseId']);
        if ($existingTask) {
            Log::channel('webhook')->warning("Task already exists", ['id' => $existingTask->id]);
            return $this->webhookChallengeResponse($request);
        }

        $project = Project::where('time_board_id', $eventData['boardId'])->first();
        if ($project) {
            $taskable = $project;
        } else {
            $userBoard = UserBoard::find($eventData['boardId']);
            if ($userBoard) {
                $taskable = $userBoard;
            } else {
                Log::channel('webhook')->warning("No matching taskable entity found for board ID {$eventData['boardId']} (item_id: {$eventData['pulseId']})");
                return $this->webhookChallengeResponse($request);
            }
        }

        $task = new Task([
            'id' => $eventData['pulseId'],
            'name' => $eventData['pulseName'],
            'group_id' => $eventData['groupId']
        ]);
        $task->taskable()->associate($taskable);
        $task->save();

        Log::channel('webhook')->info("New task created: ".$eventData['pulseName']." ({$task->id})");
        return $this->webhookChallengeResponse($request);
    }

    private function handleCreateProjectButton(Request $request)
    {
        Log::channel('webhook')->debug(__METHOD__ . " by {$this->_username} ");
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
                Log::channel('webhook')->warning("Board not found on attempt {$attempts}, sleeping 1 second...");
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
        Log::channel('webhook')->debug(__METHOD__ . " by {$this->_username} ");
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
