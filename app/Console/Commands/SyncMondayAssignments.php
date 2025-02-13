<?php

namespace App\Console\Commands;

use App\Models\MondayAssignment;
use App\Models\SyncStatus;
use App\Services\MondayService;
use Illuminate\Console\Command;

class SyncMondayAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-assignments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com assignments with the database';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Fetching assignments from Monday.com...");

        // Fetch tasks with assigned users where `is_done === false`
        $assignments = $this->mondayService->getAssignments();

        $syncedAssignments = [];

        foreach ($assignments as $assignment) {
            foreach ($assignment['items_page']['items'] as $item) {
                $itemId = $item['id'];

                // Check if the item is marked as done
                $isDone = false;
                foreach ($item['column_values'] as $column) {
                    if (isset($column['is_done'])) {
                        $isDone = $column['is_done'];
                        break;
                    }
                }

                // Skip completed tasks
                if ($isDone) {
                    continue;
                }

                // Process assigned users
                foreach ($item['column_values'] as $column) {
                    if (!empty($column['persons_and_teams'])) {
                        foreach ($column['persons_and_teams'] as $person) {
                            $userId = $person['id'];
                            $syncedAssignments[] = [
                                'item_id' => $itemId,
                                'user_id' => $userId,
                            ];
                        }
                    }
                }
            }
        }

        // Sync database with Monday.com data
        $this->syncAssignments($syncedAssignments);
        $this->info("Successfully fetched assignments.");
        SyncStatus::recordSync('monday-assignments'); // Record sync time

    }

    private function syncAssignments(array $syncedAssignments)
    {
        // Get existing assignments
        $existingAssignments = MondayAssignment::all()->toArray();

        // Convert to key-value pairs for quick comparison
        $existingAssignmentsSet = array_map(fn($a) => ['item_id' => $a['item_id'], 'user_id' => $a['user_id']], $existingAssignments);

        // Find assignments to add
        $assignmentsToAdd = array_filter($syncedAssignments, function ($assignment) use ($existingAssignmentsSet) {
            return !in_array($assignment, $existingAssignmentsSet);
        });

        // Find assignments to delete
        $assignmentsToDelete = array_filter($existingAssignmentsSet, function ($assignment) use ($syncedAssignments) {
            return !in_array($assignment, $syncedAssignments);
        });

        // Insert new assignments
        if (!empty($assignmentsToAdd)) {
            MondayAssignment::insert($assignmentsToAdd);
            $this->info(count($assignmentsToAdd) . " new assignments added.");
        }

        // Delete outdated assignments
        if (!empty($assignmentsToDelete)) {
            foreach ($assignmentsToDelete as $assignment) {
                MondayAssignment::where('item_id', $assignment['item_id'])
                    ->where('user_id', $assignment['user_id'])
                    ->delete();
            }
            $this->info(count($assignmentsToDelete) . " outdated assignments removed.");
        }
    }
}
