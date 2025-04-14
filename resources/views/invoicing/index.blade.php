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


                        <form method="POST" action="{{ route('invoicing.store') }}">
                            @csrf

                            <!-- Clients Dropdown -->
                            <div>
                                <label for="contact-dropdown" class="block mb-1 text-sm font-medium text-gray-800 dark:text-gray-200">Client</label>
                                <select id="contact-dropdown" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Projects Dropdown -->
                            <div class="my-3">
                                <label for="project-dropdown" class="block mb-1 text-sm font-medium text-gray-800 dark:text-gray-200">Project</label>
                                <select id="project-dropdown" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @foreach($projects as $project)
                                        <option>{{ $project }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Contacts Dropdown -->
                            <div>
                                <label for="contact-dropdown" class="block mb-1 text-sm font-medium text-gray-800 dark:text-gray-200">Contact</label>
                                <select id="contact-dropdown" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @foreach($contacts as $contact)
                                        <option>{{ $contact }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit"
                                    class="px-4 py-2 bg-gray-300 hover:bg-gray-700 text-white bg-blend-darken font-semibold rounded">
                                Create Invoice
                            </button>
                        </form>
                    </div>

            </div>
        </div>
    </div>
</x-app-layout>
