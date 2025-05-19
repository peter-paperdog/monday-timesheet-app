@php use Illuminate\Support\Carbon; @endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Time Tracking Entries
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        @if(auth()->user()->admin)
            <form method="get" action="{{ route('timesheets.timetracking') }}" class="flex flex-wrap gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">User</label>
                    <select name="user_id" class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        @foreach($users as $userOption)
                            <option value="{{ $userOption->id }}" {{ $selectedUserId == $userOption->id ? 'selected' : '' }}>
                                {{ $userOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                    <input type="date" name="start" value="{{ $start->toDateString() }}"
                           class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                    <input type="date" name="end" value="{{ $end->toDateString() }}"
                           class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                </div>

                <div class="flex items-end">
                    <x-primary-button class="px-6 py-2 text-lg whitespace-nowrap">Filter</x-primary-button>
                </div>
            </form>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Board</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-300 dark:divide-gray-700">
                @foreach($trackings as $entry)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->started_at->format('Y-m-d') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->user->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->item->board->name ?? 'Unknown Board' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->item->name ?? 'Unknown Task' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->started_at->format('H:i') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->ended_at?->format('H:i') ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            @if($entry->ended_at)
                                @php $duration = $entry->started_at->diffInMinutes($entry->ended_at); @endphp
                                {{ floor($duration / 60) }}h {{ $duration % 60 }}m
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if($trackings->isEmpty())
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    No time tracking entries found for the selected range.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
