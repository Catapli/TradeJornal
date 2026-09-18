{{-- Autopsia de la cuenta quemada. Una vez por cuenta y solo durante el mes siguiente. --}}
<div>
    @if ($this->account())
        @php
            $summary = $this->summary();
            $mistakes = $this->costliestMistakes();
            $currency = $this->account()->currency ?? 'USD';
        @endphp

        <div class="mb-5 overflow-hidden rounded-2xl border border-red-200 bg-white dark:border-red-500/30 dark:bg-gray-800">

            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-red-100 bg-red-50 px-6 py-4 dark:border-red-500/20 dark:bg-red-500/10">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-fire-flame-curved mt-1 text-red-500"></i>
                    <div>
                        <h2 class="text-base font-black text-red-900 dark:text-red-200">
                            {{ __('recovery.title', ['account' => $this->account()->name]) }}
                        </h2>
                        <p class="mt-0.5 text-xs text-red-800/80 dark:text-red-300/80">
                            {{ __('recovery.lead', [
                                'trades' => $summary['trades'],
                                'pnl' => number_format($summary['pnl'], 2, ',', '.') . ' ' . $currency,
                                'percent' => number_format($summary['percent'], 2, ',', '.'),
                            ]) }}
                        </p>
                    </div>
                </div>

                <button class="shrink-0 text-xs font-semibold text-red-700/70 underline transition hover:text-red-900 dark:text-red-300/70 dark:hover:text-red-200"
                        type="button"
                        wire:click="dismiss">{{ __('recovery.dismiss') }}</button>
            </div>

            <div class="grid gap-6 px-6 py-5 md:grid-cols-2">

                {{-- Los errores más caros, en dinero --}}
                <div>
                    @forelse ($mistakes as $mistake)
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-2 last:border-0 dark:border-gray-700/60">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $mistake['name'] }}</span>
                            <span class="shrink-0 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                {{ __('recovery.mistake_line', [
                                    'count' => $mistake['count'],
                                    'cost' => number_format($mistake['cost'], 2, ',', '.') . ' ' . $currency,
                                ]) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('recovery.no_mistakes') }}</p>
                    @endforelse
                </div>

                {{-- Oferta. El descuento solo se anuncia si hay código configurado:
                     no se promete una rebaja que el checkout no vaya a aplicar. --}}
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900/50">
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('recovery.cta_title') }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ __('recovery.cta_text') }}</p>

                    <a class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-700"
                       href="{{ route('pricing') }}">
                        {{ __('recovery.cta') }}
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>

                    @if ($this->coupon())
                        <p class="mt-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                            {{ __('recovery.cta_coupon', ['coupon' => $this->coupon()]) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
