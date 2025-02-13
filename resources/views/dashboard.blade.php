<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Dashboard
        </h2>
    </x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 text-right text-gray-400 mt-2 font-extralight" id="lastupdated">
        Last updated: {{$lastupdated}}
    </div>

    @if(auth()->user()->admin)
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-5">
            <div class="text-gray-900 dark:text-gray-100">
                <form method="get" action="{{ route('dashboard') }}" class="flex items-center gap-4">
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

    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="text-xl mb-6" style="font-size: 1.3em">Hello {{Auth::User()->name}}!</h1>

                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">
                        Below are your tasks that are currently in progress.<br/>
                        Click on a task name to open it directly in Monday.com.
                    </p>
                    @if($items->isEmpty())
                        <p class="text-center text-gray-500 dark:text-gray-400 py-6">
                            No tasks in progress.
                        </p>
                    @else
                        <table class="w-full border-collapse border border-gray-300">
                            <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="border border-gray-300 px-4 py-2 text-left">Board</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Task</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $previousBoard = null;
                            @endphp

                            @foreach($items as $item)
                                <tr class="border border-gray-300">
                                    <td class="border border-gray-300 px-4 py-2">
                                        @if($item->board->name !== $previousBoard)
                                            {{ $item->board->name }}
                                            @php $previousBoard = $item->board->name; @endphp
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <a href="https://paperdog-team.monday.com/boards/{{$item->board->id}}/pulses/{{$item->id}}"
                                           target="_blank"
                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 hover:underline transition">
                                            {{ $item->name }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
