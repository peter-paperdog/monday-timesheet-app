<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Epic time trackings
        </h2>
    </x-slot>


    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Person</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stakeholder</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Hours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hourly rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-300 dark:divide-gray-700">
                @foreach($trackings as $entry)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->name}}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->user}}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $entry->hours}}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">$100</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${{ $entry->hours * 100}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
