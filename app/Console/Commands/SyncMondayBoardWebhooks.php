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
        $this->info("Starting webhook sync...");
        $workspaceId = env('MONDAY_WORKSPACE_ID');
        $limit = 10;
        $page = 1;

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

            $data = $response->json();
            $boards = data_get($data, 'data.boards', []);

            if (empty($boards)) {
                break;
            }

            foreach ($boards as $board) {
                if (str_starts_with($board['name'], 'Subitems of')) {
                    continue;
                }
                foreach ($this->events as $event) {
                    $exists = BoardWebhook::where('board_id', $board['id'])->where('event', $event)->exists();

                    if (!$exists) {
                        $webhookId = $this->createWebhook($board['id'], $event);

                        if ($webhookId) {
                            BoardWebhook::create([
                                'board_id' => $board['id'],
                                'event' => $event,
                                'webhook_id' => $webhookId,
                            ]);

                            $this->info("Webhook for '{$event}' created on board '{$board['name']}' ({$board['id']})");
                        } else {
                            $this->warn("Failed to create webhook for event '{$event}' on board {$board['id']}");
                        }
                    }
                }
            }

            if (count($boards) < $limit) {
                break;
            }

            $page++;
        }

        $this->info("All webhooks synchronized.");
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
