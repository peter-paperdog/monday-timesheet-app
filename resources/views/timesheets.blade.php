<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Weekly timesheets
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="get" action="{{ route('timesheets') }}" class="mt-6 space-y-6">
                        <input type="text" id="datepicker" name="weekStartDate" readonly="readonly">
                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Search') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 style="text-align: center; padding: 0; margin: 0; font-style: italic; padding-bottom: 12px;">
                        Weekly Timesheet</h1>
                    <p style="text-align: center; padding: 0; margin: 0; font-style: italic; padding-bottom: 12px;">startOfWeek - endOfWeek</p>
                    <h1 style="text-align: center; padding: 0; margin: 0; font-style: italic; padding-bottom: 12px;" class="day-header-days">name</h1>
                    <form action="{{ route('download.sheet') }}" method="post" target="_blank">
                        @csrf
                        <input type="hidden" name="data" value="0">
                        <x-primary-button>Download PDF</x-primary-button>
                    </form>
                    <form action="{{ route('download.sheetcsv') }}" method="post" target="_blank">
                        @csrf
                        <input type="hidden" name="data" value="0">
                        <x-primary-button>Download CSV</x-primary-button>
                    </form>
                    @foreach([] as $day)
                        <div class="day-header">
                            <h1 class="day-header-days">{{ $day['day'] }}, {{ $day['date'] }}</h1>
                            <span class="project-total day-header-days">Total: X hours</span>
                        </div>

                        <table>
                            <thead>
                            <tr>
                                <th>Project/Task</th>
                                <th class="total-column">Total Hours</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($day['boards'] as $board)
                                <tr>
                                    <td colspan="2"><strong>{{ $board['name'] }}</strong></td>
                                </tr>
                                @foreach($board['groups'] as $group_name => $group)
                                    <tr>
                                        <td><strong>{{ $group_name }}</strong></td>
                                        <td class="item-total">{{ round($group['duration'] / 3600, 2) }} hours</td>
                                    </tr>
                                    @foreach($group['items'] as $item)
                                        <tr>
                                            <td>{{ $item['item_name'] }}</td>
                                            <td class="sub-item-total">{{ round($item['duration'] / 3600, 2) }}hours
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                <tr>
                                    <td class="project-total">Total for {{ $board['name'] }}</td>
                                    <td class="project-total">{{ round($board['duration'] / 3600, 2) }} hours</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endforeach
                    <div class="final-total">
                        Total for the Week: <strong>X hours</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
