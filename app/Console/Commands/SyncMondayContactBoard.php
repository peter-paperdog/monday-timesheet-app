<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\MondayBoard;
use App\Models\MondayGroup;
use App\Models\MondayItem;
use App\Models\MondayTimeTracking;
use App\Models\SyncStatus;
use App\Services\MondayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncMondayContactBoard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:monday-contact-board';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Monday.com boards with the database';

    public function __construct(private MondayService $mondayService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $this->info('Fetching contact board from Monday.com...');

        $items = $this->mondayService->getContactItems('8451006561');

        if (empty($items)) {
            $this->warn("No item data found for contact board '' (#8451006561)");
        } else {
            $this->info("Found ".count($items)." task items for board contact' (#8451006561)");
        }

        foreach ($items as $itemData) {
            $MondayContactItem = Contact::updateOrCreate(
                ['id' => $itemData['id']],
                [
                    'name' => $itemData['name'],
                    'type' => $itemData['column_values'][0]['text'],
                    'company' => $itemData['column_values'][1]['text'],
                    'title' => $itemData['column_values'][2]['text'],
                    'email' => $itemData['column_values'][3]['text'],
                    'mobile' => $itemData['column_values'][4]['text'],
                    'work_phone' => $itemData['column_values'][5]['text'],
                    'address' => $itemData['column_values'][6]['text']
                ]
            );
            $MondayContactItem->touch();
        }


        $this->info("Successfully updated board contact' (#8451006561)".PHP_EOL.PHP_EOL);

    }
}
