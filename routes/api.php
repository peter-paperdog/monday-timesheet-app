<?php

use App\Models\User;
use Carbon\Carbon;
use Google\Client;

use Google\Service\Sheets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/test', function (Request $request) {
    // Fetch all users from the database and create a name-to-ID mapping
    $users = User::pluck('id', 'email')->toArray();

    $pathToCredentials = storage_path('app/' . env('GOOGLE_SERVICE_ACCOUNT_JSON'));

    // Check if credentials file exists
    if (!file_exists($pathToCredentials)) {
        return response()->json(['error' => 'Google service account JSON file not found.'], 500);
    }

// Initialize Google Client
    $client = new Client();
    $client->setAuthConfig($pathToCredentials);
    $client->addScope(Sheets::SPREADSHEETS_READONLY);

    // Load Spreadsheet ID from .env
    $sheetId = env('GOOGLE_SHEET_ID_OFFICEDAYS');
    if (!$sheetId) {
        return response()->json(['error' => 'Google Sheet ID is not set in .env file.'], 500);
    }

    // Define the different office sheets
    $sheets = [
        'uk' => '2025_PD UK!A:Z',
        'hu' => '2025_HU_office/Friday offs!A:Z',
        'other' => 'Allison_Mike 2025!A:Z'
    ];

    // Initialize Sheets Service
    $service = new Sheets($client);
    $allOfficeData = [];
    foreach ($sheets as $officeKey => $sheetRange) {
        $data = $service->spreadsheets_values->get($sheetId, $sheetRange);
        $values = $data->getValues(); // Extract actual data

        if (empty($values)) {
            continue; // Skip if no data found for this office
        }

        $parsedData = [];
        $userColumns = [];
        $currentMonth = null;
        $firstDayRow = null;

        foreach ($values as $rowIndex => $row) {
            // Detect the month row (contains "Jan 2025" etc.)
            if (!$currentMonth && isset($row[1]) && preg_match('/([A-Za-z]+)\s(\d{4})/', $row[1], $matches)) {
                $currentMonth = Carbon::createFromFormat('M Y', $matches[1] . ' ' . $matches[2]);
                continue;
            }

            // Detect header row with user names (Only done once)
            if (empty($userColumns) && isset($row[0]) && strtoupper(trim($row[0])) === 'DAY') {
                foreach ($row as $colIndex => $colName) {
                    if ($colIndex === 0 || empty($colName)) continue; // Skip first column & empty columns
                    $userColumns[$colIndex] = strtolower(trim($colName)) . "@paperdog.com"; // Store column index → Name mapping
                }
                continue;
            }

            // Detect the first row with a numeric day (e.g., 1, 2, 3...)
            if ($firstDayRow === null && isset($row[0]) && is_numeric($row[0])) {
                $firstDayRow = $rowIndex;
                continue;
            }

            // Process work statuses based on fixed columns
            if ($firstDayRow !== null && isset($row[0]) && is_numeric($row[0])) {
                $day = intval($row[0]);

                // If day resets to 1, assume a new month
                if ($day == 1 && $currentMonth) {
                    $currentMonth = $currentMonth->copy()->addMonth();
                }

                if (!$currentMonth) {
                    continue; // Skip if month is not determined
                }

                $date = $currentMonth->copy()->day($day)->format('Y-m-d');

                // Iterate over recorded columns and statuses
                foreach ($userColumns as $colIndex => $email) {
                    $userId = $users[$email] ?? null;
                    if (!$userId) continue;

                    $status = $row[$colIndex] ?? ''; // Fetch from the correct column
                    if (!empty($status)) {
                        $parsedData[] = [
                            'office' => $officeKey, // Identify which office the data belongs to
                            'date' => $date,
                            'user_id' => $userId,
                            'name' => $email,
                            'status' => $status
                        ];
                    }
                }
            }
        }
        // Merge this office's parsed data into the full dataset
        $allOfficeData = array_merge($allOfficeData, $parsedData);
    }


    var_dump($allOfficeData);
});
