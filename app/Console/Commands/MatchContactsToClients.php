<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Console\Command;

class MatchContactsToClients extends Command
{
    protected $signature = 'app:match-contacts-to-clients';
    protected $description = 'Assign client_id to contacts based on company-name match with clients.name';

    public function handle()
    {
        $updated = 0;
        $skipped = 0;

        $this->info('🔍 Matching contacts to clients by name...');

        $contacts = Contact::all();

        foreach ($contacts as $contact) {
            if ($contact->client_id) {
                $this->line("⏩ Already linked: {$contact->name}");
                $skipped++;
                continue;
            }

            $client = Client::whereRaw('LOWER(name) = ?', [strtolower($contact->company)])->first();

            if ($client) {
                $contact->client_id = $client->id;
                $contact->save();
                $this->line("✅ Linked '{$contact->name}' → Client '{$client->name}'");
                $updated++;
            } else {
                $this->warn("❌ No match for '{$contact->name}' (company: '{$contact->company}')");
            }
        }

        $this->newLine();
        $this->info("🎯 Done! {$updated} contacts updated.");
        $this->info("⏭ {$skipped} contacts already had client_id.");
    }
}
