<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Office Schedule
        </h2>
    </x-slot>

    <div class="flex items-center justify-center gap-4 my-4">
        <!-- Left Arrow (Previous Week) -->
        <a href="{{ route('office-schedule', ['weekStartDate' => $startOfWeek->copy()->subWeek()->toDateString()]) }}"
           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
            ←
        </a>

        <!-- Current Week Display -->
        <span class="font-semibold text-lg dark:text-gray-300 dark:hover:text-white">
            {{ $startOfWeek->format('M d, Y') }} - {{ $endOfWeek->format('M d, Y') }}
        </span>

        <!-- Right Arrow (Next Week) -->
        <a href="{{ route('office-schedule', ['weekStartDate' => $startOfWeek->copy()->addWeek()->toDateString()]) }}"
           class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white text-2xl px-3">
            →
        </a>
    </div>



    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-center gap-4 my-4">
                        <select id="user-filter" multiple="multiple" class="w-1/2">
                            @foreach ($structuredData as $username => $days)
                                <option value="{{ $username }}">{{ $username }}</option>
                            @endforeach
                        </select>
                    </div>

                    <table class="w-full border-collapse border border-gray-300 dark:border-gray-700">
                        <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700">
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-left" width="60"></th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-left">Name</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Monday</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Tuesday</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Wednesday</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Thursday</th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">Friday</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($structuredData as $username => $days)
                            @php
                                $flagPath = "/images/flag-{$locations[$username]}.svg";
                            @endphp
                            <tr class="border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center">
                                    @if(file_exists(public_path($flagPath)))
                                        <img src="{{ asset($flagPath) }}" alt="{{ $locations[$username] }}" class="w-6 h-4">
                                    @endif
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{{ $username }}</td>
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center
                                            @if(strtolower($days[$day]) == 'office') bg-green-200 dark:bg-green-800
                                            @elseif(strtolower($days[$day]) == 'wfh') bg-blue-200 dark:bg-blue-800
                                            @elseif(strtolower($days[$day]) == 'friday off') bg-yellow-200 dark:bg-yellow-800
                                            @endif">
                                        {{ ucfirst($days[$day]) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#user-filter').select2({
            placeholder: "Choose user",
            allowClear: true,
            width: 'resolve'
        });

        function applyTailwindStyles() {
            $('.select2-container--default .select2-selection--multiple')
                .addClass('border border-gray-300 rounded-lg shadow-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200');

            $('.select2-selection__choice')
                .addClass('bg-indigo-600 text-white rounded-full px-3 py-1 m-1 text-sm shadow-md');

            $('.select2-search__field')
                .addClass('w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200');

            $('.select2-results__option')
                .addClass('px-3 py-2 cursor-pointer hover:bg-indigo-500 hover:text-white transition');

            $('.select2-results__option--highlighted')
                .addClass('bg-indigo-600 text-white');

            $('.select2-dropdown')
                .addClass('rounded-lg shadow-lg dark:bg-gray-800 dark:text-gray-200');
        }

        applyTailwindStyles();

        $('#user-filter').on('select2:open select2:select select2:unselect', function() {
            applyTailwindStyles();
        });

        $('#user-filter').on('change', function() {
            let selectedUsers = $(this).val();
            let rows = $('tbody tr');

            if (!selectedUsers || selectedUsers.length === 0) {
                rows.show();
                return;
            }

            rows.each(function() {
                let username = $(this).find('td:nth-child(2)').text().trim();
                if (selectedUsers.includes(username)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });
</script>

<style>
    /* Dropdown alap */
    .select2-container--default .select2-selection--multiple {
        background-color: #fff;
        border: 1px solid #d1d5db; /* Tailwind gray-300 */
        border-radius: 0.5rem; /* Lekerekített sarkok */
        padding: 0.5rem;
        min-height: 3rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Árnyék */
    }

    .dark .select2-container--default .select2-selection--multiple {
        background-color: #1f2937; /* Sötét háttér */
        border-color: #374151; /* Tailwind gray-700 */
        color: #e5e7eb; /* Tailwind gray-200 */
    }

    /* Kijelölt felhasználók (chip dizájn) */
    .select2-selection__choice {
        background-color: #4f46e5; /* Tailwind indigo-600 */
        color: #fff;
        border: none;
        border-radius: 0.375rem; /* Lekerekített kapszulák */
        padding: 0.25rem 0.5rem;
        margin: 0.25rem;
        font-size: 0.875rem;
    }

    .dark .select2-selection__choice {
        background-color: #6366f1; /* Sötét indigo */
        color: #e5e7eb;
    }

    /* Kereső mező */
    .select2-search__field {
        padding: 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid #d1d5db;
        margin-bottom: 0.5rem;
    }

    .dark .select2-search__field {
        background-color: #374151;
        border-color: #4b5563;
        color: #e5e7eb;
    }

    /* Dropdown menü */
    .select2-dropdown {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        padding: 0.5rem;
    }

    .dark .select2-dropdown {
        background-color: #1f2937;
        color: #e5e7eb;
    }

    /* Lebegés kiemelése */
    .select2-results__option--highlighted {
        background-color: #4f46e5;
        color: white;
    }

    /* Görgetősáv (opcionális) */
    .select2-results__options {
        max-height: 200px;
        overflow-y: auto;
    }

    /* Scrollbar stílus (Chrome, Edge, Safari) */
    .select2-results__options::-webkit-scrollbar {
        width: 8px;
    }

    .select2-results__options::-webkit-scrollbar-thumb {
        background-color: #4f46e5;
        border-radius: 4px;
    }
</style>


