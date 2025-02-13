@php use Illuminate\Support\Carbon; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Weekly timesheets
        </h2>
    </x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 text-right text-gray-400 mt-2 font-extralight" id="lastupdated">
        Last updated: {{$lastupdated}}
        <br>
        @if(auth()->user()->admin)
            <a href="{{ route('sync.boards') }}"
               onclick="return confirm('The sync process might take a few minutes. Do you want to continue?')"
               class="text-sm py-2 bg-blue-600 hover:bg-blue-700 hover:underline  transition">
                Update
            </a>
        @endif
    </div>

    @if(auth()->user()->admin)
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-5">
            <div class="text-gray-900 dark:text-gray-100">
                <form method="get" action="{{ route('timesheets') }}" class="flex items-center gap-4">
                    <!-- User Dropdown -->
                    <select name="user_id"
                            class="px-4 py-2 border rounded-lg shadow-sm text-gray-900 dark:text-gray-100 dark:bg-gray-700 dark:border-gray-600">
                        @foreach($users as $userOption)
                            <option
                                value="{{ $userOption->id }}" {{ $selectedUserId == $userOption->id ? 'selected' : '' }}>
                                {{ $userOption->name }}
                            </option>
                        @endforeach
                    </select>
                    <!-- Search Button -->
                    <x-primary-button class="px-6 py-2 text-lg whitespace-nowrap">
                        {{ __('Search') }}
                    </x-primary-button>
                </form>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-center gap-4 my-4">
        <!-- Left Arrow (Previous Week) -->
        <a href="{{ route('timesheets', ['weekStartDate' => $startOfWeek->copy()->subWeek()->toDateString(), 'user_id' => $selectedUserId]) }}"
           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
            ←
        </a>

        <!-- Current Week Display -->
        <span class="font-semibold text-lg">
        {{ $startOfWeek->format('M d, Y') }} - {{ $endOfWeek->format('M d, Y') }}
    </span>

        <!-- Right Arrow (Next Week) -->
        <a href="{{ route('timesheets', ['weekStartDate' => $startOfWeek->copy()->addWeek()->toDateString(), 'user_id' => $selectedUserId]) }}"
           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
            →
        </a>
    </div>

    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($groupedData->isEmpty())
                        <p style="text-align: center; font-weight: bold; padding: 20px;">
                            No time tracking records found.
                        </p>
                    @else
                        <!-- Table -->
                        <table class="w-full border-collapse border border-gray-300 mt-4">
                            <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="border border-gray-300 px-4 py-2 text-left">Day</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Board</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Group</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Task</th>
                                <th class="border border-gray-300 px-4 py-2 text-center">Total Time</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach (range(0, 6) as $dayOffset)
                                @php
                                    $dateKey = $startOfWeek->copy()->addDays($dayOffset)->format('Y-m-d (l)');
                                    $dailyTotal = 0;
                                @endphp

                                <tr class="bg-gray-200 dark:bg-gray-700 font-bold">
                                    <td class="border border-gray-300 px-4 py-2" colspan="5">
                                        {{ $dateKey }}
                                    </td>
                                </tr>

                                @if (isset($groupedData[$dateKey]))
                                    @foreach ($groupedData[$dateKey] as $boardName => $groups)
                                        @foreach ($groups as $groupName => $tasks)
                                            @foreach ($tasks as $taskName => $entries)
                                                @php
                                                    $taskTotal = $entries->sum(fn($t) => Carbon::parse($t->started_at)->diffInMinutes(Carbon::parse($t->ended_at)));
                                                    $dailyTotal += $taskTotal;
                                                @endphp
                                                <tr>
                                                    <td class="border border-gray-300 px-4 py-2"></td> <!-- Empty for alignment -->
                                                    <td class="border border-gray-300 px-4 py-2">
                                                        <a href="https://paperdog-team.monday.com/boards/{{$entries->first()->item->board->id}}"
                                                           target="_blank"
                                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 hover:underline transition">
                                                            {{ $boardName }}
                                                        </a>
                                                    </td>
                                                    <td class="border border-gray-300 px-4 py-2">
                                                        {{ $groupName ?? 'No Group' }}
                                                    </td>
                                                    <td class="border border-gray-300 px-4 py-2">
                                                        <a href="https://paperdog-team.monday.com/boards/{{$entries->first()->item->board->id}}/pulses/{{$entries->first()->item->id}}"
                                                           target="_blank"
                                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 hover:underline transition">
                                                            {{ $taskName }}
                                                        </a>
                                                    </td>
                                                    <td class="border border-gray-300 px-4 py-2 text-center">
                                                        {{ floor($taskTotal / 60) }}h {{ $taskTotal % 60 }}m
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="border border-gray-300 px-4 py-2 text-center" colspan="5">
                                            No entries for this day.
                                        </td>
                                    </tr>
                                @endif

                                <!-- Daily Total Row -->
                                <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                                    <td class="border border-gray-300 px-4 py-2 text-right" colspan="4">Daily Total</td>
                                    <td class="border border-gray-300 px-4 py-2 text-center">
                                        {{ $dailyTotal > 0 ? floor($dailyTotal / 60) . 'h ' . ($dailyTotal % 60) . 'm' : '-' }}
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Weekly Total Row -->
                            <tr class="bg-gray-900 dark:bg-gray-600 font-bold text-white">
                                <td class="border border-gray-300 px-4 py-2 text-right" colspan="4">Weekly Total</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    {{ $groupedData->flatten()->sum(fn($t) => Carbon::parse($t->started_at)->diffInMinutes(Carbon::parse($t->ended_at))) / 60 }}h
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
