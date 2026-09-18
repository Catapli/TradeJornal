{{--
    Repaso de errores, una operación por pantalla (Fase 6 · usabilidad).

    **Esta pantalla no hace scroll y no puede hacerlo.** Se repasan catorce
    operaciones seguidas: si para marcar un error o pulsar «siguiente» hay que
    bajar, el repaso se abandona a la tercera. Por eso el alto está repartido a
    mano y no se deja crecer:

        barra superior (fija)  ·  gráfico | ficha + errores + botones

    El contenedor se ata a `100vh - 4rem` (la altura del navbar) con
    `overflow-hidden`, y dentro del lateral solo hay una zona elástica: la nube de
    errores, que se queda con lo que sobre y se desplaza por dentro si el catálogo
    crece. La ficha y los botones no encogen nunca.
--}}
{{-- `mt-16` por el navbar, que es `fixed h-16`: 4rem arriba + el resto de la
     ventana da exactamente 100vh y el <main> no se pasa. En pantallas
     pequeñas se suelta la atadura: con el gráfico y la ficha apilados no cabe
     todo, y ahí es preferible desplazar que aplastar. --}}
<div class="mt-16 flex min-h-[calc(100vh-4rem)] flex-col px-4 pb-3 pt-3 sm:px-6 lg:h-[calc(100vh-4rem)] lg:min-h-0 lg:overflow-hidden lg:px-8"
     x-data="{
         atajo(e) {
             // Las flechas no se tocan si se está escribiendo: dentro del selector
             // hay un formulario para crear errores propios.
             const t = e.target;
             if (t.matches('input, textarea, select') || t.isContentEditable) return;
             if (e.key === 'ArrowRight') { e.preventDefault(); $wire.markReviewed(); }
             if (e.key === 'ArrowLeft') { e.preventDefault(); $wire.back(); }
         }
     }"
     @keydown.window="atajo($event)">

    @php
        $trade = $this->trade;
        $total = $this->total;
    @endphp

    {{-- ═══ BARRA SUPERIOR: título, progreso y salida, todo en una línea ═══ --}}
    <div class="mb-3 flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2">
        <h1 class="text-lg font-black text-gray-900 dark:text-gray-100">{{ __('review.title') }}</h1>

        @if ($total > 0 && $trade)
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                {{ __('review.position', ['position' => $this->position, 'total' => $total]) }}
            </span>

            <div class="h-1.5 min-w-[6rem] flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div class="h-full rounded-full bg-indigo-500 transition-all duration-300"
                     style="width: {{ round($this->index / $total * 100) }}%"></div>
            </div>

            <span class="hidden text-[11px] text-gray-400 dark:text-gray-500 xl:inline">
                {{ __('review.shortcuts') }}
            </span>
        @else
            <div class="flex-1"></div>
        @endif

        <a class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
           href="{{ route('trades') }}"
           wire:navigate>
            <i class="fa-solid fa-xmark text-[10px]"></i>
            {{ __('review.exit') }}
        </a>
    </div>

    @if ($total === 0)
        <div class="flex min-h-0 flex-1 items-center justify-center">
            {{-- La cola vacía es una buena noticia y hay que darla. --}}
            <x-empty-state icon="fa-circle-check"
                           :title="__('review.empty_title')"
                           :text="__('review.empty_text')">
                <a class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700"
                   href="{{ route('mentor') }}"
                   wire:navigate>
                    <i class="fa-solid fa-user-graduate text-xs"></i>
                    {{ __('review.go_mentor') }}
                </a>
            </x-empty-state>
        </div>
    @elseif (! $trade)
        <div class="flex min-h-0 flex-1 items-center justify-center">
            <div class="w-full max-w-xl rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center dark:border-emerald-500/30 dark:bg-emerald-500/10">
                <span class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-500/20">
                    <i class="fa-solid fa-flag-checkered text-xl text-emerald-600 dark:text-emerald-400"></i>
                </span>

                <p class="text-lg font-black text-emerald-900 dark:text-emerald-200">{{ __('review.done_title') }}</p>
                <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                    {{ trans_choice('review.done_text', $done, ['count' => $done]) }}
                </p>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                    <a class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700"
                       href="{{ route('mentor') }}"
                       wire:navigate>
                        <i class="fa-solid fa-user-graduate text-xs"></i>
                        {{ __('review.go_mentor') }}
                    </a>

                    <button class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 px-5 py-2.5 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-500/40 dark:text-emerald-200 dark:hover:bg-emerald-500/15"
                            type="button"
                            wire:click="reload">
                        <i class="fa-solid fa-rotate text-xs"></i>
                        {{ __('review.reload') }}
                    </button>
                </div>
            </div>
        </div>
    @else
        @php
            $exit = $trade->exit_time;
            $entry = $trade->entry_time;
            $minutes = (int) $trade->duration_minutes;
            $duration = $minutes >= 60
                ? intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm'
                : $minutes . 'm';
            $isLong = $trade->direction === 'long';
            $symbol = \App\MoneyHelper::getSymbol($trade->account?->currency ?? 'USD');
        @endphp

        {{-- ═══ ZONA CENTRAL: gráfico + ficha. Se queda con lo que sobre ═══ --}}
        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-5">

            {{-- El gráfico ocupa todo el alto disponible de la fila. --}}
            <div class="min-h-0 lg:col-span-3">
                <x-trade-chart :trade="$trade"
                               height="h-[45vh] lg:h-full" />
            </div>

            {{-- LA FICHA y los botones. La fecha va primero y en grande: el gráfico
                 de aquí no se parece al de TradingView, así que el día es lo único
                 que permite situar la operación de un vistazo. --}}
            <div class="flex min-h-0 flex-col gap-3 lg:col-span-2">

                <div class="shrink-0 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        {{ __('review.when') }}
                    </p>
                    <p class="mt-0.5 text-xl font-black leading-tight text-gray-900 dark:text-gray-100">
                        {{ $exit?->translatedFormat('l, j \d\e F') }}
                    </p>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                        {{ $exit?->translatedFormat('Y') }}
                        <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                        <span class="tabular-nums">{{ $entry?->format('H:i') }} → {{ $exit?->format('H:i') }}</span>
                        <span class="text-gray-400 dark:text-gray-500">({{ $duration }})</span>
                    </p>

                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                            <i class="fa-solid {{ $isLong ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                        </span>

                        <span class="text-base font-black text-gray-900 dark:text-gray-100">
                            {{ $trade->tradeAsset?->symbol ?? ($trade->tradeAsset?->name ?? '—') }}
                        </span>

                        <span @class([
                            'rounded px-1.5 py-0.5 text-[10px] font-bold uppercase',
                            'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' => $isLong,
                            'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400' => ! $isLong,
                        ])>{{ $trade->direction }}</span>

                        <span class="ml-auto font-mono text-lg font-black text-rose-600 dark:text-rose-400">
                            {{ number_format((float) $trade->pnl, 2, ',', '.') }} {{ $symbol }}
                        </span>
                    </div>

                    <dl class="mt-3 space-y-1 border-t border-gray-100 pt-3 text-xs dark:border-gray-700">
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('review.prices') }}</dt>
                            <dd class="font-mono font-semibold text-gray-900 dark:text-gray-100">
                                {{ (float) $trade->entry_price }} → {{ (float) $trade->exit_price }}
                            </dd>
                        </div>

                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('labels.lots') }}</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format((float) $trade->size, 2) }}</dd>
                        </div>

                        @if ($trade->account?->name)
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('menu.accounts') }}</dt>
                                <dd class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $trade->account->name }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($trade->notes)
                        <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs italic leading-relaxed text-gray-600 dark:bg-gray-700/40 dark:text-gray-300">
                            <i class="fa-solid fa-quote-left mr-1 text-gray-400"></i>{{ $trade->notes }}
                        </p>
                    @endif
                </div>

                {{-- LA AUDITORÍA DE ERRORES, pegada a la ficha. Es la única zona que
                     se estira: se queda con el alto que sobre y, si algún día el
                     catálogo no cabe, se desplaza por dentro sin mover la página. --}}
                <div class="min-h-0 flex-1 overflow-y-auto"
                     wire:key="selector-{{ $trade->id }}">
                    <livewire:mistake-selector :trade="$trade"
                                               :compact="true"
                                               :key="'mistake-selector-' . $trade->id" />
                </div>

                {{-- BOTONERA. Nunca encoge y nunca se va de la pantalla. --}}
                <div class="shrink-0 space-y-2">
                    <button class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700 disabled:opacity-60"
                            type="button"
                            wire:key="repaso-siguiente"
                            wire:click="markReviewed"
                            wire:loading.attr="disabled">
                        {{ __('mistake_cost.review.save') }}
                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                    </button>

                    <div class="grid grid-cols-3 gap-2">
                        <button class="rounded-xl border border-gray-300 px-2 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 disabled:opacity-40 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                type="button"
                                wire:key="repaso-atras"
                                wire:click="back"
                                @disabled($this->index === 0)>
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span class="ml-1">{{ __('review.back') }}</span>
                        </button>

                        <button class="rounded-xl border border-gray-300 px-2 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                type="button"
                                title="{{ __('review.skip_hint') }}"
                                wire:key="repaso-saltar"
                                wire:click="skip">
                            {{ __('review.skip') }}
                        </button>

                        <button class="rounded-xl border border-gray-300 px-2 py-2 text-xs font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                type="button"
                                title="{{ __('mistake_cost.review.clean_hint') }}"
                                wire:key="repaso-limpia"
                                wire:click="markClean"
                                wire:loading.attr="disabled">
                            {{ __('mistake_cost.review.clean') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
