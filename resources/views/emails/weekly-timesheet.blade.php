<x-mail::message>
    # Weekly Timesheet Report

    Hello {{ explode(' ', trim($user->name))[0] }},

    Your timesheet for the week {{ $startOfWeek->format('d M Y') }} - {{ $startOfWeek->copy()->endOfWeek()->format('d M Y') }} is attached to this email.

    If you have any questions, please let us know.

    Thanks,
    the PD Monday team
</x-mail::message>
