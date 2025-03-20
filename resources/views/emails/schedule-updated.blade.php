<x-mail::message>
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
     Thanks,
     the PD Monday team


</x-mail::message>
