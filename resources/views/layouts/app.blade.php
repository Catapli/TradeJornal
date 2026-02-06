<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1">
        <meta name="csrf-token"
              content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TradeForge') }}</title>
        <link rel="icon"
              href="{{ asset('img/favicon/logo_only.ico') }}"
              type="image/x-icon">
        <!-- Fonts -->
        <link rel="preconnect"
              href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
              rel="stylesheet" />

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <link rel="icon"
              href="{{ asset('img/favicon/logo_only.ico') }}"
              type="image/x-icon">


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>

    <livewire:trade-detail-modal />




    <body class="overflow-x-hidden font-sans antialiased">
        <livewire:language-manager />
        <x-banner />

        <div class="bg-white">
            @livewire('navigation-menu')

            @include('sidebar-menu')

            @livewire('trade-toast') {{-- El espía invisible --}}

            <!-- NOTIFICACIONES FLASH (Éxito/Error) -->
            <div class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6"
                 aria-live="assertive">
                <div class="flex w-full flex-col items-center space-y-4 sm:items-end">

                    <!-- Mensaje de ÉXITO (status) -->
                    @if (session('status'))
                        <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                             x-data="{ show: true }"
                             x-show="show"
                             x-transition:enter="transform ease-out duration-300 transition"
                             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             x-init="setTimeout(() => show = false, 5000)">
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <!-- Icono Check Verde -->
                                        <i class="fa-solid fa-circle-check text-xl text-emerald-400"></i>
                                    </div>
                                    <div class="ml-3 w-0 flex-1 pt-0.5">
                                        <p class="text-sm font-medium text-gray-900">¡Hecho!</p>
                                        <p class="mt-1 text-sm text-gray-500">{{ session('status') }}</p>
                                    </div>
                                    <div class="ml-4 flex flex-shrink-0">
                                        <button class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                @click="show = false">
                                            <span class="sr-only">Cerrar</span>
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Mensaje de ERROR (error) -->
                    @if (session('error'))
                        <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border-l-4 border-red-500 bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                             x-data="{ show: true }"
                             x-show="show"
                             x-transition:enter="transform ease-out duration-300 transition"
                             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                             x-init="setTimeout(() => show = false, 6000)">
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fa-solid fa-circle-exclamation text-xl text-red-400"></i>
                                    </div>
                                    <div class="ml-3 w-0 flex-1 pt-0.5">
                                        <p class="text-sm font-medium text-gray-900">Atención</p>
                                        <p class="mt-1 text-sm text-gray-500">{{ session('error') }}</p>
                                    </div>
                                    <div class="ml-4 flex flex-shrink-0">
                                        <button class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500"
                                                @click="show = false">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>



            @if (isset($header))
                <header class="relative top-0 z-10 ml-12 mt-[55px] w-auto bg-white shadow">
                    <div class="flex min-h-11 max-w-7xl items-center space-x-1.5 px-4 py-1 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif



            {{-- Page Content --}}
            <main class="ml-20 min-h-screen transition-all duration-300">
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
