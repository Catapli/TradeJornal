{{-- Renderizado por App\View\Components\PublicLayout, que expone $title y $description. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1">
        <meta name="csrf-token"
              content="{{ csrf_token() }}">

        {{-- Tema: misma lógica que layouts/app y layouts/guest. Sin esto la landing
             parpadea en blanco antes de aplicar el modo oscuro. --}}
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

        @php
            $metaTitle = $title ? $title . ' · ' . config('app.name', 'TradeForge') : config('app.name', 'TradeForge') . ' — ' . __('landing.hero.badge');
            $metaDescription = $description ?: __('landing.hero.subtitle');
        @endphp

        <title>{{ $metaTitle }}</title>
        <meta name="description"
              content="{{ $metaDescription }}">

        {{-- Open Graph: la landing se comparte en Discord y X, donde la tarjeta
             de previsualización decide si alguien pincha o no. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'TradeForge') }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('img/logo_trader_h.png') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">

        <link rel="canonical" href="{{ url()->current() }}">
        <link rel="icon"
              href="{{ asset('img/favicon/logo_only.ico') }}"
              type="image/x-icon">

        @include('partials.pwa-head')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap"
              rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>

    <body class="bg-white font-sans text-gray-900 antialiased transition-colors duration-300 dark:bg-gray-950 dark:text-gray-100">

        <livewire:language-manager />

        {{-- ══════════════════════════════════════════════════════════
             CABECERA
        ══════════════════════════════════════════════════════════ --}}
        <header class="sticky top-0 z-50 border-b transition-all duration-300"
                x-data="{ scrolled: false, mobile: false }"
                @scroll.window="scrolled = window.scrollY > 12"
                :class="scrolled
                    ? 'border-gray-200/80 bg-white/85 backdrop-blur-lg dark:border-gray-800 dark:bg-gray-950/85'
                    : 'border-transparent bg-transparent'">

            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

                {{-- Logo --}}
                <a class="flex shrink-0 items-center"
                   href="{{ route('landing') }}">
                    <img class="h-9 w-auto object-contain dark:hidden"
                         src="{{ asset('img/logo_trader_h.webp') }}"
                         alt="{{ config('app.name', 'TradeForge') }}">
                    <img class="hidden h-9 w-auto object-contain brightness-0 invert dark:block"
                         src="{{ asset('img/logo_trader_h.webp') }}"
                         alt="{{ config('app.name', 'TradeForge') }}">
                </a>

                {{-- Enlaces de sección (solo escritorio) --}}
                <nav class="hidden items-center gap-1 lg:flex">
                    <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                       href="{{ route('landing') }}#modulos">{{ __('landing.nav.modules') }}</a>
                    <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                       href="{{ route('landing') }}#propfirms">{{ __('landing.nav.propfirm') }}</a>
                    <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                       href="{{ route('pricing') }}">{{ __('landing.nav.pricing') }}</a>
                </nav>

                {{-- Acciones --}}
                <div class="flex items-center gap-2">

                    <div class="hidden sm:block">
                        <x-language-selector />
                    </div>

                    <x-theme-toggle />

                    @auth
                        <a class="ml-1 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950"
                           href="{{ route('dashboard') }}">
                            <i class="fa-solid fa-chart-pie"></i>
                            {{ __('menu.dashboard') }}
                        </a>
                    @else
                        <a class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 sm:inline-block"
                           href="{{ route('login') }}">{{ __('landing.nav.login') }}</a>
                        <a class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950"
                           href="{{ route('register') }}">{{ __('landing.nav.register') }}</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        {{-- ══════════════════════════════════════════════════════════
             PIE
        ══════════════════════════════════════════════════════════ --}}
        <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-10 md:flex-row md:justify-between">

                    <div class="max-w-xs">
                        <img class="h-9 w-auto object-contain dark:hidden"
                             src="{{ asset('img/logo_trader_h.webp') }}"
                             alt="{{ config('app.name', 'TradeForge') }}">
                        <img class="hidden h-9 w-auto object-contain brightness-0 invert dark:block"
                             src="{{ asset('img/logo_trader_h.webp') }}"
                             alt="{{ config('app.name', 'TradeForge') }}">
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('landing.footer.tagline') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-10 sm:grid-cols-3">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-gray-100">{{ __('landing.footer.product') }}</h3>
                            <ul class="mt-4 space-y-3 text-sm">
                                <li><a class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400" href="{{ route('landing') }}#modulos">{{ __('landing.nav.modules') }}</a></li>
                                <li><a class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400" href="{{ route('landing') }}#propfirms">{{ __('landing.nav.propfirm') }}</a></li>
                                <li><a class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400" href="{{ route('pricing') }}">{{ __('landing.nav.pricing') }}</a></li>
                                <li><a class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400" href="{{ route('demo.enter') }}">{{ __('landing.nav.demo') }}</a></li>
                            </ul>
                        </div>
                        {{-- Legal: la feature termsAndPrivacyPolicy de Jetstream está desactivada
                             (config/jetstream.php:61), así que las rutas terms.show/policy.show no
                             existen. Se muestran solo si se activa, para no reventar la landing. --}}
                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-gray-100">{{ __('landing.footer.legal') }}</h3>
                                <ul class="mt-4 space-y-3 text-sm">
                                    <li><a class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400" href="{{ route('terms.show') }}">{{ __('landing.footer.terms') }}</a></li>
                                    <li><a class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400" href="{{ route('policy.show') }}">{{ __('landing.footer.privacy') }}</a></li>
                                </ul>
                            </div>
                        @endif

                        <div class="col-span-2 sm:col-span-1">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-gray-100">{{ __('landing.nav.register') }}</h3>
                            <a class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700"
                               href="{{ route('register') }}">
                                {{ __('landing.hero.cta') }}
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex flex-col gap-4 border-t border-gray-200 pt-6 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ date('Y') }} {{ config('app.name', 'TradeForge') }}. {{ __('landing.footer.rights') }}</p>
                    <div class="sm:hidden">
                        <x-language-selector align="left" />
                    </div>
                </div>
            </div>
        </footer>

        @livewireScripts
    </body>

</html>
