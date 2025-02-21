<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Office schedule
        </h2>
    </x-slot>

    <div class="flex items-center justify-center gap-4 my-4">
        <!-- Left Arrow (Previous Week) -->
        <a href="{{ route('office-schedule', ['weekStartDate' => $startOfWeek->copy()->subWeek()->toDateString()]) }}"
           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
            ←
        </a>

        <!-- Current Week Display -->
        <span class="font-semibold text-lg">
        {{ $startOfWeek->format('M d, Y') }} - {{ $endOfWeek->format('M d, Y') }}
        </span>

        <!-- Right Arrow (Next Week) -->
        <a href="{{ route('office-schedule', ['weekStartDate' => $startOfWeek->copy()->addWeek()->toDateString()]) }}"
           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
            →
        </a>
    </div>

    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @foreach ($schedules as $date => $entries)
                        <div class="border-b py-4">
                            <h3 class="text-lg font-semibold mb-2">{{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}</h3>

                            <table class="w-full border-collapse border border-gray-300 dark:border-gray-700">
                                <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-left">Username</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-left">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($entries as $entry)
                                    <tr class="border border-gray-300 dark:border-gray-700">
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{{ $entry->username }}</td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{{ ucfirst($entry->status) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
