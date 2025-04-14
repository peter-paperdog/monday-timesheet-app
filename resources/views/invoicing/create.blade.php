<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <a href="{{ url('/invoicing') }}">Invoicing</a> / {{ $clientName }} @if($projectName)
                / {{ $projectName }}
            @endif
        </h2>
    </x-slot>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 pt-3 my-3">
                    <h1 class="text-xl">Client: {{ $clientName }}</h1>
                    @if($projectName)
                        <h1 class="text-xl">Project: {{ $projectName }}</h1>
                    @endif
                </div>
                <div class="p-6 text-gray-900 dark:text-gray-100 pt-3 my-3">
                    @foreach ($boards as $board)
                        <h1>{{ $board['name'] }}</h1>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
