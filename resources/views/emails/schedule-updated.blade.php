<!DOCTYPE html>
<html>
<head>
    <title>Office Schedules Updated</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h1>Office Schedules Updated</h1>

<p>The following user schedules have been changed:</p>

<table>
    <thead>
    <tr>
        <th>User</th>
        <th>Date</th>
        <th>Old Status</th>
        <th>New Status</th>
    </tr>
    </thead>
    <tbody>
    @foreach($changedSchedules as $schedule)
        <tr>
            <td>{{ $schedule['user_name'] }}</td>
            <td>{{ $schedule['date'] }}</td>
            <td>{{ $schedule['old_status'] }}</td>
            <td>{{ $schedule['status'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<p>Check the system for more details.</p>

<p>Thanks,<br>
    <strong>The PD Monday Team</strong></p>

</body>
</html>
