{{--
    Tarjeta de rachas y disciplina del panel (R3).

    Amplía los chips de la barra: qué cuenta cada racha, qué la rompe y cómo ha
    ido el mes día a día. El calendario solo pinta los días transcurridos — un
    mes lleno de huecos futuros parece un mes fallado.
--}}
@php
    $streaks = app(\App\Actions\Retention\CalculateStreaks::class)->execute(auth()->user());
    $calendar = $streaks['calendar'];

    $blocks = [
        [
            'key' => 'journal',
            'value' => $streaks['journal']['current'],
            'icon' => 'fa-pen-nib',
            'classes' => 'text-indigo-600 dark:text-indigo-400',
        ],
        [
            'key' => 'clean',
            'value' => $streaks['clean']['current'],
            'icon' => 'fa-shield-halved',
            'classes' => 'text-emerald-600 dark:text-emerald-400',
        ],
        [
            'key' => 'plan',
            'value' => $streaks['plan']['current'],
            'icon' => 'fa-bullseye',
            'classes' => 'text-amber-600 dark:text-amber-400',
        ],
    ];
@endphp

<div class="mb-5 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700">
        <div>
            <h2 class="text-base font-black text-gray-900 dark:text-gray-100">{{ __('streaks.title') }}</h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('streaks.subtitle') }}</p>
        </div>

        @unless ($streaks['journal']['today'])
            <a class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-indigo-700"
               href="{{ route('journal') }}">{{ __('streaks.write_today') }}</a>
        @endunless
    </div>

    <div class="grid gap-5 px-5 py-5 md:grid-cols-3">
        @foreach ($blocks as $block)
            <div>
                <p class="flex items-baseline gap-2">
                    <i class="fa-solid {{ $block['icon'] }} {{ $block['classes'] }}"></i>
                    <span class="text-2xl font-black tabular-nums text-gray-900 dark:text-gray-100">{{ $block['value'] }}</span>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        {{ trans_choice('streaks.' . $block['key'] . '.unit', $block['value']) }}
                    </span>
                </p>
                <p class="mt-1 text-sm font-bold text-gray-800 dark:text-gray-200">{{ __('streaks.' . $block['key'] . '.label') }}</p>
                <p class="mt-0.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ __('streaks.' . $block['key'] . '.tooltip') }}</p>
            </div>
        @endforeach
    </div>

    {{-- Calendario del mes --}}
    <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-700">
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach ($calendar as $day)
                @php
                    $tone = match (true) {
                        $day['severe'] => 'severe',
                        $day['journal'] => 'journal',
                        $day['traded'] => 'traded',
                        $day['weekend'] => 'weekend',
                        default => 'empty',
                    };
                    $label = __('streaks.legend.' . $tone);
                @endphp

                <span @class([
                    'flex h-7 w-7 items-center justify-center rounded-md text-[10px] font-bold tabular-nums',
                    'bg-red-500 text-white' => $tone === 'severe',
                    'bg-indigo-500 text-white' => $tone === 'journal',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' => $tone === 'traded',
                    'bg-gray-50 text-gray-300 dark:bg-gray-900/40 dark:text-gray-600' => $tone === 'weekend',
                    'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => $tone === 'empty',
                ])
                      title="{{ $label }}">{{ $day['day'] }}</span>
            @endforeach
        </div>

        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-500 dark:text-gray-400">
            <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-sm bg-indigo-500 align-middle"></span>{{ __('streaks.legend.journal') }}</span>
            <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-sm bg-emerald-200 align-middle dark:bg-emerald-500/40"></span>{{ __('streaks.legend.traded') }}</span>
            <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-sm bg-red-500 align-middle"></span>{{ __('streaks.legend.severe') }}</span>
        </div>
    </div>
</div>
