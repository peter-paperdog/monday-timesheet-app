<style>


    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 5px;
        margin-bottom: 10px;
    }

    table, th, td {
        border: 1px solid #ddd;
    }

    th, td {
        padding: 4px 6px;
        text-align: left;
    }

    th {
        background-color: #f4f4f4;
    }

    .total-column {
        width: 70px;
        text-align: right;
    }

    .project-total, .item-total {
        text-align: right;
        font-weight: bold;
    }

    .project-total, .sub-item-total {
        text-align: right;
        font-style: italic;
    }

    .final-total {
        font-size: 14px;
        text-align: right;
        margin-top: 20px;
        font-weight: bold;
    }

    .day-header {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ddd;
        padding-bottom: 3px;
    }

    .logo {
        max-width: 80px;
        height: auto;
        margin-bottom: 10px;
    }

    .footer {
        position: fixed;
        left: 0;
        bottom: 0;
        width: 100%;
        text-align: right;
        font-size: 8px;
        color: #666;
        border-top: 1px solid #ddd;
        padding-top: 3px;
    }
</style>
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
                    <form method="get" action="{{ route('sheets.search') }}" class="mt-6 space-y-6">
                        <select name="email" id="user" class="form-control input-sm">
                            @foreach($users as $user)
                                <option value="{{ $user['email'] }}" {{ $user['email'] == $userMail ? 'selected' : '' }}>
                                    {{ $user['name'] }}
                                </option>
                            @endforeach

                        </select>

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
                    <p style="text-align: center; padding: 0; margin: 0; font-style: italic; padding-bottom: 12px;">{{ $data['startOfWeek'] }}
                        - {{ $data['endOfWeek'] }}</p>
                    <h1 style="text-align: center; padding: 0; margin: 0; font-style: italic; padding-bottom: 12px;">{{ $data['name'] }}</h1>
                    <form action="{{ route('download.sheet') }}" method="post" target="_blank">
                        @csrf
                        <input type="hidden" name="data" value="{{ json_encode($data) }}">
                        <x-primary-button>Download</x-primary-button>
                    </form>
                    @foreach($data['days'] as $day)
                        <div class="day-header">
                            <h2>{{ $day['day'] }}, {{ $day['date'] }}</h2>
                            <span class="project-total">Total: {{ round($day['time'] / 3600, 2) }} hours</span>
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
                        Total for the Week: <strong>{{ round($data['time'] / 3600, 2) }} hours</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
