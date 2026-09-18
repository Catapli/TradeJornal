<div>
    {{-- Navbar TradeForge --}}
    {{-- CAMBIO: bg-white, border-b, eliminamos el margin-left negativo si lo hubiera --}}
    {{-- IMPORTANTE: sm:ml-20 para que empiece DESPUÉS del sidebar en escritorio --}}
    <nav class="fixed left-0 right-0 top-0 z-[40] h-16 border-b border-gray-200 bg-white/80 backdrop-blur-md transition-all dark:border-gray-800 dark:bg-gray-900/80 sm:ml-20">

        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

            {{-- IZQUIERDA: TÍTULO O LOGO (En móvil se ve logo, en desktop título de sección o breadcrumbs) --}}
            <div class="flex items-center gap-4">
                {{-- Hamburger Móvil (Solo visible en móvil) --}}
                <button class="rounded-lg p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 sm:hidden"
                        @click="$dispatch('open-mobile-menu')">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                {{-- En Desktop, el logo ya está en el Sidebar. Aquí podemos poner un Título dinámico o dejarlo limpio --}}
                <div class="hidden font-bold text-gray-800 dark:text-gray-100 sm:block">
                    {{-- Puedes poner breadcrumbs aquí --}}
                    <span class="text-indigo-600">TradeForge</span>
                </div>

                {{-- Logo solo para Móvil --}}
                <div class="flex shrink-0 items-center sm:hidden">
                    <img class="h-8 w-auto"
                         src="{{ asset('img/logo.png') }}"
                         alt="TradeForge">
                </div>
            </div>

            {{-- ESTADO DE SUSCRIPCIÓN --}}
            @php
                $navUser = Auth::user();
                $navTrialDays = $navUser->trialDaysLeft();
                $navTrialEnding = $navTrialDays > 0 && $navTrialDays <= (int) config('billing.trial_warning_days');
            @endphp

            <div class="mr-2 hidden items-center gap-2 md:flex">

                {{-- RACHAS: la disciplina solo se cuida si se ve. --}}
                <x-streak-badges />

                {{-- CRÉDITOS DE IA: un límite que se entiende se percibe como valor;
                     uno invisible, como tacañería el día que se acaba. --}}
                @php($navCredits = $navUser->aiCreditsLeft())
                <span @class([
                    'flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold',
                    'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' => $navCredits > 0,
                    'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-400' => $navCredits === 0,
                ])
                      title="{{ __('labels.ai_credits_tooltip', ['limit' => $navUser->aiDailyLimit()]) }}">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span class="tabular-nums">{{ $navCredits }}/{{ $navUser->aiDailyLimit() }}</span>
                </span>

                @if ($navUser->onProTrial())
                    {{-- PRUEBA EN CURSO: se avisa desde el principio y con más
                         urgencia en los últimos días, para que nadie la pierda
                         sin enterarse. --}}
                    <a @class([
                        'flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold transition-all hover:shadow-md',
                        'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300' => ! $navTrialEnding,
                        'border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100 dark:border-amber-500/50 dark:bg-amber-500/15 dark:text-amber-300' => $navTrialEnding,
                    ])
                       href="{{ route('pricing') }}">
                        <i class="fa-solid fa-hourglass-half"></i>
                        <span>{{ trans_choice('labels.trial_days_left', $navTrialDays, ['days' => $navTrialDays]) }}</span>
                    </a>
                @elseif ($navUser->hasProAccess())
                    {{-- USUARIO PRO (Badge elegante) --}}
                    <div class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 shadow-sm">
                        <i class="fa-solid fa-crown text-emerald-500"></i>
                        <span>{{ __('labels.pro_plan') }}</span>
                    </div>
                @else
                    {{-- USUARIO FREE (Botón Call to Action) --}}
                    <a class="group flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600 transition-all hover:border-indigo-300 hover:bg-indigo-100 hover:text-indigo-700 hover:shadow-md"
                       href="{{ route('pricing') }}">
                        <i class="fa-regular fa-star transition-transform group-hover:scale-110"></i>
                        <span>{{ __('labels.upgrade_plan') }}</span>
                    </a>
                @endif
            </div>

            {{-- DERECHA: USER & IDIOMA --}}
            <div class="flex items-center gap-3">

                {{-- TOGGLE TEMA CLARO/OSCURO --}}
                <button class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm transition-all hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-indigo-400"
                        x-data
                        @click="
                            const html = document.documentElement;
                            const isDark = html.classList.toggle('dark');
                            localStorage.setItem('theme', isDark ? 'dark' : 'light');
                            window.dispatchEvent(new CustomEvent('theme:changed', { detail: { dark: isDark } }));
                        "
                        type="button"
                        title="{{ __('labels.toggle_theme') }}">
                    <i class="fa-solid fa-moon dark:hidden"></i>
                    <i class="fa-solid fa-sun hidden dark:inline"></i>
                </button>

                <div class="flex items-center rounded-lg bg-gray-200 p-1 dark:bg-gray-800"
                     x-data>
                    <button class="rounded-md px-3 py-1 text-xs font-bold transition-all"
                            @click="$store.viewMode.mode = 'currency'; localStorage.setItem('tf_view_mode', 'currency')"
                            :class="$store.viewMode.mode === 'currency' ? 'bg-white dark:bg-gray-800 text-indigo-600 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                        $
                    </button>
                    <button class="rounded-md px-3 py-1 text-xs font-bold transition-all"
                            @click="$store.viewMode.mode = 'percentage'; localStorage.setItem('tf_view_mode', 'percentage')"
                            :class="$store.viewMode.mode === 'percentage' ? 'bg-white dark:bg-gray-800 text-indigo-600 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                        %
                    </button>
                </div>

                {{-- SELECTOR DE IDIOMA --}}
                <x-language-selector />

                {{-- PERFIL DE USUARIO (Estilo Clean) --}}
                <x-dropdown align="right"
                            width="48">
                    <x-slot name="trigger">
                        <button
                                class="flex items-center gap-2 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1 pr-3 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm transition-all hover:bg-gray-50 dark:hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                <span class="text-xs font-bold">{{ substr(Auth::user()->name, 0, 2) }}</span>
                            </div>
                            <span class="hidden md:block">{{ Str::limit(Auth::user()->name, 12) }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 dark:text-gray-500"></i>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="block px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            {{ __('labels.account') }}
                        </div>

                        <x-dropdown-link class="flex items-center gap-2"
                                         href="{{ route('profile.show') }}">
                            <i class="fa-regular fa-user text-gray-400 dark:text-gray-500"></i> {{ __('labels.profile') }}
                        </x-dropdown-link>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                        <form method="POST"
                              action="{{ route('logout') }}"
                              x-data>
                            @csrf
                            <x-dropdown-link class="flex items-center gap-2 text-rose-600 hover:cursor-pointer hover:bg-rose-50 hover:text-rose-700"
                                             @click.prevent="$root.submit();">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> {{ __('labels.session_close') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>

            </div>
        </div>
    </nav>
</div>
