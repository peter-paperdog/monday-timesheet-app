<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Weekly Time Tracking Calendar
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
                <form method="get" action="{{ route('timesheets.calendar') }}" class="flex items-center gap-4">
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

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-5">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div id="calendar" data-user-id="{{ $selectedUserId }}"></div>
            </div>
        </div>
    </div>

</x-app-layout>
