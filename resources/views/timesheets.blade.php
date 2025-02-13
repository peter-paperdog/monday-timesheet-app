<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Weekly timesheets
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-5">
        <div class="text-gray-900 dark:text-gray-100">
            <form method="get" action="{{ route('timesheets') }}" class="space-y-6">
                <div class="flex gap-4">
                    <input type="text" id="datepicker" name="weekStartDate"
                           class="w-auto px-4 py-2 border rounded-lg shadow-sm text-gray-900 dark:text-gray-100 dark:bg-gray-700 dark:border-gray-600"
                           placeholder="Select date" value="{{ $selectedDate }}"  readonly>

                    <x-primary-button class="px-6 py-2 text-lg whitespace-nowrap">{{ __('Search') }}</x-primary-button>
                </div>
            </form>
        </div>
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
