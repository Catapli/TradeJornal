@props([
    // 'public' → los CTA llevan a registro/login.  'app' → los CTA llaman a
    // wire:click="subscribe(...)" del componente Livewire Settings\Subscription.
    'mode' => 'public',
    'subscribed' => false,
    'showCompare' => true,
])

{{--
    Tabla de precios compartida por la landing pública (welcome.blade.php),
    la página /pricing y el bloque de suscripción dentro de la app.

    Fuente única de la matriz de funcionalidades: lang/{locale}/landing.php →
    pricing.features. Si cambia lo que incluye un plan, se cambia ahí y se
    actualizan los tres sitios a la vez.
--}}

@php
    $features = __('landing.pricing.features');
    $monthly = '9,99 €';
    $yearly = '100 €';

    $trialDays = (int) config('billing.trial_days');
    $trialUser = auth()->user();
    $onTrial = $trialUser?->onProTrial() ?? false;
@endphp

<div x-data="{ yearly: false }">

    {{-- Prueba sin tarjeta: se anuncia arriba del todo porque es lo que quita el
         miedo a registrarse, y se sustituye por los días restantes si ya corre. --}}
    @if ($onTrial)
        <p class="mb-6 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-4 py-1.5 text-xs font-bold text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300">
                <i class="fa-solid fa-hourglass-half"></i>
                {{ __('landing.pricing.trialing', ['days' => $trialUser->trialDaysLeft()]) }}
            </span>
        </p>
    @elseif ($trialDays > 0 && ! $subscribed)
        <p class="mb-6 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300 bg-emerald-50 px-4 py-1.5 text-xs font-bold text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300">
                <i class="fa-solid fa-gift"></i>
                {{ __('landing.pricing.trial_badge', ['days' => $trialDays]) }}
            </span>
        </p>
    @endif

    {{-- ── Conmutador mensual / anual ───────────────────────────── --}}
    <div class="flex items-center justify-center gap-3">
        <span class="text-sm font-semibold transition"
              :class="yearly ? 'text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-gray-100'">
            {{ __('landing.pricing.monthly') }}
        </span>

        <button class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-300 transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:bg-gray-700 dark:focus-visible:ring-offset-gray-900"
                :class="yearly ? '!bg-indigo-600' : ''"
                @click="yearly = !yearly"
                type="button"
                role="switch"
                :aria-checked="yearly.toString()"
                aria-label="{{ __('landing.pricing.yearly') }}">
            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                  :class="yearly ? 'translate-x-5' : 'translate-x-0'"></span>
        </button>

        <span class="text-sm font-semibold transition"
              :class="yearly ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500'">
            {{ __('landing.pricing.yearly') }}
        </span>

        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
            {{ __('landing.pricing.save') }}
        </span>
    </div>

    {{-- ── Las dos tarjetas ─────────────────────────────────────── --}}
    <div class="mx-auto mt-10 grid max-w-4xl gap-6 md:grid-cols-2">

        {{-- FREE --}}
        <div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-8 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('landing.pricing.free.name') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('landing.pricing.free.claim') }}</p>

            <div class="mt-6 flex items-baseline gap-1">
                <span class="text-4xl font-black text-gray-900 dark:text-gray-100">{{ __('landing.pricing.free.price') }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('landing.pricing.per_month') }}</span>
            </div>

            <ul class="mt-6 flex-1 space-y-3 text-sm">
                @foreach ($features as $feature)
                    @if ($feature['free'] !== false)
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-check mt-1 text-xs text-emerald-500"></i>
                            <span class="text-gray-600 dark:text-gray-300">
                                {{ $feature['label'] }}@if ($feature['free'] !== true)<span class="ml-1 font-semibold text-gray-900 dark:text-gray-100">· {{ $feature['free'] }}</span>@endif
                            </span>
                        </li>
                    @endif
                @endforeach
            </ul>

            <div class="mt-8">
                @auth
                    <span class="block w-full rounded-lg border border-gray-200 py-3 text-center text-sm font-semibold text-gray-400 dark:border-gray-800 dark:text-gray-500">
                        {{ $subscribed ? '—' : __('landing.pricing.current') }}
                    </span>
                @else
                    <a class="block w-full rounded-lg border border-gray-300 py-3 text-center text-sm font-bold text-gray-900 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-800"
                       href="{{ route('register') }}">
                        {{ __('landing.pricing.free_cta') }}
                    </a>
                @endauth
            </div>
        </div>

        {{-- PRO --}}
        <div class="relative flex flex-col rounded-2xl border-2 border-indigo-600 bg-white p-8 shadow-xl shadow-indigo-600/10 dark:bg-gray-900">
            <span class="absolute -top-3 left-8 rounded-full bg-indigo-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">
                {{ __('landing.pricing.popular') }}
            </span>

            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('landing.pricing.pro.name') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('landing.pricing.pro.claim') }}</p>

            <div class="mt-6 flex items-baseline gap-1">
                <span class="text-4xl font-black text-gray-900 dark:text-gray-100"
                      x-text="yearly ? '{{ $yearly }}' : '{{ $monthly }}'">{{ $monthly }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400"
                      x-text="yearly ? '{{ __('landing.pricing.per_year') }}' : '{{ __('landing.pricing.per_month') }}'">{{ __('landing.pricing.per_month') }}</span>
            </div>

            <ul class="mt-6 flex-1 space-y-3 text-sm">
                @foreach ($features as $feature)
                    @if ($feature['pro'] !== false)
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-check mt-1 text-xs text-indigo-500"></i>
                            <span class="text-gray-600 dark:text-gray-300">
                                {{ $feature['label'] }}@if ($feature['pro'] !== true)<span class="ml-1 font-semibold text-gray-900 dark:text-gray-100">· {{ $feature['pro'] }}</span>@endif
                            </span>
                        </li>
                    @endif
                @endforeach
            </ul>

            @if ($trialDays > 0 && ! $subscribed)
                <p class="mt-5 rounded-lg bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    {{ __('landing.pricing.trial_note', ['days' => $trialDays]) }}
                </p>
            @endif

            <div class="mt-8">
                @if ($subscribed)
                    <span class="block w-full rounded-lg bg-emerald-50 py-3 text-center text-sm font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <i class="fa-solid fa-circle-check mr-1"></i>{{ __('landing.pricing.current') }}
                    </span>
                @elseif ($mode === 'app')
                    <button class="block w-full rounded-lg bg-indigo-600 py-3 text-center text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60"
                            type="button"
                            @click="$wire.subscribe(yearly ? 'yearly' : 'monthly')"
                            wire:loading.attr="disabled"
                            wire:target="subscribe">
                        <span wire:loading.remove wire:target="subscribe">{{ __('landing.pricing.pro_cta') }}</span>
                        <span wire:loading wire:target="subscribe">
                            <i class="fa-solid fa-circle-notch fa-spin mr-1"></i>{{ __('labels.loading') }}
                        </span>
                    </button>
                @elseif (auth()->check())
                    <a class="block w-full rounded-lg bg-indigo-600 py-3 text-center text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700"
                       href="{{ route('pricing') }}">
                        {{ __('landing.pricing.pro_cta') }}
                    </a>
                @else
                    <a class="block w-full rounded-lg bg-indigo-600 py-3 text-center text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700"
                       href="{{ route('register') }}">
                        {{ __('landing.pricing.pro_cta') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Comparativa completa ─────────────────────────────────── --}}
    @if ($showCompare)
        <div class="mx-auto mt-14 max-w-4xl">
            <h3 class="mb-4 text-center text-xs font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
                {{ __('landing.pricing.compare') }}
            </h3>

            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
                <table class="w-full min-w-[520px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-left dark:border-gray-800 dark:bg-gray-900">
                            <th class="px-5 py-3 font-semibold text-gray-500 dark:text-gray-400" scope="col"></th>
                            <th class="w-32 px-5 py-3 text-center font-bold text-gray-900 dark:text-gray-100" scope="col">{{ __('landing.pricing.free.name') }}</th>
                            <th class="w-32 px-5 py-3 text-center font-bold text-indigo-600 dark:text-indigo-400" scope="col">{{ __('landing.pricing.pro.name') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($features as $feature)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800/60">
                                <th class="px-5 py-3 text-left font-medium text-gray-700 dark:text-gray-300" scope="row">{{ $feature['label'] }}</th>

                                {{-- Clases literales a propósito: Tailwind no escanea clases
                                     construidas por concatenación y el safelist se eliminó. --}}
                                @foreach (['free', 'pro'] as $plan)
                                    <td class="px-5 py-3 text-center">
                                        @if ($feature[$plan] === true)
                                            <i class="fa-solid fa-check {{ $plan === 'pro' ? 'text-indigo-500' : 'text-emerald-500' }}"
                                               aria-label="{{ __('labels.yes') }}"></i>
                                        @elseif ($feature[$plan] === false)
                                            <i class="fa-solid fa-minus text-gray-300 dark:text-gray-700"
                                               aria-label="{{ __('labels.no') }}"></i>
                                        @else
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $feature[$plan] }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
