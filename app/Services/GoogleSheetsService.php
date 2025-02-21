<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Sheets;
use Psr\Log\LoggerInterface;

class GoogleSheetsService
{
    private Sheets $service;
    private string $sheetId;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $pathToCredentials = storage_path('app/' . env('GOOGLE_SERVICE_ACCOUNT_JSON'));

        if (!file_exists($pathToCredentials)) {
            throw new \Exception('Google service account JSON file not found.');
        }

        // Initialize Google Client
        $client = new Client();
        $client->setAuthConfig($pathToCredentials);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $this->service = new Sheets($client);
        $this->sheetId = env('GOOGLE_SHEET_ID_OFFICEDAYS');
    }

    /**
     * Fetch data from all office sheets and return structured data.
     */
    public function getOfficeSchedules(): array
    {
        $sheets = [
            'uk' => '2025_PD UK!A:Z',
            'hu' => '2025_HU_office/Friday offs!A:Z',
            'other' => 'Allison_Mike 2025!A:Z'
        ];

        $users = User::pluck('id', 'email')->toArray();
        $allOfficeData = [];

        foreach ($sheets as $officeKey => $sheetRange) {
            $this->logger->info('Synchronizing the ' . $officeKey . ' office...');

            $data = $this->service->spreadsheets_values->get($this->sheetId, $sheetRange);
            $values = $data->getValues();
            if (empty($values)) continue;

            $parsedData = [];
            $userColumns = [];
            $currentMonth = null;
            $firstDayRow = null;

            foreach ($values as $rowIndex => $row) {
                // Detect month row (e.g., "Jan 2025")
                if (!$currentMonth && isset($row[1]) && preg_match('/([A-Za-z]+)\s(\d{4})/', $row[1], $matches)) {
                    $currentMonth = Carbon::createFromFormat('M Y', $matches[1] . ' ' . $matches[2]);
                    continue;
                }

                // Detect header row with user names
                if (empty($userColumns) && isset($row[0]) && strtoupper(trim($row[0])) === 'DAY') {
                    foreach ($row as $colIndex => $colName) {
                        if ($colIndex === 0 || empty($colName)) continue;
                        $userColumns[$colIndex] = strtolower(trim($colName)) . "@paperdog.com";
                        $this->logger->info($colName . "'s schedule...");
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

                    if (!$currentMonth) continue;

                    $date = $currentMonth->copy()->day($day)->format('Y-m-d');

                    foreach ($userColumns as $colIndex => $email) {
                        $userId = $users[$email] ?? null;
                        if (!$userId) continue;

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
}
