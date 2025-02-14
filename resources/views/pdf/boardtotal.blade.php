<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timesheet Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid black;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .day-header {
            background-color: #e0e0e0;
            padding: 12px;
        }

        .spacer {
            height: 20px;
        }

        h2 {
            font-size: 2em;
            padding: 0;
            margin: 0;
        }

        h3 {
            margin: 0;
            padding: 0;
            font-weight: normal;
            font-style: italic;
        }

        .text-italic {
            font-style: italic;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div style="text-align: right;">
    <img src="{{ resource_path('image/img.png') }}" class="logo" alt="Company Logo">
</div>
<div class="header">
    <div>
        <h2>Total time spent on each board</h2>
        <h3>{{ $startOfWeek->format('d M Y') }} - {{ $endOfWeek->format('d M Y') }}</h3>
    </div>
</div>
<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
    <tr class="bg-gray-100 dark:bg-gray-700">
        <th class="border border-gray-300 px-4 py-2 text-left">Board</th>
        <th class="border border-gray-300 px-4 py-2 text-center">Total Time</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($boardWeeklyTotals as $boardTotal)
        <tr>
            <td class="border border-gray-300 px-4 py-2">
                {{ $boardTotal->board_name ?? 'Unknown Board' }}
            </td>
            <td class="border border-gray-300 px-4 py-2 text-center">
                {{ floor($boardTotal->total_minutes / 60) }}h {{ $boardTotal->total_minutes % 60 }}m
            </td>
        </tr>
    @endforeach
    </tbody>
</table>


</body>
</html>
