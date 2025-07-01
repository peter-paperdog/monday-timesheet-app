<?php

namespace App\Console\Commands;

use App\Models\BoardWebhook;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncMondayBoardWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-board-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync board webhooks for all boards in the workspace';

    protected array $events = [
        "create_item",
        "change_name",
        "item_archived",
        "item_deleted",
        "item_moved_to_any_group",
        "item_restored",
        "change_column_value",
        "change_status_column_value",
        "create_column",
    ];

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
        $this->webhookCallbackUrl = env('MONDAY_WEBHOOK_CALLBACK');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting board webhook sync...");

        $boards = $this->mondayService->getBoards();

        foreach ($boards as $board) {
            $boardId = $board['id'];
            $boardType = $board['type'];
            $boardName = $board['name'];

            // Skip subitem boards
            if ($boardType === "sub_items_board") {
                continue;
            }

            $webhooks = $this->mondayService->getWebhooksForBoard($boardId);
            $existingEvents = [];

            // Count occurrences per event
            foreach ($webhooks as $webhook) {
                $event = $webhook['event'];
                $existingEvents[$event][] = $webhook['id'];
            }

            foreach ($this->events as $event) {
                $eventCount = count($existingEvents[$event] ?? []);

                if ($eventCount === 0) {
                    // Event not registered at all → create it
                    $this->createWebhook($boardId, $event);
                } elseif ($eventCount > 1) {
                    // Event registered more than once → keep one, delete the rest
                    $this->info("Found duplicate webhooks for '$event' on board $boardId ($boardName)");
                    $webhookIds = $existingEvents[$event];
                    // Keep the first, delete the rest
                    foreach (array_slice($webhookIds, 1) as $duplicateId) {
                        $this->deleteWebhook($duplicateId);
                    }
                }
            }
        }

        $this->info("Finished board webhook sync...");
        return Command::SUCCESS;
    }


    protected function createWebhook(string $boardId, string $event): ?string
    {
        $this->info("Creating webhook for board $boardId for event $event");
        $url = env('MONDAY_WEBHOOK_CALLBACK') . '/' . $event;

        $mutation = <<<GRAPHQL
        mutation {
            create_webhook(board_id: $boardId, url: "$url", event: $event) {
                id
            }
        }
        GRAPHQL;

        $response = Http::withHeaders([
            'Authorization' => env('MONDAY_API_TOKEN'),
        ])->post('https://api.monday.com/v2', ['query' => $mutation]);

        return data_get($response->json(), 'data.create_webhook.id');
    }

    protected function deleteWebhook(string $webhookId): ?string
    {
        $this->info("Deleting webhook $webhookId");
        $mutation = <<<GRAPHQL
        mutation {
            delete_webhook(id: $webhookId) {
                id
            }
        }
        GRAPHQL;

        $response = Http::withHeaders([
            'Authorization' => env('MONDAY_API_TOKEN'),
        ])->post('https://api.monday.com/v2', ['query' => $mutation]);

        return data_get($response->json(), 'data.delete_webhook.id');
    }
}
