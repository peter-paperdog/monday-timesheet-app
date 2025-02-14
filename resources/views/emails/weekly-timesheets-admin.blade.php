<x-mail::message>
    Dear Team,

    Attached is the weekly timesheet report for {{ $startOfWeek->format('d M Y') }} - {{ $startOfWeek->copy()->endOfWeek()->format('d M Y') }}.

    Thanks,
    the PD Monday team
</x-mail::message>
