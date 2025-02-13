<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="mb-6" style="font-size: 1.3em">Hello {{Auth::User()->name}}!</h1>

                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">
                        These are your tasks in Monday.com that are currently in progress:
                    </p>

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
                                <td class="border border-gray-300 px-4 py-2">{{ $item->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
