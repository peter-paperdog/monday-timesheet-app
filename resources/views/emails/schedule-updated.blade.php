<x-mail::message>
    # Office Schedules Updated

    The following user schedules have been changed:

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
        <tr>
            <th style="border: 1px solid #ddd; padding: 8px; background: #f2f2f2;">User</th>
            <th style="border: 1px solid #ddd; padding: 8px; background: #f2f2f2;">Date</th>
            <th style="border: 1px solid #ddd; padding: 8px; background: #f2f2f2;">Old Status</th>
            <th style="border: 1px solid #ddd; padding: 8px; background: #f2f2f2;">New Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach($changedSchedules as $schedule)
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $schedule['user_name'] }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $schedule['date'] }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $schedule['old_status'] }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $schedule['status'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    Check the system for more details.

    Thanks,
    **The PD Monday Team**
</x-mail::message>
