<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Recorded time
        </h2>
    </x-slot>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <strong>Date:</strong> {{ $date }}<br><br>
            @foreach($tasks as $taskId => $task)
                <p>
                    <strong>Task ID:</strong> {{ $taskId }}<br>
                    <strong>Board ID:</strong> {{ $task->board->id ?? 'N/A' }}<br>
                    <strong>Time Tracking Column:</strong> {{ $time_tracking_columns[$taskId] ?? 'N/A' }}<br>
                    <strong>Start Time:</strong> {{ $start_times[$taskId] ?? 'N/A' }}<br>
                    <strong>End Time:</strong> {{ $end_times[$taskId] ?? 'N/A' }}<br>
                </p>
                <hr>
            @endforeach
        </div>
    </div>
</x-app-layout>
