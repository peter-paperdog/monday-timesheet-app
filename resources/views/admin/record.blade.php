<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Record time
        </h2>
    </x-slot>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('admin.monday.recordtime') }}">
                        @csrf
                        <!-- Date Picker -->
                        <div class="mb-4">
                            <label for="date" class="block text-gray-700 dark:text-gray-300 font-semibold mb-1">Select
                                Date:</label>
                            <input type="date" name="date" id="date"
                                   class="border rounded px-3 py-2 w-64 dark:bg-gray-700 dark:text-white"
                                   value="{{ now()->format('Y-m-d') }}">
                        </div>

                        <table class="w-full border-collapse border border-gray-300">
                            <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="border border-gray-300 px-4 py-2 text-center">Start Time</th>
                                <th class="border border-gray-300 px-4 py-2 text-center">End Time</th>
                                <th class="border border-gray-300 px-4 py-2 text-center">Total Time</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Board</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Group</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Task</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tasks as $task)
                                <tr class="border border-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <td class="border border-gray-300 px-4 py-2">
                                        <input type="time" name="start_time[{{ $task->id }}]"
                                               class="start-time w-full px-2 py-1 border rounded"
                                               data-task-id="{{ $task->id }}">
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <input type="time" name="end_time[{{ $task->id }}]"
                                               class="end-time w-full px-2 py-1 border rounded"
                                               data-task-id="{{ $task->id }}">
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <span class="total-time" id="total-time-{{ $task->id }}">-</span>
                                    </td>

                                    <td class="border border-gray-300 px-4 py-2">
                                        {{ $task->board->name }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        {{ $task->group->name ?? 'No Group' }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        {{ $task->name }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>


                        <!-- Submit Button -->
                        <div class="mt-4">
                            <x-primary-button type="submit">
                                Submit Time Records
                            </x-primary-button>
                        </div>
                    </form>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            function calculateTotalTime(taskId) {
                                const startTimeInput = document.querySelector(`.start-time[data-task-id="${taskId}"]`);
                                const endTimeInput = document.querySelector(`.end-time[data-task-id="${taskId}"]`);
                                const totalTimeField = document.getElementById(`total-time-${taskId}`);

                                if (startTimeInput.value && endTimeInput.value) {
                                    const startTime = new Date(`1970-01-01T${startTimeInput.value}:00`);
                                    const endTime = new Date(`1970-01-01T${endTimeInput.value}:00`);

                                    if (endTime > startTime) {
                                        const diffMs = endTime - startTime;
                                        const diffMins = Math.floor(diffMs / 60000);
                                        const hours = Math.floor(diffMins / 60);
                                        const minutes = diffMins % 60;

                                        totalTimeField.textContent = `${hours}h ${minutes}m`;
                                    } else {
                                        totalTimeField.textContent = '-';
                                    }
                                } else {
                                    totalTimeField.textContent = '-';
                                }
                            }

                            document.querySelectorAll('.start-time, .end-time').forEach(input => {
                                input.addEventListener('change', function () {
                                    const taskId = this.getAttribute('data-task-id');
                                    calculateTotalTime(taskId);
                                });
                            });
                        });
                    </script>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
