@php use Illuminate\Support\Carbon; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Weekly timesheets
        </h2>
    </x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 text-right text-gray-400 mt-2 font-extralight" id="lastupdated">
        The data displayed here is updated hourly from Monday.com.<br>The last update was {{$lastupdated}}
        <br>
        @if(auth()->user()->admin)
            <a href="{{ route('sync.boards') }}"
               onclick="return confirm('The sync process might take a few minutes. Do you want to continue?')"
               class="text-sm py-2 hover:underline  transition">
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

    <div class="max-w-7xl mx-auto px-3">
        <!-- Download PDF Button -->
        <a href="{{ route('timesheet.download.PDF', ['userId' => $selectedUserId, 'weekStartDate' => $startOfWeek->toDateString()]) }}"
           target="_blank"
           class="ml-4">
            <x-primary-button class="px-6 py-2 text-lg whitespace-nowrap">
                {{ __('Download PDF') }}
            </x-primary-button>
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
                            @php
                                $weeklyTotal = 0;
                            @endphp

                            @foreach (range(0, 6) as $dayOffset)
                                @php
                                    $date = $startOfWeek->copy()->addDays($dayOffset);
                                    $dateKey = $date->format('Y-m-d (l)');
                                    $dailyTotal = 0;
                                    $previousBoard = null;
                                    $previousGroup = null;
                                    $officeStatus = isset($officeSchedules[$date->format('Y-m-d')])?$officeSchedules[$date->format('Y-m-d')]:null;
                                    $boardCount = isset($groupedData[$dateKey]) ? count($groupedData[$dateKey]) : 0;
                                @endphp

                                @if ($date->isFuture())
                                    @continue
                                @endif

                                @if ($date->isWeekend() && !isset($groupedData[$dateKey]))
                                    @continue
                                @endif

                                <tr class="bg-gray-200 dark:bg-gray-700 font-bold">
                                    <td class="border border-gray-300 px-4 py-2 text-lg" colspan="5">
                                        {{ $dateKey }}
                                        @if ($officeStatus)
                                        <span class="px-2 py-1 ml-2 rounded text-sm
                        @if(strtolower($officeStatus) === 'office') bg-green-200 dark:bg-green-800 text-green-900 dark:text-green-200
                        @elseif(strtolower($officeStatus) === 'wfh') bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-200
                        @elseif(strtolower($officeStatus) === 'friday off') bg-yellow-200 dark:bg-yellow-800 text-yellow-900 dark:text-yellow-200
                        @endif">
                        {{ ucfirst($officeStatus) }}
                    </span>@endif
                                    </td>
                                </tr>

                                @if (isset($groupedData[$dateKey]))
                                    @foreach ($groupedData[$dateKey] as $boardName => $groups)
                                        @php
                                            $boardTotal = 0;
                                            $groupCount = count($groups);
                                        @endphp

                                        @foreach ($groups as $groupName => $tasks)
                                            @php
                                                $groupTotal = 0;
                                                $taskCount = count($tasks);
                                            @endphp

                                            @foreach ($tasks as $taskName => $entries)
                                                @php
                                                    $taskTotal = $entries->sum(fn($t) => \Carbon\Carbon::parse($t->started_at)->diffInMinutes(\Carbon\Carbon::parse($t->ended_at)));
                                                    $dailyTotal += $taskTotal;
                                                    $groupTotal += $taskTotal;
                                                    $boardTotal += $taskTotal;
                                                @endphp
                                                <tr>
                                                    <td class="border border-gray-300 px-4 py-2"></td>
                                                    <td class="border border-gray-300 px-4 py-2">
                                                        @if ($boardName !== $previousBoard)
                                                            <a href="https://paperdog-team.monday.com/boards/{{$entries->first()->item->board->id}}"
                                                               target="_blank"
                                                               class="dark:hover:text-blue-200 hover:underline transition">
                                                                {{ $boardName }}
                                                            </a>
                                                            @php $previousBoard = $boardName; @endphp
                                                        @endif
                                                    </td>
                                                    <td class="border border-gray-300 px-4 py-2">
                                                        @if ($groupName !== $previousGroup)
                                                            {{ $groupName ?? 'No Group' }}
                                                            @php $previousGroup = $groupName; @endphp
                                                        @endif
                                                    </td>
                                                    <td class="border border-gray-300 px-4 py-2">
                                                        <a href="https://paperdog-team.monday.com/boards/{{$entries->first()->item->board->id}}/pulses/{{$entries->first()->item->id}}"
                                                           target="_blank"
                                                           class="dark:text-blue-400 hover:text-blue-800 hover:underline transition">
                                                            {{ $taskName }}
                                                        </a>
                                                    </td>
                                                    <td class="border border-gray-300 px-4 py-2 text-center">
                                                        {{ floor($taskTotal / 60) }}h {{ $taskTotal % 60 }}m
                                                    </td>
                                                </tr>
                                            @endforeach

                                            @if ($taskCount > 1)
                                                <tr class="bg-gray-100 dark:bg-gray-700 font-semibold">
                                                    <td class="border border-gray-300 px-4 py-2 text-right" colspan="4">
                                                        Group Total:
                                                    </td>
                                                    <td class="border border-gray-300 px-4 py-2 text-center">
                                                        {{ floor($groupTotal / 60) }}h {{ $groupTotal % 60 }}m
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach

                                        @if ($boardCount > 1)
                                            <tr class="bg-gray-200 dark:bg-gray-800 font-bold">
                                                <td class="border border-gray-300 px-4 py-2 text-right" colspan="4">
                                                    Board Total:
                                                </td>
                                                <td class="border border-gray-300 px-4 py-2 text-center">
                                                    {{ floor($boardTotal / 60) }}h {{ $boardTotal % 60 }}m
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @else
                                    @if (!$date->isWeekend())
                                        <tr>
                                            <td class="border border-gray-300 px-4 py-2 text-center" colspan="5">
                                                No entries for this day.
                                            </td>
                                        </tr>
                                    @endif
                                @endif

                                <tr class="bg-gray-300 dark:bg-gray-700 font-bold text-lg">
                                    <td class="border border-gray-300 px-4 py-2 text-right" colspan="4">Daily Total:
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-center">
                                        {{ $dailyTotal > 0 ? floor($dailyTotal / 60) . 'h ' . ($dailyTotal % 60) . 'm' : '-' }}
                                    </td>
                                </tr>

                                @php
                                    $weeklyTotal += $dailyTotal;
                                @endphp
                            @endforeach

                            <tr class="font-bold text-xl">
                                <td class="border border-gray-300 px-4 py-2 text-right" colspan="4">Weekly Total:</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    {{ floor($weeklyTotal / 60) }}h {{ $weeklyTotal % 60 }}m
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
