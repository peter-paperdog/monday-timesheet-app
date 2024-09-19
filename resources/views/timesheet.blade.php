<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weekly Timesheet for {{ $data['name'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            font-size: 10px;
            line-height: 1.4;
        }

        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 12px;
            margin-top: 15px;
            margin-bottom: 8px;
            padding-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .total-column {
            width: 70px;
            text-align: right;
        }

        .project-total, .item-total {
            text-align: right;
            font-weight: bold;
        }

        .project-total, .sub-item-total {
            text-align: right;
            font-style: italic;
        }

        .final-total {
            font-size: 14px;
            text-align: right;
            margin-top: 20px;
            font-weight: bold;
        }

        .day-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            font-size: 24px;
            font-weight: bold;
        }

        .logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        .footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<div style="text-align: right;">
    <img src="{{ resource_path('image/img.png') }}" class="logo" alt="Company Logo">
</div>

<h1 style=" margin: 0;">Weekly Timesheet</h1>
<p style="text-align: center; padding: 0; margin: 0; font-style: italic; padding-bottom: 12px;" >{{ $data['startOfWeek'] }} - {{ $data['endOfWeek'] }}</p>
<h1 style=" margin: 0;">{{ $data['name'] }}</h1>

@foreach($data['days'] as $day)
    <div class="day-header">
        <h2>{{ $day->day }}, {{ $day->date }}</h2>
        <span class="project-total">Total: {{ round($day->time / 3600, 2) }} hours</span>
    </div>

    <table>
        <thead>
        <tr>
            <th>Project/Task</th>
            <th class="total-column">Total Hours</th>
        </tr>
        </thead>
        <tbody>
        @foreach($day->boards as $board)
            <tr>
                <td colspan="2"><strong>{{ $board->name }}</strong></td>
            </tr>
            @foreach($board->groups as $group_name => $group)
                <tr>
                    <td><strong>{{ $group_name }}</strong></td>
                    <td class="item-total">{{ round($group->duration / 3600, 2) }} hours</td>
                </tr>
                @foreach($group->items as $item)
                    <tr>
                        <td>{{ $item->item_name }}</td>
                        <td class="sub-item-total">{{ round($item->duration / 3600, 2) }} hours</td>
                    </tr>
                @endforeach
            @endforeach
            <tr>
                <td class="project-total">Total for {{ $board->name }}</td>
                <td class="project-total">{{ round($board->duration / 3600, 2) }} hours</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach

<div class="final-total">
    Total for the Week: <strong>{{ round($data['time'] / 3600, 2) }} hours</strong>
</div>
<div class="footer">
    Printed on {{ $printedDate }}
</div>
</body>
</html>
