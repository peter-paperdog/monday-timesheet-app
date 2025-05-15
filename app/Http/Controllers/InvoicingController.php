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
    public function generateSheet(Request $request, Invoice $invoice)
    {
        try {
            $envValue = trim(env('GOOGLE_SERVICE_ACCOUNT_JSON'));

            if (empty($envValue)) {
                Log::error("GOOGLE_SERVICE_ACCOUNT_JSON is missing or empty.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Google service account is not configured.',
                ], 500);
            }

            $pathToCredentials = storage_path('app/' . $envValue);

            if (!file_exists($pathToCredentials)) {
                Log::error("Google service account file not found at: $pathToCredentials");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Google credentials file not found.',
                ], 500);
            }

            // Initialize Google client
            $client = new Client();
            $client->setAuthConfig($pathToCredentials);
            $client->addScope(Sheets::SPREADSHEETS);
            $client->addScope(Drive::DRIVE);

            $driveService = new Drive($client);

            $originalSpreadsheetId = '1jY-ilQBaYRI8R_PFM2h99Nh0bXAccGA1syBypoI4pF4';
            $targetFolderId = '1CiJed7IMuGNfoFvCZtqRCBkNBKQyZVaT';

            // Check access to the original file
            try {
                $driveService->files->get($originalSpreadsheetId, [
                    'supportsAllDrives' => true,
                ]);
            } catch (\Exception $e) {
                Log::error("Access to original spreadsheet failed: " . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot access original Google Sheet.',
                    'google_error' => $e->getMessage(), // 🔍 include this for debugging
                ], 500);
            }

            // Duplicate spreadsheet
            $copy = new DriveFile([
                'name' => 'Invoice #' . $invoice->id . ' – ' . now()->format('Y-m-d H:i:s'),
                'parents' => [$targetFolderId],
            ]);

            $newFile = $driveService->files->copy($originalSpreadsheetId, $copy, [
                'supportsAllDrives' => true,
            ]);

            $newSpreadsheetId = $newFile->id;
            $sheetUrl = "https://docs.google.com/spreadsheets/d/{$newSpreadsheetId}/edit";

            Log::info("Invoice spreadsheet generated: $sheetUrl");

            // Optional: save URL to invoice
            $invoice->sheet_url = $sheetUrl;
            $invoice->save();

            return $this->updateSheetFromInvoice_($invoice);
        } catch (\Google\Service\Exception $e) {
            Log::error("Google API error: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Google API error: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error("General error: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function updateSheetFromInvoice_(Invoice $invoice) : \Illuminate\Http\JsonResponse{

        if (empty($invoice->sheet_url)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No sheet URL found for this invoice.',
            ], 404);
        }

        // Extract spreadsheetId from URL
        preg_match('/\/d\/([a-zA-Z0-9-_]+)\//', $invoice->sheet_url, $matches);
        $spreadsheetId = $matches[1] ?? null;

        if (!$spreadsheetId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Google Sheet URL.',
            ], 400);
        }

        try {
            // Load credentials
            $envValue = trim(env('GOOGLE_SERVICE_ACCOUNT_JSON'));
            $pathToCredentials = storage_path('app/' . $envValue);

            if (empty($envValue) || !file_exists($pathToCredentials)) {
                throw new \Exception("Service account config missing.");
            }

            // Init Google Sheets client
            $client = new \Google\Client();
            $client->setAuthConfig($pathToCredentials);
            $client->addScope(\Google\Service\Sheets::SPREADSHEETS);
            $sheets = new \Google\Service\Sheets($client);

            // Prepare invoice data
            $invoice->load(['items', 'items.project']);

            $data = [
                ['desc 1', 'Service', 1, 2, 3],
                ['desc 2', 'Service', 4, 5, 6],
                ['desc 3', 'Expense', 7, 8, 9],
            ];

            $insertRow = 14;
            $rowCount = count($data);
            $sheetId = 0; // vagy lekérheted dinamikusan is
            $colMap = [
                0 => [0, 23],   // A:X   = 0-23
                1 => [24, 29],  // Y:AD  = 24-29
                2 => [30, 35],  // AE:AJ = 30-35
                3 => [36, 41],  // AK:AP = 36-41
                4 => [42, 47],  // AQ:AV = 42-47
            ];

            // Insert rows first
            $sheets->spreadsheets->batchUpdate($spreadsheetId, new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [
                    new \Google\Service\Sheets\Request([
                        'insertDimension' => [
                            'range' => [
                                'sheetId' => $sheetId,
                                'dimension' => 'ROWS',
                                'startIndex' => $insertRow - 1,
                                'endIndex' => $insertRow - 1 + $rowCount,
                            ],
                            'inheritFromBefore' => false,
                        ]
                    ])
                ]
            ]));

            // Prepare values for each row (padded with empty cells)
            $rows = [];

            foreach ($data as $i => $rowData) {
                $currentRow = $insertRow + $i;

                $row = array_fill(0, 48, '');
                $row[0] = $rowData[0];    // A
                $row[24] = $rowData[1];   // Y
                $row[30] = $rowData[2];   // AE
                $row[36] = $rowData[3];   // AK

                $row[42] = "=AK$currentRow * AE$currentRow";   // AQ
                $rows[] = $row;
            }

            // Write values
            $sheets->spreadsheets_values->update($spreadsheetId, 'A' . $insertRow, new \Google\Service\Sheets\ValueRange([
                'values' => $rows,
            ]), ['valueInputOption' => 'USER_ENTERED']);

// Add merge requests per row
            $mergeRequests = [];

            for ($i = 0; $i < $rowCount; $i++) {
                $rowIndex = $insertRow - 1 + $i; // 0-based
                foreach ($colMap as $block) {
                    $mergeRequests[] = new \Google\Service\Sheets\Request([
                        'mergeCells' => [
                            'range' => [
                                'sheetId' => $sheetId,
                                'startRowIndex' => $rowIndex,
                                'endRowIndex' => $rowIndex + 1,
                                'startColumnIndex' => $block[0],
                                'endColumnIndex' => $block[1] + 1,
                            ],
                            'mergeType' => 'MERGE_ALL',
                        ]
                    ]);
                }
            }

// Apply merges
            $sheets->spreadsheets->batchUpdate($spreadsheetId, new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => $mergeRequests
            ]));


            $formatRequests = [
                new \Google\Service\Sheets\Request([
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => $insertRow - 1,
                            'endRowIndex' => $insertRow - 1 + $rowCount,
                            'startColumnIndex' => 30, // AE
                            'endColumnIndex' => 36,   // AJ (exclusive)
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'numberFormat' => [
                                    'type' => 'NUMBER'
                                ],
                            ],
                        ],
                        'fields' => 'userEnteredFormat.numberFormat'
                    ]
                ])
            ];
            $sheets->spreadsheets->batchUpdate($spreadsheetId, new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => $formatRequests
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Sheet updated successfully.',
                'spreadsheet_url' => $invoice->sheet_url
            ]);
        } catch (\Exception $e) {
            Log::error("Sheet update failed: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Sheet update failed.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function updateSheetFromInvoice(Invoice $invoice): \Illuminate\Http\JsonResponse
    {
        return $this->updateSheetFromInvoice_($invoice);
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
        $invoices = Invoice::with([
            'client',
            'items',
            'items.project'
        ])->latest()->get();

        return response()->json([
            'invoices' => $invoices
        ]);
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
            $item = new Item([
                'monday_id' => $itemData['monday_id'],
                'description' => $itemData['description'],
                'type' => $itemData['type'],
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'],
                'unit_price' => $itemData['unit_price'],
                'currency' => $itemData['currency'],
                'project_id' => $projects[$itemData['project_monday_id']],
                'invoice_id' => $invoice->id,
            ]);

            $item->invoice_id = $invoice->id;
            $item->save();
        }

        return response()->json([
            'message' => 'Invoice stored successfully.',
            'invoice_id' => $invoice->id,
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

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'items.project']);

        return response()->json([
            'id' => $invoice->id,
            'client' => $invoice->client,
            'sheet_url' => $invoice->sheet_url,
            'items' => $invoice->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'type' => $item->type,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'currency' => $item->currency,
                    'project_name' => optional($item->project)->name,
                ];
            }),
        ]);
    }
}
