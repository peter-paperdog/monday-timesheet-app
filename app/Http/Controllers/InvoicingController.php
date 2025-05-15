<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Item;
use App\Models\Project;
use App\Services\MondayService;
use Google\Client;
use Google\Service\AdMob\App;
use Google\Service\Drive\DriveFile;
use Google\Service\Sheets;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoicingController extends Controller
{
    public function __construct(private MondayService $mondayService)
    {

    }

    /**
     * Generate a Google Sheet Invoice for specific tasks
     *
     * @param Request $request The request containing the data needed for timesheet generation
     */
    public function generate(Request $request)
    {
        $envValue = trim(env('GOOGLE_SERVICE_ACCOUNT_JSON'));

        // Check if it's not set
        if ($envValue === null) {
            $this->logger->error("Error: GOOGLE_SERVICE_ACCOUNT_JSON is NOT set in .env file.");
            throw new \Exception("Error: GOOGLE_SERVICE_ACCOUNT_JSON is NOT set in .env file.");
        }

        // Check if it's empty
        if (trim($envValue) === '') {
            $this->logger->error("Error: GOOGLE_SERVICE_ACCOUNT_JSON is SET but EMPTY.");
            throw new \Exception("Error: GOOGLE_SERVICE_ACCOUNT_JSON is SET but EMPTY.");
        }

        // Build the full path
        $pathToCredentials = storage_path('app/' . $envValue);

        // Debugging: Log the resolved path
        if (!file_exists($pathToCredentials)) {
            $this->logger->error("Error: Google service account JSON file not found at: " . $pathToCredentials);
            throw new \Exception("Error: Google service account JSON file not found at: " . $pathToCredentials);
        }


        // Initialize Google Client
        $client = new Client();
        $client->setAuthConfig($pathToCredentials);
        $client->addScope(Sheets::SPREADSHEETS);
        $client->addScope(Drive::DRIVE);

        $driveService = new Drive($client);

        $originalSpreadsheetId = '1jY-ilQBaYRI8R_PFM2h99Nh0bXAccGA1syBypoI4pF4';

        try {
            $driveService->files->get($originalSpreadsheetId);
            return ('File access OK');
        } catch (\Exception $e) {
            Log::error('File access FAILED: ' . $e->getMessage());
        }



        $targetFolderId = '1CiJed7IMuGNfoFvCZtqRCBkNBKQyZVaT';

        // Create a copy of the file
        $copy = new DriveFile([
            'name' => 'Invoice Copy ' . date('Y-m-d H:i:s'),
            'parents' => [$targetFolderId]
        ]);

        try {
            $newFile = $driveService->files->copy($originalSpreadsheetId, $copy, [
                'supportsAllDrives' => true,
            ]);
            $newSpreadsheetId = $newFile->id;

            Log::info("Spreadsheet duplicated: https://docs.google.com/spreadsheets/d/{$newSpreadsheetId}/edit");

            return response()->json([
                'status' => 'success',
                'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$newSpreadsheetId}/edit"
            ]);
        } catch (\Google\Service\Exception $e) {
            Log::error('Google API error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
        // Get the new spreadsheet ID
        $newSpreadsheetId = $newFile->id;

        // Log it or return it
        $this->logger->info("Spreadsheet duplicated: https://docs.google.com/spreadsheets/d/$newSpreadsheetId/edit");
    }

    public function create(Request $request)
    {
        $clientId = $request->input('client');
        $projectId = $request->input('project');
        $boardIds = explode(',', $request->input('folders', ''));

        $clientName = $this->mondayService->getFoldername($clientId);

        if (!empty($projectId) && $projectId !== '-1') {
            $projectName = $this->mondayService->getFoldername($projectId);
        } else {
            $projectName = null;
        }

        $boards = [];

        foreach ($boardIds as $boardId) {
            $boards[] = $this->mondayService->getBoard($boardId);
        }

        return view('invoicing.create', compact('clientName', 'projectName', 'boards'));
    }

    public function index()
    {
        $data = $this->mondayService->getFolders();

        $clients = $data->clients;
        $projects = $data->projects;
        $folders = $data->folders;

        return view('invoicing.index', compact('clients', 'projects', 'folders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client.monday_id' => 'required|integer',
            'client.name' => 'required|string',
            'projects' => 'required|array',
            'projects.*.monday_id' => 'required|integer',
            'projects.*.name' => 'required|string',
            'items' => 'required|array',
            'items.*.monday_id' => 'required|integer',
            'items.*.description' => 'required|string',
            'items.*.type' => 'required|string',
            'items.*.quantity' => 'required|numeric',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric',
            'items.*.currency' => 'required|string',
            'items.*.project_monday_id' => 'required|integer',
        ]);

        $client = \App\Models\Client::updateOrCreate(
            ['monday_id' => $data['client']['monday_id']],
            ['name' => $data['client']['name']]
        );

        $invoice = Invoice::create([
            'client_id' => $client->id,
        ]);

        $projects = collect($data['projects'])->mapWithKeys(function ($projectData) use ($invoice) {
            $project = Project::updateOrCreate(
                ['monday_id' => $projectData['monday_id']],
                [
                    'name' => $projectData['name'],
                    'invoice_id' => $invoice->id,
                ]
            );
            return [$projectData['monday_id'] => $project->id];
        });

        foreach ($data['items'] as $itemData) {
            $item = Item::updateOrCreate(
                ['monday_id' => $itemData['monday_id']],
                [
                    'description' => $itemData['description'],
                    'type' => $itemData['type'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'project_id' => $projects[$itemData['project_monday_id']],
                ]
            );

            // hozzárendeljük a számlához (pivot tábla!)
            $invoice->items()->syncWithoutDetaching([$item->id]);
        }

        return response()->json([
            'message' => 'Invoice stored successfully.',
            'invoice_id' => $invoice->id
        ]);
    }

    public function init(): \Illuminate\Http\JsonResponse
    {
        $mondayService = new MondayService();
        $data = $mondayService->getFolders();

        return response()->json([
            "clients" => $data->clients,
            "projects" => $data->projects,
            "boards" => $data->boards
        ]);
    }

    public function tasks(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'board_ids' => 'required|array',
        ]);

        $data = array();
        $mondayService = new MondayService();

        foreach ($request->board_ids as $board_id) {
            $data[$board_id] = new \stdClass();
            $board_datas = $mondayService->getInvoiceItems($board_id);
            $data[$board_id]->name = $board_datas->name;
            $data[$board_id]->groups = $board_datas->data;
        }

        return response()->json(
            $data
        );
    }
}
