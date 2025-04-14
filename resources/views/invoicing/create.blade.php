<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <a href="{{ url('/invoicing') }}">Invoicing</a> / Client
        </h2>
    </x-slot>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 pt-3 my-3">
                    <p><strong>Client ID:</strong> {{ request('client') }}</p>
                    <p><strong>Project ID:</strong> {{ request('project') }}</p>
                    <p><strong>Folder IDs:</strong> {{ request('folders') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
