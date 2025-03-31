<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Exception;
use Google\Service\Sheets;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_ValueRange;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class GoogleSheetsService
{
    private Sheets $service;
    private string $sheetId;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $pathToCredentials = storage_path('app/'.env('GOOGLE_SERVICE_ACCOUNT_JSON'));

        // Get the environment variable
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

        $this->service = new Sheets($client);
        $this->sheetId = env('GOOGLE_SHEET_ID_OFFICEDAYS');
    }

    /**
     * Fetch data from all office sheets and return structured data.
     */
    public function getOfficeSchedules(): array
    {
        $sheets = [
            'uk' => '2025_PD UK!A:P',
            'hu' => '2025_HU_office/Friday offs!A:N',
            'other' => 'Allison_Mike 2025!A:D'
        ];

        $users = User::pluck('id', 'email')->toArray();
        $allOfficeData = [];

        foreach ($sheets as $officeKey => $sheetRange) {
            $this->logger->info('Synchronizing the '.$officeKey.' office...');

            $data = $this->service->spreadsheets_values->get($this->sheetId, $sheetRange);
            $values = $data->getValues();
            if (empty($values)) {
                continue;
            }

            $parsedData = [];
            $userColumns = [];
            $currentMonth = Carbon::createFromFormat('M Y', 'Jan 2025')->startOfMonth();
            $firstDayRow = null;

            foreach ($values as $rowIndex => $row) {

                // Detect header row with user names
                if (empty($userColumns) && isset($row[0]) && strtoupper(trim($row[0])) === 'DAY') {
                    foreach ($row as $colIndex => $colName) {
                        if ($colIndex === 0 || empty($colName)) {
                            continue;
                        }
                        $userColumns[$colIndex] = strtolower(trim($colName))."@paperdog.com";
                    }
                    continue;
                }

                // Detect first row with numeric day
                if ($firstDayRow === null && isset($row[0]) && is_numeric($row[0])) {
                    $firstDayRow = $rowIndex;
                    continue;
                }

                // Process work statuses
                if ($firstDayRow !== null && isset($row[0]) && is_numeric($row[0])) {
                    $day = intval($row[0]);

                    // If day resets to 1, assume new month
                    if ($day == 1 && $currentMonth) {
                        $currentMonth = $currentMonth->copy()->addMonth();
                    }

                    if (!$currentMonth) {
                        continue;
                    }

                    $date = $currentMonth->copy()->day($day)->format('Y-m-d');

                    foreach ($userColumns as $colIndex => $email) {
                        $userId = $users[$email] ?? null;
                        if (!$userId) {
                            continue;
                        }

                        $status = $row[$colIndex] ?? '';
                        if (!empty($status)) {
                            $parsedData[] = [
                                'office' => $officeKey,
                                'date' => $date,
                                'user_id' => $userId,
                                'name' => $email,
                                'status' => $status
                            ];
                        }
                    }
                }
            }

            $allOfficeData = array_merge($allOfficeData, $parsedData);
        }

        return $allOfficeData;
    }

    /**
     * @throws Exception
     */
    public function updateHUOfficeSchedule($userEmail, $date, $status)
    {
        $sheetRange = '2025_HU_office/Friday offs!A:Z';

        // Beolvassuk a sheet tartalmát
        $data = $this->service->spreadsheets_values->get($this->sheetId, $sheetRange);
        $values = $data->getValues();

        if (empty($values)) {
            return "Nincs adat a Google Sheetben.";
        }

        $userColumnIndex = null;
        $dateRowIndex = null;
        $currentMonth = null;
        $monthStartRow = null;

        // Felhasználói oszlop keresése
        foreach ($values as $rowIndex => $row) {
            if (empty($row)) {
                continue;
            }

            // Ha találunk egy hónapnevet (pl. "Feb 2025")
            if (isset($row[1]) && preg_match('/([A-Za-zÁÉÍÓÖŐÚÜŰa-záéíóöőúüű]+)\s(\d{4})/', $row[1], $matches)) {
                $currentMonth = Carbon::createFromFormat('M Y', $matches[1].' '.$matches[2]);
                $monthStartRow = $rowIndex; // A hónap neve alatt kezdődik a napok listája
                Log::info("Detected new month: {$currentMonth->format('F Y')} at row {$rowIndex}");
            }

            // Fejléc keresése (DAY sor)
            if (isset($row[0]) && strtoupper(trim($row[0])) === 'DAY') {
                foreach ($row as $colIndex => $colName) {
                    if (strtolower(trim($colName))."@paperdog.com" === strtolower($userEmail)) {
                        $userColumnIndex = $colIndex;
                        break;
                    }
                }
            }

            // Ha a hónap már megvan, akkor az adott napot keressük
            if ($currentMonth && isset($row[0]) && is_numeric($row[0])) {
                $day = intval($row[0]);

                $rowDate = $currentMonth->copy()->day($day)->format('Y-m-d');
                Log::info("Checking row {$rowIndex}: expected date {$date}, found {$rowDate}");

                if ($rowDate === $date) {
                    $dateRowIndex = $rowIndex;
                    break;
                }
            }
        }

        // Ha megtaláltuk a felhasználó oszlopát és a dátum sorát
        if ($userColumnIndex !== null && $dateRowIndex !== null) {
            $cell = chr(65 + $userColumnIndex).($dateRowIndex + 1);
            $updateRange = str_replace('A:Z', $cell, $sheetRange);

            Log::info("Updating Google Sheets at range: {$updateRange} with status: {$status}");

            $this->service->spreadsheets_values->update(
                $this->sheetId,
                $updateRange,
                new Google_Service_Sheets_ValueRange([
                    'values' => [[$status]]
                ]),
                ['valueInputOption' => 'USER_ENTERED']
            );

            $statusColors = [
                'office' => ['red' => 182 / 255, 'green' => 215 / 255, 'blue' => 168 / 255],
                'WFH' => ['red' => 213 / 255, 'green' => 166 / 255, 'blue' => 189 / 255],
                'Friday off' => ['red' => 252 / 255, 'green' => 229 / 255, 'blue' => 153 / 255],
                'off' => ['red' => 230 / 255, 'green' => 184 / 255, 'blue' => 175 / 255],
                'sick' => ['red' => 204 / 255, 'green' => 0, 'blue' => 0],
            ];

            if (!isset($statusColors[$status])) {
                Log::warning("No color defined for status: {$status}");
                return;
            }

            $color = $statusColors[$status];

            // Google Sheets API batchUpdate kérés
            $request = [
                'requests' => [
                    [
                        'updateCells' => [
                            'range' => [
                                'sheetId' => '523709850',
                                'startRowIndex' => $dateRowIndex,
                                'endRowIndex' => $dateRowIndex + 1,
                                'startColumnIndex' => $userColumnIndex,
                                'endColumnIndex' => $userColumnIndex + 1,
                            ],
                            'rows' => [
                                [
                                    'values' => [
                                        [
                                            'userEnteredFormat' => [
                                                'backgroundColor' => [
                                                    'red' => $color['red'],
                                                    'green' => $color['green'],
                                                    'blue' => $color['blue']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'fields' => 'userEnteredFormat.backgroundColor'
                        ]
                    ]
                ]
            ];

            // API hívás a szín módosítására
            $this->service->spreadsheets->batchUpdate($this->sheetId,
                new Google_Service_Sheets_BatchUpdateSpreadsheetRequest($request));
            return "Frissítés sikeres: {$userEmail} - {$date} - {$status} a HU sheetben.";
        }

        return "Nem található adat a megadott felhasználóra és dátumra a HU sheetben.";
    }

}
