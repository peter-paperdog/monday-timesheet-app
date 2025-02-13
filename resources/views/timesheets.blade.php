<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Weekly timesheets
        </h2>
    </x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 text-right text-gray-400 mt-2 font-extralight" id="lastupdated">
        Last updated: {{$lastupdated}}
        <br>
        <a href="{{ route('sync.boards') }}"
           class="text-sm py-2 bg-blue-600 hover:bg-blue-700 hover:underline  transition">
            Update
        </a>
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
                    @if($timeTrackings->isEmpty())
                        <p style="text-align: center; font-weight: bold; padding: 20px;">
                            No time tracking records found.
                        </p>
                    @else
                        <table>
                            <tbody>
                            @php
                                $currentDate = null;
                                $previousBoard = null;
                                $previousTask = null;
                                $dailyTotal = 0;
                                $weeklyTotal = 0;

                                  function formatTime($minutes) {
                                        $hours = floor($minutes / 60);
                                        $remainingMinutes = $minutes % 60;
                                        return ($hours > 0 ? "{$hours} hours " : "") . ($remainingMinutes > 0 ? "{$remainingMinutes} minutes" : "");
                                    }
                            @endphp

                            @foreach($timeTrackings as $timeTracking)
                                @php
                                    $entryDate = \Carbon\Carbon::parse($timeTracking->started_at)->format('Y-m-d (l)');
                                    $currentBoard = $timeTracking->item->board->name ?? null;
                                    $currentTask = $timeTracking->item->name ?? null;
                                    $duration = ($timeTracking->started_at && $timeTracking->ended_at)
                                        ? \Carbon\Carbon::parse($timeTracking->started_at)->diffInMinutes(\Carbon\Carbon::parse($timeTracking->ended_at))
                                        : 0;
                                @endphp

                                {{-- If date changes, show total row for the previous day --}}
                                @if ($currentDate !== null && $currentDate !== $entryDate)
                                    <tr style="font-weight: bold; background-color: #e3e3e3;">
                                        <td colspan="4"></td>
                                        <td class="text-right">{{ formatTime($dailyTotal) }}</td>
                                        <td colspan="4"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="9" height="20"></td>
                                    </tr>

                                    @php
                                        $weeklyTotal += $dailyTotal; // Add to weekly total
                                        $dailyTotal = 0; // Reset daily total for new day
                                        $previousBoard = null;
                                        $previousTask = null;
                                    @endphp
                                @endif

                                {{-- Show the date header only when the date changes --}}
                                @if ($currentDate !== $entryDate)
                                    <tr>
                                        <td colspan="9"
                                            style="font-size: 1.4em; font-weight: bold; background-color: #f1f1f1; padding: 8px;">
                                            {{ $entryDate }}
                                        </td>
                                    </tr>
                                    @php
                                        $currentDate = $entryDate;
                                    @endphp
                                @endif

                                @php
                                    $dailyTotal += $duration;
                                @endphp

                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($timeTracking->started_at)->format('H:i:s') }}</td>
                                    <td width="20" class="text-center">-</td>
                                    <td>{{ \Carbon\Carbon::parse($timeTracking->ended_at)->format('H:i:s') }}</td>
                                    <td width="20"></td>
                                    <td class="text-right">
                                        {{ (int)$duration }} min
                                    </td>
                                    <td width="20"></td>

                                    {{-- Only show Board if it’s different from the previous row --}}
                                    <td>
                                        @if ($currentBoard !== $previousBoard)
                                            {{ $currentBoard }}
                                            @php $previousBoard = $currentBoard; @endphp
                                        @endif
                                    </td>

                                    <td width="20"></td>

                                    {{-- Only show Task if it’s different from the previous row --}}
                                    <td>
                                        @if ($currentTask !== $previousTask)
                                            {{ $currentTask }}
                                            @php $previousTask = $currentTask; @endphp
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Show total row for the last day --}}
                            @if ($currentDate !== null)
                                <tr style="font-weight: bold; background-color: #e3e3e3;">
                                    <td colspan="4"></td>
                                    <td class="text-right">{{formatTime($dailyTotal)}}</td>
                                    <td colspan="4"></td>
                                </tr>
                                @php
                                    $weeklyTotal += $dailyTotal; // Add last day's total to weekly total
                                @endphp
                            @endif

                            <tr>
                                <td colspan="9" height="20"></td>
                            </tr>
                            {{-- Show Weekly Total at the end --}}
                            <tr style="font-weight: bold; background-color: #d1d1d1; font-size: 1.3em;">
                                <td colspan="4"></td>
                                <td class="text-right">{{ formatTime($weeklyTotal)  }}</td>
                                <td colspan="4"></td>
                            </tr>
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
