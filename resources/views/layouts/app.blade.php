<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ env('APP.ENV') }}</title>

        <link rel="icon" href="{{ env('APP_LOGO') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- jQuery UI -->
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

        <script>
            $(function () {
                // Funkció a hét első napjának (hétfő) kiszámítására
                function getMonday(d) {
                    d = new Date(d);
                    var day = d.getDay(),
                        diff = d.getDate() - day + (day == 0 ? -6 : 1); // hétfő
                    return new Date(d.setDate(diff));
                }

                // Alapértelmezett dátum beállítása a hét első napjára
                var today = new Date();
                var monday = getMonday(today);

                // Datepicker inicializálása
                $("#datepicker").datepicker({
                    dateFormat: "yy-mm-dd",
                    defaultDate: monday,
                    onSelect: function (dateText) {
                        var selectedDate = new Date(dateText);
                        var monday = getMonday(selectedDate);
                        $(this).datepicker('setDate', monday); // A mező értékét átváltjuk hétfőre
                    }
                });

                // A datepicker mező értékének automatikus kitöltése a hétfői dátummal
                $("#datepicker").val($.datepicker.formatDate('yy-mm-dd', monday));
            });
        </script>
    </head>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
