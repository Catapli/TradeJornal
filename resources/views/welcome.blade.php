{{--
    ════════════════════════════════════════════════════════════════════════════
    LANDING PÚBLICA
    ════════════════════════════════════════════════════════════════════════════
    Sustituye a la plantilla por defecto de Laravel (Documentation / Laracasts /
    Laravel News) que ocupaba este fichero. Es la primera pantalla que ve alguien
    que llega desde fuera, así que aquí no se pide login para nada.

    Todos los textos viven en lang/{es,en}/landing.php.

    Los mockups de producto son ilustraciones en HTML/CSS, no capturas: se marcan
    como tales con aria-hidden y no pretenden pasar por pantallazos reales.
    Cuando haya capturas de verdad, van a public/img/landing/ y sustituyen a los
    bloques comentados como MOCKUP.
--}}

<x-public-layout>

    {{-- ═══════════════════════════════════════════════════════════
         HÉROE
    ═══════════════════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden">

        {{-- Fondo: rejilla suave + halo. Decorativo. --}}
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(99,102,241,0.06)_1px,transparent_1px),linear-gradient(to_bottom,rgba(99,102,241,0.06)_1px,transparent_1px)] bg-[size:56px_56px]"></div>
            <div class="absolute left-1/2 top-0 h-[420px] w-[820px] -translate-x-1/2 rounded-full bg-indigo-500/10 blur-3xl dark:bg-indigo-500/15"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-b from-transparent to-white dark:to-gray-950"></div>
        </div>

        <div class="mx-auto max-w-7xl px-4 pb-16 pt-16 sm:px-6 sm:pt-24 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">

                <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                    <i class="fa-solid fa-shield-halved"></i>
                    {{ __('landing.hero.badge') }}
                </span>

                <h1 class="mt-6 text-balance text-4xl font-black leading-[1.08] tracking-tight text-gray-900 dark:text-white sm:text-6xl">
                    {{ __('landing.hero.title') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-pretty text-lg leading-relaxed text-gray-600 dark:text-gray-400">
                    {{ __('landing.hero.subtitle') }}
                </p>

                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-7 py-3.5 text-base font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950 sm:w-auto"
                       href="{{ route('register') }}">
                        {{ __('landing.hero.cta') }}
                        <i class="fa-solid fa-arrow-right text-sm"></i>
                    </a>
                    <a class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-7 py-3.5 text-base font-bold text-gray-900 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800 dark:focus-visible:ring-offset-gray-950 sm:w-auto"
                       href="{{ route('demo.enter') }}">
                        <i class="fa-solid fa-play text-xs"></i>
                        {{ __('landing.hero.cta_demo') }}
                    </a>
                </div>

                <p class="mt-5 text-sm text-gray-500 dark:text-gray-500">{{ __('landing.hero.trust') }}</p>
            </div>

            {{-- MOCKUP: ventana de aplicación ilustrada --}}
            <div class="relative mx-auto mt-16 max-w-5xl" aria-hidden="true">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-900/10 dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/40">

                    {{-- Barra de ventana --}}
                    <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950">
                        <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        <span class="ml-3 font-mono text-[11px] text-gray-400 dark:text-gray-600">tradeforge · dashboard</span>
                    </div>

                    <div class="grid gap-4 p-5 sm:p-6 lg:grid-cols-[1.6fr_1fr]">

                        {{-- Columna izquierda: KPIs + curva --}}
                        <div class="flex flex-col gap-4">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @php
                                    $kpis = [
                                        ['P&L', '+2.847 €', 'text-emerald-600 dark:text-emerald-400'],
                                        ['Win rate', '58 %', 'text-gray-900 dark:text-gray-100'],
                                        ['Profit factor', '1,84', 'text-gray-900 dark:text-gray-100'],
                                        ['DD diario', '1,2 %', 'text-amber-600 dark:text-amber-400'],
                                    ];
                                @endphp
                                @foreach ($kpis as [$label, $value, $tone])
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-3 dark:border-gray-800 dark:bg-gray-950/50">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $label }}</p>
                                        <p class="mt-1 text-lg font-black tabular-nums {{ $tone }}">{{ $value }}</p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Curva de balance ilustrada --}}
                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <p class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Curva de balance</p>
                                <svg class="h-32 w-full" viewBox="0 0 400 120" preserveAspectRatio="none" role="presentation">
                                    <defs>
                                        <linearGradient id="tf-curve" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0%" stop-color="rgb(99 102 241)" stop-opacity="0.35" />
                                            <stop offset="100%" stop-color="rgb(99 102 241)" stop-opacity="0" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M0,104 L28,98 L56,101 L84,86 L112,90 L140,72 L168,78 L196,58 L224,64 L252,44 L280,50 L308,32 L336,38 L364,22 L400,18 L400,120 L0,120 Z"
                                          fill="url(#tf-curve)" />
                                    <path d="M0,104 L28,98 L56,101 L84,86 L112,90 L140,72 L168,78 L196,58 L224,64 L252,44 L280,50 L308,32 L336,38 L364,22 L400,18"
                                          fill="none" stroke="rgb(99 102 241)" stroke-width="2.5"
                                          stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                                </svg>
                            </div>
                        </div>

                        {{-- Columna derecha: calendario de P&L --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                            <p class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Calendario de P&L</p>
                            @php
                                // Patrón fijo (no aleatorio) para que el mockup sea idéntico en cada render.
                                $days = [0,0,1,2,-1,1,0, 2,1,-2,1,3,0,0, 1,2,1,-1,2,0,0, 3,1,2,-1,1,0,0, 2,3,1];
                            @endphp
                            <div class="grid grid-cols-7 gap-1.5">
                                @foreach ($days as $d)
                                    <span @class([
                                        'aspect-square rounded-[3px]',
                                        'bg-gray-200 dark:bg-gray-800' => $d === 0,
                                        'bg-emerald-300 dark:bg-emerald-700/60' => $d === 1,
                                        'bg-emerald-400 dark:bg-emerald-600/70' => $d === 2,
                                        'bg-emerald-500 dark:bg-emerald-500' => $d === 3,
                                        'bg-red-400 dark:bg-red-600/70' => $d === -1,
                                        'bg-red-500 dark:bg-red-500' => $d === -2,
                                    ])></span>
                                @endforeach
                            </div>

                            <div class="mt-4 space-y-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                                @foreach ([['Objetivo de fase', '68 %', 'bg-indigo-500', '68'], ['Días operados', '7 / 5', 'bg-emerald-500', '100'], ['Drawdown usado', '24 %', 'bg-amber-500', '24']] as [$label, $value, $color, $pct])
                                    <div>
                                        <div class="flex items-baseline justify-between text-[11px]">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $label }}</span>
                                            <span class="font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $value }}</span>
                                        </div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                                            <div class="h-full rounded-full {{ $color }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         PROBLEMA
    ═══════════════════════════════════════════════════════════ --}}
    <section class="border-y border-gray-200 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-900/50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">{{ __('landing.problem.eyebrow') }}</p>
                <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('landing.problem.title') }}</h2>
                <p class="mt-4 text-pretty text-gray-600 dark:text-gray-400">{{ __('landing.problem.lead') }}</p>
            </div>

            <div class="mx-auto mt-12 grid max-w-5xl gap-5 md:grid-cols-3">
                @foreach (__('landing.problem.items') as $i => $item)
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 font-mono text-sm font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">
                            {{ sprintf('%02d', $i + 1) }}
                        </span>
                        <h3 class="mt-4 text-base font-bold text-gray-900 dark:text-gray-100">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         MÓDULOS
    ═══════════════════════════════════════════════════════════ --}}
    <section class="py-20" id="modulos">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">{{ __('landing.modules.eyebrow') }}</p>
                <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('landing.modules.title') }}</h2>
                <p class="mt-4 text-pretty text-gray-600 dark:text-gray-400">{{ __('landing.modules.lead') }}</p>
            </div>

            @php
                // El icono es el mismo que usa cada entrada del sidebar, para que la
                // landing y la aplicación hablen el mismo lenguaje visual.
                $moduleIcons = [
                    'dashboard'   => ['fa-chart-pie',      'text-indigo-600 dark:text-indigo-400',   'bg-indigo-50 dark:bg-indigo-500/10'],
                    'journal'     => ['fa-book-bookmark',  'text-sky-600 dark:text-sky-400',         'bg-sky-50 dark:bg-sky-500/10'],
                    'session'     => ['fa-bolt',           'text-amber-600 dark:text-amber-400',     'bg-amber-50 dark:bg-amber-500/10'],
                    'lab'         => ['fa-flask',          'text-violet-600 dark:text-violet-400',   'bg-violet-50 dark:bg-violet-500/10'],
                    'playbook'    => ['fa-chess-board',    'text-emerald-600 dark:text-emerald-400', 'bg-emerald-50 dark:bg-emerald-500/10'],
                    'backtesting' => ['fa-database',       'text-rose-600 dark:text-rose-400',       'bg-rose-50 dark:bg-rose-500/10'],
                ];
            @endphp

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (__('landing.modules.items') as $key => $module)
                    @php
                        [$icon, $iconColor, $iconBg] = $moduleIcons[$key];
                    @endphp
                    <div class="group rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-indigo-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/40">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl {{ $iconBg }}">
                            <i class="fa-solid {{ $icon }} {{ $iconColor }}"></i>
                        </span>
                        <h3 class="mt-4 text-base font-bold text-gray-900 dark:text-gray-100">{{ $module['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $module['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         PROP FIRMS
    ═══════════════════════════════════════════════════════════ --}}
    <section class="border-y border-gray-200 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-900/50" id="propfirms">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">{{ __('landing.propfirm.eyebrow') }}</p>
                    <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('landing.propfirm.title') }}</h2>
                    <p class="mt-4 text-pretty leading-relaxed text-gray-600 dark:text-gray-400">{{ __('landing.propfirm.lead') }}</p>

                    <dl class="mt-8 grid gap-5 sm:grid-cols-2">
                        @php
                            $ruleIcons = [
                                'daily_dd' => 'fa-gauge-high',
                                'max_dd'   => 'fa-arrow-trend-down',
                                'target'   => 'fa-bullseye',
                                'min_days' => 'fa-calendar-check',
                            ];
                        @endphp
                        @foreach (__('landing.propfirm.rules') as $key => $rule)
                            <div class="flex gap-3">
                                <i class="fa-solid {{ $ruleIcons[$key] }} mt-1 text-indigo-500"></i>
                                <div>
                                    <dt class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $rule['title'] }}</dt>
                                    <dd class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $rule['text'] }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>

                    <p class="mt-8 text-xs text-gray-500 dark:text-gray-500">{{ __('landing.propfirm.note') }}</p>
                </div>

                {{-- MOCKUP: tarjeta de objetivos --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900" aria-hidden="true">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Challenge 50 000 USD</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Fase 1 · 18 días operando</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">En curso</span>
                    </div>

                    <div class="mt-5 space-y-5">
                        @foreach ([
                            ['Objetivo de beneficio', '4.020 € / 5.000 €', 80, 'bg-indigo-500', 'text-indigo-600 dark:text-indigo-400'],
                            ['Drawdown diario', '620 € libres de 2.500 €', 25, 'bg-emerald-500', 'text-emerald-600 dark:text-emerald-400'],
                            ['Drawdown total', '1.180 € usados de 5.000 €', 24, 'bg-amber-500', 'text-amber-600 dark:text-amber-400'],
                            ['Días mínimos', '18 de 4', 100, 'bg-emerald-500', 'text-emerald-600 dark:text-emerald-400'],
                        ] as [$label, $value, $pct, $bar, $tone])
                            <div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                    <span class="text-xs font-bold tabular-nums {{ $tone }}">{{ $value }}</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                                    <div class="h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         AUDITOR IA
    ═══════════════════════════════════════════════════════════ --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">

                {{-- MOCKUP: salida del auditor --}}
                <div class="order-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900 lg:order-1" aria-hidden="true">
                    <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-800 dark:bg-gray-950">
                        <i class="fa-solid fa-robot text-indigo-500"></i>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('landing.ai.sample.label') }}</span>
                    </div>
                    <div class="space-y-4 p-6 text-sm">
                        <p class="text-gray-700 dark:text-gray-300"><span class="mr-1">🎯</span>{{ __('landing.ai.sample.entry') }}</p>
                        <p class="text-gray-700 dark:text-gray-300"><span class="mr-1">🧠</span>{{ __('landing.ai.sample.mgmt') }}</p>
                        <p class="text-gray-700 dark:text-gray-300"><span class="mr-1">⚖️</span>{{ __('landing.ai.sample.verdict') }}</p>
                        <div class="flex items-center gap-3 rounded-xl bg-amber-50 px-4 py-3 dark:bg-amber-500/10">
                            <span class="text-2xl font-black tabular-nums text-amber-600 dark:text-amber-400">4</span>
                            <span class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">{{ __('landing.ai.sample.score') }}</span>
                        </div>
                    </div>
                </div>

                <div class="order-1 lg:order-2">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">{{ __('landing.ai.eyebrow') }}</p>
                    <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('landing.ai.title') }}</h2>
                    <p class="mt-4 text-pretty leading-relaxed text-gray-600 dark:text-gray-400">{{ __('landing.ai.lead') }}</p>
                    <p class="mt-6 text-xs text-gray-500 dark:text-gray-500">{{ __('landing.ai.note') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         ERRORES / DISCIPLINA
    ═══════════════════════════════════════════════════════════ --}}
    <section class="border-y border-gray-200 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-900/50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">{{ __('landing.mistakes.eyebrow') }}</p>
                <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('landing.mistakes.title') }}</h2>
                <p class="mt-4 text-pretty text-gray-600 dark:text-gray-400">{{ __('landing.mistakes.lead') }}</p>
            </div>

            @php
                // Mismos slugs y misma agrupación por peso que database/seeders/MistakesSeeder.php.
                $mistakeGroups = [
                    ['key' => 'grave',  'ring' => 'border-red-200 dark:border-red-500/30',    'dot' => 'bg-red-500',
                     'slugs' => ['revenge_trading', 'no_stop_loss', 'moved_stop_loss', 'averaging_down', 'excessive_risk']],
                    ['key' => 'medium', 'ring' => 'border-amber-200 dark:border-amber-500/30','dot' => 'bg-amber-500',
                     'slugs' => ['fomo', 'overtrading', 'counter_trend', 'round_trip', 'held_loser', 'no_setup', 'news_trading']],
                    ['key' => 'light',  'ring' => 'border-sky-200 dark:border-sky-500/30',    'dot' => 'bg-sky-500',
                     'slugs' => ['early_exit', 'late_entry', 'wrong_size']],
                ];
            @endphp

            <div class="mx-auto mt-12 grid max-w-5xl gap-5 md:grid-cols-3">
                @foreach ($mistakeGroups as $group)
                    <div class="rounded-2xl border bg-white p-6 dark:bg-gray-900 {{ $group['ring'] }}">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $group['dot'] }}"></span>
                            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">{{ __('landing.mistakes.' . $group['key']) }}</h3>
                        </div>
                        <ul class="mt-4 space-y-2">
                            @foreach ($group['slugs'] as $slug)
                                <li class="text-sm text-gray-600 dark:text-gray-400">{{ __('mistakes.' . $slug . '.name') }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         DEMO
    ═══════════════════════════════════════════════════════════ --}}
    <section class="py-20">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">{{ __('landing.demo.eyebrow') }}</p>
            <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('landing.demo.title') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-pretty text-gray-600 dark:text-gray-400">{{ __('landing.demo.lead') }}</p>

            <a class="mt-8 inline-flex items-center gap-2 rounded-xl bg-gray-900 px-7 py-3.5 text-base font-bold text-white shadow-lg transition hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 dark:focus-visible:ring-offset-gray-950"
               href="{{ route('demo.enter') }}">
                <i class="fa-solid fa-play text-xs"></i>
                {{ __('landing.demo.cta') }}
            </a>

            <p class="mt-4 text-xs text-gray-500 dark:text-gray-500">{{ __('landing.demo.note') }}</p>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         PRECIOS
    ═══════════════════════════════════════════════════════════ --}}
    <section class="border-y border-gray-200 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-900/50" id="precios">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">{{ __('landing.pricing.eyebrow') }}</p>
                <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('landing.pricing.title') }}</h2>
                <p class="mt-4 text-pretty text-gray-600 dark:text-gray-400">{{ __('landing.pricing.lead') }}</p>
            </div>

            <x-pricing-table mode="public"
                             :subscribed="auth()->check() && auth()->user()->subscribed('default')" />
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         FAQ
    ═══════════════════════════════════════════════════════════ --}}
    <section class="py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">{{ __('landing.faq.eyebrow') }}</p>
                <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('landing.faq.title') }}</h2>
            </div>

            <div class="mt-10 divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900"
                 x-data="{ open: null }">
                @foreach (__('landing.faq.items') as $i => $item)
                    <div>
                        <button class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500 dark:hover:bg-gray-800/50"
                                type="button"
                                @click="open = open === {{ $i }} ? null : {{ $i }}"
                                :aria-expanded="(open === {{ $i }}).toString()"
                                aria-controls="faq-{{ $i }}">
                            <span class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $item['q'] }}</span>
                            <i class="fa-solid fa-chevron-down shrink-0 text-xs text-gray-400 transition-transform duration-200"
                               :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                        </button>
                        <div class="px-6 pb-5 text-sm leading-relaxed text-gray-600 dark:text-gray-400"
                             id="faq-{{ $i }}"
                             x-show="open === {{ $i }}"
                             x-collapse
                             style="display: none;">
                            {{ $item['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         CIERRE
    ═══════════════════════════════════════════════════════════ --}}
    <section class="pb-24">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-gray-900 px-8 py-14 text-center dark:bg-indigo-600 sm:px-16">
                <div class="pointer-events-none absolute inset-0 -z-0 opacity-20" aria-hidden="true">
                    <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-indigo-500 blur-3xl dark:bg-white"></div>
                    <div class="absolute -bottom-20 -left-10 h-64 w-64 rounded-full bg-violet-500 blur-3xl dark:bg-indigo-300"></div>
                </div>

                <div class="relative">
                    <h2 class="text-balance text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('landing.cta.title') }}</h2>
                    <p class="mx-auto mt-4 max-w-xl text-pretty text-indigo-100">{{ __('landing.cta.lead') }}</p>

                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 text-base font-bold text-gray-900 shadow-lg transition hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 sm:w-auto"
                           href="{{ route('register') }}">
                            {{ __('landing.cta.primary') }}
                            <i class="fa-solid fa-arrow-right text-sm"></i>
                        </a>
                        <a class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/30 px-7 py-3.5 text-base font-bold text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 sm:w-auto"
                           href="{{ route('demo.enter') }}">
                            {{ __('landing.cta.secondary') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-public-layout>
