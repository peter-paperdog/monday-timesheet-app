<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Office Schedule
        </h2>
    </x-slot>

    <div class="flex items-center justify-center gap-4 my-4">
        <!-- Left Arrow (Previous Week) -->
        <a href="{{ route('office-schedule', ['weekStartDate' => $startOfWeek->copy()->subWeek()->toDateString()]) }}"
           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
            ←
        </a>

        <!-- Current Week Display -->
        <span class="font-semibold text-lg dark:text-gray-300 dark:hover:text-white">
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

                    <table class="w-full border-collapse border border-gray-300 dark:border-gray-700">
                        <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700">
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-left" width="60"></th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-left">Username</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Monday</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Tuesday</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Wednesday</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Thursday</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Friday</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($structuredData as $username => $days)
                            @php
                                $flagPath = "/images/flag-{$locations[$username]}.svg";
                            @endphp
                            <tr class="border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">
                                    @if(file_exists(public_path($flagPath)))
                                        <img src="{{ asset($flagPath) }}" alt="{{ $locations[$username] }}" class="w-6 h-4">
                                    @endif
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{{ $username }}</td>
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center
                                            @if(strtolower($days[$day]) == 'office') bg-green-200 dark:bg-green-800
                                            @elseif(strtolower($days[$day]) == 'wfh') bg-blue-200 dark:bg-blue-800
                                            @elseif(strtolower($days[$day]) == 'friday off') bg-yellow-200 dark:bg-yellow-800
                                            @endif">
                                        {{ ucfirst($days[$day]) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
