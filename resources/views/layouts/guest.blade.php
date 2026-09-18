<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1">
        <meta name="csrf-token"
              content="{{ csrf_token() }}">

        {{-- Tema: misma lógica que layouts/app.blade.php. Sin esto el tema se
             perdía al salir a login/registro (el usuario veía un flash blanco). --}}
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme');
                    if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <title>{{ config('app.name', 'TradeForge') }}</title>

        <link rel="icon"
              href="{{ asset('img/favicon/logo_only.ico') }}"
              type="image/x-icon">

        @include('partials.pwa-head')

        <!-- Fonts -->
        <link rel="preconnect"
              href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
              rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>

    <body class="bg-white transition-colors duration-300 dark:bg-gray-900">
        <div class="font-sans text-gray-900 antialiased dark:text-gray-100">
            <livewire:language-manager />

            {{-- El LanguageManager ya estaba montado aquí, pero sin disparador visible:
                 en las pantallas de auth no había forma de cambiar de idioma, porque el
                 selector vivía solo en navigation-menu (layout autenticado). --}}
            <div class="fixed right-4 top-4 z-50">
                <x-language-selector />
            </div>

            {{ $slot }}
        </div>

        @livewireScripts
    </body>

</html>
