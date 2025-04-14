<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin
        </h2>
    </x-slot>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900 dark:text-gray-100 pt-3 my-3">
                    <form method="GET" action="{{ route('invoicing.create') }}" id="invoicing_form">
                        @csrf

                        <!-- Clients Dropdown -->
                        <div>
                            <label for="contact-dropdown"
                                   class="block mb-1 text-sm font-medium text-gray-800 dark:text-gray-200">Client</label>
                            <select name="client" id="client-dropdown"
                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Projects Dropdown -->
                        <div class="my-3">
                            <label for="project-dropdown"
                                   class="block mb-1 text-sm font-medium text-gray-800 dark:text-gray-200">Project</label>
                            <select name="project" id="project-dropdown"
                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="-1">All</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="folders" id="folder-ids">

                        <button type="submit"
                                class="px-4 py-2 bg-gray-500 hover:bg-gray-950 text-white bg-blend-darken font-semibold rounded">
                            Create Invoice
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        const folders = @json($folders);
        const form = document.getElementById('invoicing_form');
        const folderInput = document.getElementById('folder-ids');
        const clientSelect = document.getElementById('client-dropdown');
        const projectSelect = document.getElementById('project-dropdown');

        form.addEventListener('submit', function (e) {
            const selectedClientId = clientSelect.value;
            const selectedProjectId = projectSelect.value;
            let folderIds = [];

            // If specific project selected
            if (selectedProjectId !== "-1") {
                if (
                    folders[selectedClientId] &&
                    folders[selectedClientId][selectedProjectId]
                ) {
                    folderIds = folders[selectedClientId][selectedProjectId];
                }
            } else {
                // Collect all folders under the selected client
                if (folders[selectedClientId]) {
                    Object.values(folders[selectedClientId]).forEach(folderArray => {
                        folderIds.push(...folderArray);
                    });
                }
            }

            // Set hidden input value
            folderInput.value = folderIds.join(',');
        });
    </script>
</x-app-layout>
