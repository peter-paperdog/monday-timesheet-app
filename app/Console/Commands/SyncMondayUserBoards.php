<?php

namespace App\Console\Commands;

use App\Models\SyncStatus;
use App\Models\User;
use App\Models\UserBoard;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class SyncMondayUserBoards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-user-boards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com user boards with the database';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching user boards from Monday.com...');
        $user_boards = $this->mondayService->getUserBoards();

        foreach ($user_boards as $user_board) {
            $boardName = $user_board['name'];
            $boardId = $user_board['id'];

            $this->info("Processing board {$boardName} ({$boardId}).");

            $guessedName = $this->extractFirstNameFromBoardName($boardName);
            $guessedEmail = strtolower($guessedName) . '@paperdog.com';

            $this->info("Guessed email: {$guessedEmail}.");
            $user = User::where('email', $guessedEmail)->first();

            $userBoard = UserBoard::firstOrNew(['id' => $boardId]);
            $userBoard->name = $boardName;

            if (is_null($userBoard->user_id) && $user) {
                $userBoard->user_id = $user->id;
                $this->info("Linked user {$user->email} to board ID {$boardId}");
            } elseif (is_null($userBoard->user_id)) {
                $this->warn("No matching user for guessed email: {$guessedEmail} (board '{$boardName}')");
            }

            $userBoard->save();

        }

        $this->info('User boards synchronization complete.');
    }

    protected function extractFirstNameFromBoardName(string $boardName): string
    {
        // Pl. "Admin | Kalani." → "Kalani"
        $parts = explode('|', $boardName);
        $namePart = trim(end($parts));

        return preg_replace('/[^a-zA-Z]/', '', explode(' ', $namePart)[0]);
    }
}
