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
        <h2>{{ $user->name }}</h2>
        <h3>{{ $startOfWeek->format('d M Y') }} - {{ $endOfWeek->format('d M Y') }}</h3>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>Day</th>
        <th>Board</th>
        <th>Group</th>
        <th>Task</th>
        <th width="60">Total Time</th>
    </tr>
    </thead>
    <tbody>
    @php
        $weeklyTotal = 0;
    @endphp

    @foreach (range(0, 6) as $dayOffset)
        @php
            $date = $startOfWeek->copy()->addDays($dayOffset);
            $dateKey = $date->format('Y-m-d (l)');
            $dailyTotal = 0;
            $previousBoard = null;
            $previousGroup = null;
            $boardCount = isset($groupedData[$dateKey]) ? count($groupedData[$dateKey]) : 0;
            $officeStatus = isset($officeSchedules[$date->format('Y-m-d')])?$officeSchedules[$date->format('Y-m-d')]:null;
        @endphp

        @if ($date->isFuture())
            @continue
        @endif

        @if ($date->isWeekend() && !isset($groupedData[$dateKey]))
            @continue
        @endif

        <!-- Add spacing between days -->
        <tr class="spacer">
            <td colspan="5" style="border: none;"></td>
        </tr>

        <tr class="day-header">
            <td colspan="5"><strong style="font-size: 1.5em">{{ $date->format('l') }}</strong>
                @if ($officeStatus)
                    <i>({{ strtolower($officeStatus) }})</i>
                @endif
                <div
                    style="font-style: normal; padding-top: 5px; font-style: italic;">{{ $date->format('M d, Y') }}</div>
            </td>
        </tr>

        @if (isset($groupedData[$dateKey]))
            @foreach ($groupedData[$dateKey] as $boardName => $groups)
                @php
                    $boardTotal = 0;
                    $groupCount = count($groups);
                @endphp

                @foreach ($groups as $groupName => $tasks)
                    @php
                        $groupTotal = 0;
                        $taskCount = count($tasks);
                    @endphp

                    @foreach ($tasks as $taskName => $entries)
                        @php
                            $taskTotal = $entries->sum(fn($t) => \Carbon\Carbon::parse($t->started_at)->diffInMinutes(\Carbon\Carbon::parse($t->ended_at)));
                            $dailyTotal += $taskTotal;
                            $groupTotal += $taskTotal;
                            $boardTotal += $taskTotal;
                        @endphp
                        <tr>
                            <td colspan="2" class="font-bold" style="font-size: 1.2em;">
                                @if ($boardName !== $previousBoard)
                                    {{ $boardName }}
                                    @php $previousBoard = $boardName; @endphp
                                @endif
                            </td>
                            <td style="border-left: none; border-right: none;">
                                @if ($groupName !== $previousGroup)
                                    {{ $groupName ?? 'No Group' }}
                                    @php $previousGroup = $groupName; @endphp
                                @endif
                            </td>
                            <td>{{ $taskName }}</td>
                            <td class="text-right text-italic">
                                {{ floor($taskTotal / 60) }}h {{ $taskTotal % 60 }}m
                            </td>
                        </tr>
                    @endforeach

                    <tr class="bg-gray-100 font-bold">
                        <td colspan="4" class="text-right" style="font-size: 1.2em">Group Total:</td>
                        <td class="text-right" style="font-size: 1.2em">
                            {{ floor($groupTotal / 60) }}h {{ $groupTotal % 60 }}m
                        </td>
                    </tr>
                @endforeach

                @if ($boardCount > 1)
                    <tr class="bg-gray-200 font-bold">
                        <td colspan="4" class="text-right">Board Total:</td>
                        <td class="text-right">
                            {{ floor($boardTotal / 60) }}h {{ $boardTotal % 60 }}m
                        </td>
                    </tr>
                @endif
            @endforeach
        @else
            @if (!$date->isWeekend())
                <tr>
                    <td colspan="5" class="text-center text-italic">No entries for this day.</td>
                </tr>
            @endif
        @endif

        @if ($dailyTotal > 0)
            <tr class="bg-gray-300 font-bold text-lg">
                <td colspan="4" class="text-right" style="font-size: 1.25em;border: none;">Daily Total:</td>
                <td class="text-right" style="font-size: 1.25em;border: none;">
                    {{ $dailyTotal > 0 ? floor($dailyTotal / 60) . 'h ' . ($dailyTotal % 60) . 'm' : '-' }}
                </td>
            </tr>
        @endif

        @php
            $weeklyTotal += $dailyTotal;
        @endphp
    @endforeach


    <!-- Add spacing between days -->
    <tr class="spacer">
        <td colspan="5" style="border: none;"></td>
    </tr>
    <tr class="bg-gray-900 font-bold text-xl text-white">
        <td colspan="5" class="text-right" style="border: none; font-size: 1.8em">Weekly Total:
            {{ floor($weeklyTotal / 60) }}h {{ $weeklyTotal % 60 }}m
        </td>
    </tr>
    </tbody>
</table>

</body>
</html>
