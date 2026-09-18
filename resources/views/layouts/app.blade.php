<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1">
        <meta name="csrf-token"
              content="{{ csrf_token() }}">

        {{-- Tema: aplica la clase 'dark' antes del primer render para evitar parpadeo (FOUC).
             Preferencia guardada en localStorage; la primera vez sigue al sistema operativo. --}}
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

    <livewire:trade-detail-modal />

    <body class="overflow-x-hidden font-sans antialiased">
        <livewire:language-manager />
        <x-banner />

        <div class="bg-white transition-colors duration-300 dark:bg-gray-900">
            @livewire('navigation-menu')

            @include('sidebar-menu')

            @livewire('trade-toast') {{-- El espía invisible --}}

            {{-- BANNER DE DEMO: la sesión pública de solo lectura tiene que ser
                 evidente en todo momento, y llevar de vuelta al registro. --}}
            @if (\App\Support\Demo::active())
                <div class="fixed inset-x-0 bottom-0 z-[60] border-t border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-950/95">
                    <div class="mx-auto flex max-w-5xl flex-col items-center gap-3 sm:flex-row sm:justify-between">
                        <p class="flex items-center gap-2 text-sm font-medium text-amber-900 dark:text-amber-200">
                            <i class="fa-solid fa-eye"></i>
                            {{ __('landing.demo.banner') }}
                        </p>
                        <div class="flex shrink-0 items-center gap-2">
                            <a class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-amber-700"
                               href="{{ route('register') }}">{{ __('landing.demo.banner_cta') }}</a>
                            <a class="rounded-lg px-3 py-2 text-sm font-semibold text-amber-800 underline transition hover:text-amber-950 dark:text-amber-300 dark:hover:text-amber-100"
                               href="{{ route('demo.exit') }}">{{ __('landing.demo.banner_exit') }}</a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- NOTIFICACIONES FLASH (Éxito/Error) → toast unificado (core/notify.js) --}}
            @if (session('status') || session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        @if (session('status'))
                            window.tjToast(@json(session('status')), 'success');
                        @endif
                        @if (session('error'))
                            window.tjToast(@json(session('error')), 'error');
                        @endif
                    });
                </script>
            @endif



            @if (isset($header))
                <header class="relative top-0 z-10 ml-12 mt-[55px] w-auto bg-white shadow transition-colors dark:bg-gray-800 dark:shadow-black/30">
                    <div class="flex min-h-11 max-w-7xl items-center space-x-1.5 px-4 py-1 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif



            {{-- Page Content --}}
            {{-- pb-28 en demo: deja hueco al banner fijo del pie. --}}
            <main @class([
                'ml-20 min-h-screen transition-all duration-300',
                'pb-28' => \App\Support\Demo::active(),
            ])>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')
        @stack('scripts')

        @livewireScripts
    </body>

    {{-- Solo datos de Blade hacia JS; la lógica vive en resources/js/core/ --}}
    <script>
        window.translations = @json($translations);

        // Destinos de los atajos de teclado (resources/js/core/shortcuts.js).
        // Llegan en variable desde AppLayout: la directiva de serialización parte
        // su argumento por comas, así que un array literal aquí no compilaría.
        window.tjRoutes = @json($shortcutRoutes);
    </script>

</html>
