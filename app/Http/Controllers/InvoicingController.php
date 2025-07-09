<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceResource;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Item;
use App\Services\MondayService;
use Google\Service\Drive\DriveFile;
use Google\Service\Sheets;
use Google\Service\Drive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoicingController extends Controller
{
    public function __construct(private MondayService $mondayService)
    {

    }

    public function index(Request $request)
    {
        $query = Invoice::with([
            'client'
        ])->latest();

        $invoices = $query->get();

        return response()->json([
            'invoices' => $invoices
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact.id' => 'required|numeric',
            'client.id' => 'required|numeric',
            'currency' => 'required|string',
            'number' => 'nullable|string',
            'issueDate' => 'required|date',
        ]);

        $contact = Contact::where('id', $data['contact']['id'])->first();

        if (!$contact) {
            return response()->json(['message' => 'Contact not found.'], 422);
        }

        $client = Client::where('id', $data['client']['id'])->first();

        if (!$client) {
            return response()->json(['message' => 'Client not found.'], 422);
        }

        $invoice = Invoice::create([
            'contact_id' => $contact->id,
            'client_id' => $client->id,
            'currency' => $data['currency'],
            'number' => $data['number'] ?? null,
            'issue_date' => $data['issueDate'],
        ]);

        return response()->json([
            'message' => 'Invoice created',
            'invoice_id' => $invoice->id,
        ]);

    }
    public function googleCreate(Request $request){


        try {
            $envValue = trim(env('GOOGLE_SERVICE_ACCOUNT_JSON'));

            if (empty($envValue)) {
                Log::error("GOOGLE_SERVICE_ACCOUNT_JSON is missing or empty.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Google service account is not configured.',
                ], 500);
            }

            $pathToCredentials = storage_path('app/'.$envValue);

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
                Log::error("Access to original spreadsheet failed: ".$e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot access original Google Sheet.',
                    'google_error' => $e->getMessage(),
                ], 500);
            }

            // Duplicate spreadsheet
            $copy = new DriveFile([
                'name' => 'Invoice #'.$invoice->id.' – '.now()->format('Y-m-d H:i:s'),
                'parents' => [$targetFolderId],
            ]);

            $newFile = $driveService->files->copy($originalSpreadsheetId, $copy, [
                'supportsAllDrives' => true,
            ]);

            $spreadsheetId = $newFile->id;
            $sheetUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit";

            Log::info("Invoice spreadsheet generated: $sheetUrl");

            // Optional: save URL to invoice
            $invoice->sheet_url = $sheetUrl;
            $invoice->save();
        } catch (\Google\Service\Exception $e) {
            Log::error("Google API error: ".$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Google API error: '.$e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error("General error: ".$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected error: '.$e->getMessage(),
            ], 500);
        }

        $items = array_map(function ($item) {
            return [
                'item_id' => (int) $item['monday_id'],
                'board_id' => (int) $item['board_id'], // itt történik a cast int-re
            ];
        }, $data['items']);
        try {
            $this->mondayService->updateTaskStatus('Invoiced', $items);
        } catch (\Exception $e) {
            Log::error("Monday task update failed: ".$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Monday task update failed.',
                'debug' => $e->getMessage()
            ], 500);
        }

        try {
            // Load credentials
            $envValue = trim(env('GOOGLE_SERVICE_ACCOUNT_JSON'));
            $pathToCredentials = storage_path('app/'.$envValue);

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

            $data = $invoice->items->map(function ($item) {
                return [
                    $item->description,     // Column A (merged A:X)
                    $item->type,            // Column Y (Y:AD)
                    $item->quantity,        // Column AE (AE:AJ)
                    $item->unit_price,      // Column AK (AK:AP)
                    null,                   // Placeholder for AQ (we’ll use formula)
                ];
            })->values()->all();

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
            $sheets->spreadsheets->batchUpdate($spreadsheetId,
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
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
            $sheets->spreadsheets_values->update($spreadsheetId, 'A'.$insertRow, new \Google\Service\Sheets\ValueRange([
                'values' => $rows,
            ]), ['valueInputOption' => 'USER_ENTERED']);

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

            $sheets->spreadsheets->batchUpdate($spreadsheetId,
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
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
            $sheets->spreadsheets->batchUpdate($spreadsheetId,
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                    'requests' => $formatRequests
                ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Sheet updated successfully.',
                'invoice' => $invoice->load('client'),
            ]);
        } catch (\Exception $e) {
            Log::error("Sheet update failed: ".$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Sheet update failed.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function tasks(Request $request): JsonResponse
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

    public function contacts(): JsonResponse
    {
        $mondayService = new MondayService();
        $data = $mondayService->getInvoiceContacts();

        return response()->json(
            $data
        );
    }

    public function show($id)
    {
        $invoice = Invoice::with([
            'client',
            'groups.invoiceProject.project',
            'groups.items.task',
            'groups.items.project'
        ])->findOrFail($id);
        return new InvoiceResource($invoice);
    }

    public function destroy($id)
    {
        try {
            $invoice = Invoice::with('items')->find($id);

            if (!$invoice) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Invoice with ID {$id} not found.",
                ], 404);
            }

            $items = [];

            foreach ($invoice->items as $item) {
                $item->delete();
                $items[] = [
                    'item_id' => (int) $item['monday_id'],
                    'board_id' => (int) $item['board_id'],
                ];
            }

            $invoice->delete();

            $this->mondayService->updateTaskStatus('To Be Invoiced', $items);

            Log::info("Invoice #{$invoice->id} deleted.");

            return response()->json([
                'status' => 'success',
                'message' => "Invoice #{$invoice->id} deleted successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete invoice: ".$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete invoice.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
