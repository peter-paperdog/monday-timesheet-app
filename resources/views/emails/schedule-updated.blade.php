<x-mail::message>
    # Office Schedules Updated

    The following user schedules have been changed:

    <x-mail::table>
        | User | Date | Old Status | New Status |
        |------|------|-----------|------------|
        @foreach($changedSchedules as $schedule)
            | {{ $schedule['user_name'] }} | {{ $schedule['date'] }} | {{ $schedule['old_status'] }} | {{ $schedule['status'] }} |
        @endforeach
    </x-mail::table>

    Check the system for more details.

    Thanks,
    **The PD Monday Team**
</x-mail::message>
