<?php

namespace App\Console\Commands;

use App\Models\BoardWebhook;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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
        $this->mondayToken = env('MONDAY_API_TOKEN');
        $this->webhookCallbackUrl = env('MONDAY_WEBHOOK_CALLBACK');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = microtime(true);
        $this->info("Starting board webhook sync...");
        $existingBoardIds = BoardWebhook::distinct()->pluck('board_id')->toArray();

        $workspaceId = env('MONDAY_WORKSPACE_ID');
        $limit = 500;
        $page = 1;
        $totalCreated = 0;

        while (true) {
            $query = <<<GRAPHQL
        query GetBoards(\$workspaceIds: [ID!]!, \$limit: Int!, \$page: Int!) {
            boards(workspace_ids: \$workspaceIds, limit: \$limit, page: \$page) {
                id
                name
            }
        }
        GRAPHQL;

            $response = Http::withHeaders([
                'Authorization' => env('MONDAY_API_TOKEN'),
            ])->post('https://api.monday.com/v2', [
                'query' => $query,
                'variables' => [
                    'workspaceIds' => [$workspaceId],
                    'limit' => $limit,
                    'page' => $page,
                ],
            ]);

            $boards = data_get($response->json(), 'data.boards', []);

            if (empty($boards)) {
                break;
            }

            foreach ($boards as $board) {
                if (str_starts_with($board['name'], 'Subitems of')) {
                    continue;
                }
                if (in_array($board['id'], $existingBoardIds)) {
                    continue;
                }

                // Check if any webhook exists for this board
                $hasWebhook = BoardWebhook::where('board_id', $board['id'])->exists();

                if ($hasWebhook) {
                    continue;
                }

                // No webhook found, create all events
                foreach ($this->events as $event) {
                    $webhookId = $this->createWebhook($board['id'], $event);

                    if ($webhookId) {
                        BoardWebhook::create([
                            'board_id' => $board['id'],
                            'event' => $event,
                            'webhook_id' => $webhookId,
                        ]);
                        $this->info("✅ Created webhook for '{$event}' on board '{$board['name']}'");
                        $totalCreated++;
                    } else {
                        $this->warn("❌ Failed to create webhook for '{$event}' on board {$board['id']}");
                    }
                }
            }

            if (count($boards) < $limit) {
                break;
            }

            $page++;
        }

        $duration = round(microtime(true) - $start, 2);
        $this->info("🔁 Sync completed in {$duration} seconds. Total new webhooks created: $totalCreated");
        return Command::SUCCESS;
    }


    protected function createWebhook(string $boardId, string $event): ?string
    {
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
}
