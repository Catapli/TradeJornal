{{-- HEADER --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ __('labels.backtesting') }}</h1>
        <p class="text-sm text-gray-500">{{ __('labels.bt_subtitle') }}</p>
    </div>
    <button class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-500"
            @click="openCreate()">
        <svg class="h-4 w-4"
             xmlns="http://www.w3.org/2000/svg"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 4v16m8-8H4" />
        </svg>
        {{ __('labels.new_setup') }}
    </button>
</div>

{{-- EMPTY STATE --}}
@if ($strategies->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <svg class="mb-4 h-12 w-12 text-gray-300"
             xmlns="http://www.w3.org/2000/svg"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="1.5">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15M14.25 3.104c.251.023.501.05.75.082M19.8 15l-1.575 1.57a2.25 2.25 0 01-1.591.659H7.366a2.25 2.25 0 01-1.591-.659L4.2 15m15.6 0H4.2" />
        </svg>
        <h3 class="text-base font-medium text-gray-700">{{ __('labels.bt_no_strategies_title') }}</h3>
        <p class="mt-1 max-w-xs text-sm text-gray-400">{{ __('labels.bt_no_strategies_text') }}</p>
        <button class="mt-6 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-500"
                @click="openCreate()">
            {{ __('labels.create_strategy') }}
        </button>
    </div>

    {{-- GRID --}}
@else
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($strategies as $strategy)
            @php
                $totalTrades = $strategy->trades_count ?? 0;
                $winningTrades = $strategy->winning_trades_count ?? 0;
                $winrate = $totalTrades > 0 ? round(($winningTrades / $totalTrades) * 100) : null;
                $pnlR = $strategy->trades_sum_pnl_r ?? 0;
            @endphp

            <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-colors hover:border-gray-300">

                {{-- Nombre + badge dirección --}}
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="truncate font-semibold text-gray-900">{{ $strategy->name }}</h3>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $strategy->symbol }} · {{ $strategy->timeframe }}</p>
                    </div>
                    <span
                          class="{{ $strategy->direction === 'long' ? 'bg-emerald-50 text-emerald-600' : '' }} {{ $strategy->direction === 'short' ? 'bg-red-50 text-red-600' : '' }} {{ $strategy->direction === 'both' ? 'bg-gray-100 text-gray-600' : '' }} shrink-0 rounded-full px-2 py-0.5 text-xs">
                        {{ match ($strategy->direction) {'long' => 'Long','short' => 'Short',default => 'L & S'} }}
                    </span>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-3 border-t border-gray-100 pt-3">
                    <div>
                        <p class="text-xs text-gray-400">{{ __('labels.trades') }}</p>
                        <p class="text-sm font-semibold tabular-nums text-gray-900">{{ $totalTrades }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">{{ __('labels.pnl_r') }}</p>
                        <p class="{{ $pnlR >= 0 ? 'text-emerald-600' : 'text-red-500' }} text-sm font-semibold tabular-nums">
                            {{ $pnlR >= 0 ? '+' : '' }}{{ number_format($pnlR, 2) }}R
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">{{ __('labels.win_rate') }}</p>
                        <p class="{{ $winrate === null ? 'text-gray-400' : ($winrate >= 50 ? 'text-emerald-600' : 'text-red-500') }} text-sm font-semibold tabular-nums">
                            {{ $winrate !== null ? $winrate . '%' : '—' }}
                        </p>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="flex items-center gap-2 pt-1">
                    <button class="flex-1 rounded-lg bg-blue-600 py-1.5 text-sm font-medium text-white transition-colors hover:bg-blue-500"
                            wire:click="selectStrategy({{ $strategy->id }})">
                        {{ __('labels.open_btn') }}
                    </button>
                    <button class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                            type="button"
                            @click="openEdit({{ $strategy->id }})">
                        <svg class="h-4 w-4"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                    </button>
                    <button class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-red-500"
                            type="button"
                            @click="confirmArchive({{ $strategy->id }})">
                        <svg class="h-4 w-4"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </button>
                </div>

            </div>
        @endforeach
    </div>
@endif
