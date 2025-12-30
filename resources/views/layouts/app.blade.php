<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1">
        <meta name="csrf-token"
              content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Cloud Detrafic') }}</title>
        <link rel="icon"
              href="{{ asset('img/favicon/favicon.ico') }}"
              type="image/x-icon">
        <!-- Fonts -->
        <link rel="preconnect"
              href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
              rel="stylesheet" />

        <link rel="icon"
              href="{{ asset('img/favicon/favicon.ico') }}"
              type="image/x-icon">


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>

    <body class="font-sans antialiased">
        <livewire:language-manager />
        <x-banner />

        <div class="min-h-screen bg-white">
            @livewire('navigation-menu')

            @include('sidebar-menu')


            @if (isset($header))
                <header class="fixed z-10 ml-12 mt-[55px] w-full bg-white shadow">
                    <div class="flex max-w-7xl items-center space-x-1.5 px-4 py-3 sm:px-6 lg:px-8">
                        <img class="h-8"
                             src="{{ asset('img/detrafic_logo_gradient.png') }}"
                             alt="">
                        <span class="text-2xl text-gray-500">></span>
                        {{ $header }}
                    </div>
                </header>
            @endif



            {{-- Page Content --}}
            <main class="ml-12 mt-28">
                {{ $slot }}
            </main>
        </div>

        @stack('modals')
        @stack('scripts')

        @livewireScripts
    </body>

    <script>
        window.translations = @json($translations);
    </script>

</html>
