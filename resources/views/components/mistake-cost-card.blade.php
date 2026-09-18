@props(['data', 'compact' => true])

{{-- Coste de los errores (Fase 5 · P2).

     `compact` = tarjeta de portada: titular, top-3 y cobertura. Sin compact sale
     el desglose entero, que es lo que se pinta en el Laboratorio. --}}

@php
    $cost = (float) ($data['cost'] ?? 0);
    $marked = (int) ($data['marked_trades'] ?? 0);
    $top = array_slice($data['by_mistake'] ?? [], 0, $compact ? 3 : 100);

    // El dinero se escribe en el formato del idioma activo: 1.234,56 en español,
    // 1,234.56 en inglés. number_format con la coma puesta a mano fallaba en en.
    $money = fn (float $n): string => app()->getLocale() === 'es'
        ? number_format(abs($n), 2, ',', '.')
        : number_format(abs($n), 2, '.', ',');
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-fire-flame-curved mt-1 text-rose-500"></i>

            <div class="min-w-0">
                <h3 class="text-[11px] font-black uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('mistake_cost.title') }}</h3>

                @if (($data['trades_total'] ?? 0) === 0)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('mistake_cost.no_trades') }}</p>
                @elseif ($marked === 0)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('mistake_cost.no_marked') }}</p>
                @else
                    <p @class([
                        'text-lg font-black leading-tight',
                        'text-rose-600 dark:text-rose-400' => $cost > 0,
                        'text-emerald-600 dark:text-emerald-400' => $cost < 0,
                        'text-gray-900 dark:text-gray-100' => $cost == 0,
                    ])>
                        @if ($cost > 0)
                            {{ __('mistake_cost.headline_cost', ['amount' => $money($cost) . ' €']) }}
                        @elseif ($cost < 0)
                            {{ __('mistake_cost.headline_gain', ['amount' => $money($cost) . ' €']) }}
                        @else
                            {{ __('mistake_cost.headline_neutral') }}
                        @endif
                    </p>

                    @if ($cost != 0)
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                            {{ __('mistake_cost.without_them', [
                                'amount' => ($data['pnl_without'] >= 0 ? '+' : '-') . $money((float) $data['pnl_without']) . ' €',
                                'real' => ($data['real_pnl'] >= 0 ? '+' : '-') . $money((float) $data['real_pnl']) . ' €',
                            ]) }}
                        </p>
                    @endif
                @endif

                {{-- La cobertura va con el número, no en una nota al pie: sin ella
                     no se sabe si esto sale de treinta operaciones o de trescientas. --}}
                @if (($data['trades_total'] ?? 0) > 0)
                    <p class="mt-2 text-[11px] leading-relaxed text-gray-400 dark:text-gray-500">
                        {{ __('mistake_cost.coverage', [
                            'reviewed' => $data['reviewed'],
                            'total' => $data['trades_total'],
                            'percent' => rtrim(rtrim(number_format((float) $data['coverage'], 1, ',', ''), '0'), ','),
                        ]) }}
                        @if (($data['pending_review'] ?? 0) > 0)
                            {{ __('mistake_cost.coverage_warning') }}
                        @endif
                    </p>

                    {{-- Avisar de que falta cobertura sin dar la salida es dejar al
                         usuario con el problema. El repaso está a un clic. --}}
                    @if (($data['pending_review'] ?? 0) > 0)
                        <a class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-bold text-indigo-600 underline transition hover:text-indigo-700 dark:text-indigo-400"
                           href="{{ route('review') }}"
                           wire:navigate>
                            <i class="fa-solid fa-clipboard-check text-[10px]"></i>
                            {{ __('review.cta_pending', ['count' => $data['pending_review']]) }}
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if (!empty($top))
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($top as $row)
                <div class="flex items-center justify-between gap-3 px-5 py-2.5">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                              style="background-color: {{ $row['color'] }}"></span>
                        <span class="truncate text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $row['name'] }}</span>
                        <span class="shrink-0 text-[11px] text-gray-400">
                            {{ trans_choice('mistake_cost.trades_count', $row['count'], ['count' => $row['count']]) }}
                        </span>
                    </div>

                    <div class="shrink-0 text-right">
                        <span @class([
                            'font-mono text-sm font-black',
                            'text-rose-600 dark:text-rose-400' => $row['cost'] > 0,
                            'text-emerald-600 dark:text-emerald-400' => $row['cost'] <= 0,
                        ])>
                            {{ $row['cost'] > 0 ? '-' : '+' }}{{ $money((float) $row['cost']) }} €
                        </span>

                        @unless ($compact)
                            <span class="ml-2 text-[11px] text-gray-400">
                                {{ __('mistake_cost.avg_column') }} {{ $row['avg_cost'] > 0 ? '-' : '+' }}{{ $money((float) $row['avg_cost']) }} €
                            </span>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>

        <p class="border-t border-gray-100 px-5 py-2.5 text-[11px] leading-relaxed text-gray-400 dark:border-gray-700 dark:text-gray-500">
            {{ __('mistake_cost.overlap_note') }}
        </p>
    @endif
</div>
