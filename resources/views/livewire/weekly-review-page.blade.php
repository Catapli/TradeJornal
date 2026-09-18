{{-- Revisión semanal guiada (R2). Seis operaciones, tres preguntas cada una. --}}
<div class="mx-auto max-w-5xl px-4 pb-16 pt-16 sm:px-6 lg:px-8">

    @php
        $summary = $this->summary;
        $trades = $this->trades;
        $completed = $this->isCompleted();
        $week = \Carbon\CarbonImmutable::parse($weekStart);
        $weekLabel = $week->translatedFormat('j M') . ' – ' . $week->endOfWeek()->translatedFormat('j M Y');
    @endphp

    {{-- CABECERA --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ __('weekly.review.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('weekly.review.lead') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <button class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    type="button"
                    wire:click="previousWeek"
                    title="{{ __('weekly.review.previous') }}">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>

            <span class="min-w-[11rem] text-center text-sm font-bold text-gray-900 dark:text-gray-100">{{ $weekLabel }}</span>

            <button @class([
                'flex h-9 w-9 items-center justify-center rounded-lg border text-xs transition',
                'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800' => $this->canGoForward(),
                'cursor-not-allowed border-gray-200 text-gray-300 dark:border-gray-700 dark:text-gray-600' => ! $this->canGoForward(),
            ])
                    type="button"
                    @disabled(! $this->canGoForward())
                    wire:click="nextWeek"
                    title="{{ __('weekly.review.next') }}">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    {{-- RESUMEN DE LA SEMANA --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @php
            $kpis = [
                ['label' => __('weekly.kpi.result'), 'value' => ($summary['pnl'] >= 0 ? '+' : '') . number_format($summary['pnl'], 2, ',', '.') . ' $', 'tone' => $summary['pnl'] >= 0 ? 'up' : 'down'],
                ['label' => __('weekly.kpi.trades'), 'value' => $summary['trades'], 'tone' => 'flat'],
                ['label' => __('weekly.kpi.win_rate'), 'value' => number_format($summary['win_rate'], 1) . '%', 'tone' => 'flat'],
                ['label' => __('weekly.kpi.journal_days'), 'value' => $summary['journal_days'], 'tone' => 'flat'],
            ];
        @endphp

        @foreach ($kpis as $kpi)
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                <p @class([
                    'text-lg font-black tabular-nums',
                    'text-emerald-600 dark:text-emerald-400' => $kpi['tone'] === 'up',
                    'text-red-600 dark:text-red-400' => $kpi['tone'] === 'down',
                    'text-gray-900 dark:text-gray-100' => $kpi['tone'] === 'flat',
                ])>{{ $kpi['value'] }}</p>
                <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($trades->isEmpty())
        <x-empty-state icon="fa-calendar-xmark"
                       :title="__('weekly.review.empty_title')"
                       :text="__('weekly.review.empty_text')" />
    @else

        {{-- ESTADO --}}
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 dark:border-indigo-500/25 dark:bg-indigo-500/10">
            <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">
                @if ($completed)
                    <i class="fa-solid fa-circle-check mr-1"></i>
                    {{ __('weekly.review.done_on', ['date' => $this->review?->completed_at?->translatedFormat('j M Y')]) }}
                @else
                    {{ __('weekly.review.progress', ['done' => $this->answered(), 'total' => $trades->count()]) }}
                @endif
            </p>

            <span class="text-xs text-indigo-700/80 dark:text-indigo-300/80">{{ __('weekly.review.selection') }}</span>
        </div>

        {{-- La otra mitad de la rutina semanal: etiquetar lo que salió mal. Vive en
             su propia pantalla porque son dos cosas distintas —revisar el proceso y
             marcar errores— pero se hacen el mismo día. --}}
        <x-review-cta class="mb-5" />

        {{-- OPERACIONES --}}
        <div class="space-y-4">
            @foreach ($trades as $trade)
                @php
                    $key = 'trade_' . $trade->id;
                @endphp

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span @class([
                                'flex h-8 w-8 items-center justify-center rounded-lg text-xs',
                                'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400' => $trade->pnl >= 0,
                                'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-400' => $trade->pnl < 0,
                            ])>
                                <i class="fa-solid {{ $trade->direction === 'long' ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                            </span>

                            <div>
                                <p class="text-sm font-black text-gray-900 dark:text-gray-100">{{ $trade->tradeAsset?->symbol ?? '—' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\CarbonImmutable::parse($trade->exit_time)->translatedFormat('D j M · H:i') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span @class([
                                'text-sm font-black tabular-nums',
                                'text-emerald-600 dark:text-emerald-400' => $trade->pnl >= 0,
                                'text-red-600 dark:text-red-400' => $trade->pnl < 0,
                            ])>
                                {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format((float) $trade->pnl, 2, ',', '.') }} $
                            </span>

                            {{-- Ver la operación entera sin salir de la revisión: gráfico,
                                 notas y errores marcados en el modal global. --}}
                            <button class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                                    type="button"
                                    wire:click="openTradeDetail({{ $trade->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="openTradeDetail({{ $trade->id }})"
                                    title="{{ __('weekly.review.open_trade') }}">
                                <i class="fa-solid fa-magnifying-glass-chart"
                                   wire:loading.remove
                                   wire:target="openTradeDetail({{ $trade->id }})"></i>
                                <i class="fa-solid fa-spinner fa-spin"
                                   wire:loading
                                   wire:target="openTradeDetail({{ $trade->id }})"></i>
                                <span class="hidden sm:inline">{{ __('weekly.review.open_trade') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4 px-5 py-4">

                        {{-- 1. ¿Estaba en el plan? --}}
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('weekly.review.q_plan') }}</p>

                            <div class="flex flex-wrap gap-2">
                                @foreach (['yes', 'partly', 'no'] as $option)
                                    @php
                                        $selected = ($answers[$key]['plan'] ?? null) === $option;
                                    @endphp
                                    <button @class([
                                        'rounded-lg border px-3 py-1.5 text-xs font-bold transition',
                                        'border-indigo-600 bg-indigo-600 text-white' => $selected,
                                        'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700' => ! $selected,
                                    ])
                                            type="button"
                                            @disabled($completed)
                                            wire:click="$set('answers.{{ $key }}.plan', '{{ $option }}')">
                                        {{ __('weekly.review.plan_' . $option) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- 2 y 3: texto libre --}}
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                       for="{{ $key }}-trigger">{{ __('weekly.review.q_trigger') }}</label>
                                <textarea class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                          id="{{ $key }}-trigger"
                                          rows="2"
                                          maxlength="1000"
                                          @disabled($completed)
                                          wire:model.blur="answers.{{ $key }}.trigger"
                                          placeholder="{{ __('weekly.review.q_trigger_hint') }}"></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                       for="{{ $key }}-change">{{ __('weekly.review.q_change') }}</label>
                                <textarea class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                          id="{{ $key }}-change"
                                          rows="2"
                                          maxlength="1000"
                                          @disabled($completed)
                                          wire:model.blur="answers.{{ $key }}.change"
                                          placeholder="{{ __('weekly.review.q_change_hint') }}"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- CONCLUSIÓN Y CIERRE --}}
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <label class="mb-1 block text-sm font-bold text-gray-900 dark:text-gray-100"
                   for="takeaway">{{ __('weekly.review.takeaway') }}</label>
            <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ __('weekly.review.takeaway_hint') }}</p>

            <textarea class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                      id="takeaway"
                      rows="3"
                      maxlength="1000"
                      @disabled($completed)
                      wire:model.blur="takeaway"></textarea>

            @unless ($completed)
                <div class="mt-4 flex flex-wrap gap-3">
                    <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                            type="button"
                            wire:click="complete"
                            wire:loading.attr="disabled">
                        {{ __('weekly.review.complete') }}
                    </button>

                    <button class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                            type="button"
                            wire:click="save"
                            wire:loading.attr="disabled">
                        {{ __('weekly.review.save_draft') }}
                    </button>
                </div>
            @endunless
        </div>
    @endif

    {{-- ARCHIVO: la serie es lo que da sentido a la revisión --}}
    @if ($this->history->isNotEmpty())
        <div class="mt-10">
            <h2 class="mb-3 text-lg font-black text-gray-900 dark:text-gray-100">{{ __('weekly.review.history') }}</h2>

            <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">{{ __('weekly.review.col_week') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('weekly.kpi.result') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('weekly.kpi.trades') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('weekly.kpi.win_rate') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('weekly.kpi.discipline') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($this->history as $past)
                            @php
                                $stats = $past->stats ?? [];
                            @endphp
                            <tr class="text-gray-800 dark:text-gray-200">
                                <td class="px-4 py-3 font-semibold">{{ $past->week_start->translatedFormat('j M Y') }}</td>
                                <td @class([
                                    'px-4 py-3 text-right font-bold tabular-nums',
                                    'text-emerald-600 dark:text-emerald-400' => ($stats['pnl'] ?? 0) >= 0,
                                    'text-red-600 dark:text-red-400' => ($stats['pnl'] ?? 0) < 0,
                                ])>
                                    {{ ($stats['pnl'] ?? 0) >= 0 ? '+' : '' }}{{ number_format((float) ($stats['pnl'] ?? 0), 2, ',', '.') }} $
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $stats['trades'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) ($stats['win_rate'] ?? 0), 1) }}%</td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ isset($stats['discipline']) ? number_format((float) $stats['discipline'], 1) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button class="text-xs font-bold text-indigo-600 underline transition hover:text-indigo-800 dark:text-indigo-400"
                                            type="button"
                                            wire:click="openWeek('{{ $past->week_start->toDateString() }}')">
                                        {{ __('weekly.review.open') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
