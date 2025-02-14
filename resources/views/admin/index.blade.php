<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin
        </h2>
    </x-slot>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-center gap-4 my-4">
                        <!-- Left Arrow (Previous Week) -->
                        <a href="{{ route('admin.index', ['weekStartDate' => $startOfWeek->copy()->subWeek()->toDateString()]) }}"
                           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
                            ←
                        </a>

                        <!-- Current Week Display -->
                        <span class="font-semibold text-lg">
        {{ $startOfWeek->format('M d, Y') }} - {{ $endOfWeek->format('M d, Y') }}
    </span>

                        <!-- Right Arrow (Next Week) -->
                        <a href="{{ route('admin.index', ['weekStartDate' => $startOfWeek->copy()->addWeek()->toDateString()]) }}"
                           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
                            →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
