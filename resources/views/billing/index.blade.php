<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Billings
        </h2>
    </x-slot>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-center gap-4 my-4">
                        <select id="user-filter" multiple="multiple" class="w-1/2">
                            @foreach ($structuredData as $username => $days)
                                <option value="{{ $username }}">{{ $username }}</option>
                            @endforeach
                        </select>
                    </div>

                    <h1 class="text-xl">Total time recorded by users</h1>
                    <table class="w-full border-collapse border border-gray-300 mt-4">
                        <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700">
                            <th class="border border-gray-300 px-4 py-2 text-left">User</th>
                            <th class="border border-gray-300 px-4 py-2 text-center">Total Time</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($userWeeklyTotals as $userTotal)
                            <tr class="{{ $userTotal->total_minutes == 0 ? 'bg-red-100' : '' }}">
                                <td class="border border-gray-300 px-4 py-2">
                                    {{ $userTotal->user_name }}
                                </td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    @if ($userTotal->total_minutes > 0)
                                        {{ floor($userTotal->total_minutes / 60) }}h {{ $userTotal->total_minutes % 60 }}m
                                    @else
                                        <span class="text-gray-500 italic">No time recorded</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>


                    <h1 class="text-xl mt-6">Total time spent on each board</h1>
                    <table class="w-full border-collapse border border-gray-300 mt-4">
                        <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700">
                            <th class="border border-gray-300 px-4 py-2 text-left">Board</th>
                            <th class="border border-gray-300 px-4 py-2 text-center">Total Time</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($boardWeeklyTotals as $boardTotal)
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">
                                    {{ $boardTotal->board_name ?? 'Unknown Board' }}
                                </td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    {{ floor($boardTotal->total_minutes / 60) }}h {{ $boardTotal->total_minutes % 60 }}m
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
