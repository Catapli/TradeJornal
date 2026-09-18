{{-- Guía de puesta en marcha. Se pinta solo mientras quede algún paso pendiente. --}}
<div>
    @if ($this->shouldShow())
        @php
            $total = count($done);
            $completed = $this->completed();
            $steps = [
                'account' => ['icon' => 'fa-wallet', 'route' => route('cuentas'), 'alt' => null],
                'trades' => ['icon' => 'fa-file-import', 'route' => route('trades.import'), 'alt' => route('trades')],
                'goals' => ['icon' => 'fa-bullseye', 'route' => route('cuentas'), 'alt' => null],
            ];
        @endphp

        <div class="mb-5 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
             x-data="tfMinimizable('onboarding')">

            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
                 :class="min || 'border-b border-gray-100 dark:border-gray-700'">
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-gray-100">{{ __('onboarding.title') }}</h2>
                    {{-- El subtítulo se va con el cuerpo: plegada, la tarjeta es una línea. --}}
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                       x-show="!min">{{ __('onboarding.subtitle') }}</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-28 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                                 style="width: {{ $total > 0 ? round($completed / $total * 100) : 0 }}%"></div>
                        </div>
                        <span class="text-xs font-bold tabular-nums text-gray-500 dark:text-gray-400">
                            {{ __('onboarding.progress', ['done' => $completed, 'total' => $total]) }}
                        </span>
                    </div>

                    <button class="text-xs font-semibold text-gray-400 underline transition hover:text-gray-600 dark:hover:text-gray-200"
                            type="button"
                            wire:click="dismiss">{{ __('onboarding.dismiss') }}</button>

                    <button class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            type="button"
                            @click="toggle()"
                            :aria-expanded="(!min).toString()"
                            :title="min ? @js(__('labels.card_expand')) : @js(__('labels.card_minimize'))"
                            :aria-label="min ? @js(__('labels.card_expand')) : @js(__('labels.card_minimize'))">
                        <i class="fa-solid fa-chevron-up text-xs transition-transform"
                           :class="min && 'rotate-180'"></i>
                    </button>
                </div>
            </div>

            <ol class="divide-y divide-gray-100 dark:divide-gray-700"
                x-show="!min"
                x-collapse>
                @foreach ($steps as $key => $meta)
                    @php($isDone = $done[$key] ?? false)
                    <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <span @class([
                                'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm',
                                'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400' => $isDone,
                                'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-400' => ! $isDone,
                            ])>
                                <i class="fa-solid {{ $isDone ? 'fa-check' : $meta['icon'] }}"></i>
                            </span>

                            <div>
                                <p @class([
                                    'text-sm font-bold',
                                    'text-gray-400 line-through dark:text-gray-500' => $isDone,
                                    'text-gray-900 dark:text-gray-100' => ! $isDone,
                                ])>{{ __('onboarding.steps.' . $key . '.title') }}</p>

                                @unless ($isDone)
                                    <p class="mt-0.5 max-w-xl text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('onboarding.steps.' . $key . '.text') }}
                                    </p>
                                @endunless
                            </div>
                        </div>

                        @unless ($isDone)
                            <div class="flex shrink-0 flex-wrap items-center gap-2 pl-12 sm:pl-0">
                                <a class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-indigo-700"
                                   href="{{ $meta['route'] }}">{{ __('onboarding.steps.' . $key . '.cta') }}</a>

                                @if ($meta['alt'])
                                    <a class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                       href="{{ $meta['alt'] }}">{{ __('onboarding.steps.' . $key . '.cta_alt') }}</a>
                                @endif
                            </div>
                        @endunless
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</div>
