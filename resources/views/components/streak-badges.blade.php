{{--
    Rachas en la barra superior (R3).

    Tres números y nada más: el detalle está en la tarjeta del panel. Cada chip
    lleva en el `title` la definición exacta de lo que cuenta — una racha que no
    se sabe cómo se rompe no motiva, irrita.

    Componente de Blade y no de Livewire a propósito: no hay nada que pulsar y
    la cifra solo cambia al escribir el diario o etiquetar un error, así que no
    merece una petición por cada carga de página.
--}}
@php
    $streaks = app(\App\Actions\Retention\CalculateStreaks::class)->execute(auth()->user());

    $chips = [
        [
            'value' => $streaks['journal']['current'],
            'icon' => 'fa-pen-nib',
            'title' => __('streaks.journal.tooltip'),
            'tone' => 'indigo',
        ],
        [
            'value' => $streaks['clean']['current'],
            'icon' => 'fa-shield-halved',
            'title' => __('streaks.clean.tooltip'),
            'tone' => 'emerald',
        ],
        [
            'value' => $streaks['plan']['current'],
            'icon' => 'fa-bullseye',
            'title' => __('streaks.plan.tooltip'),
            'tone' => 'amber',
        ],
    ];

    $visible = array_filter($chips, fn ($chip) => $chip['value'] > 0);
@endphp

<div class="flex items-center gap-1.5">

    @forelse ($visible as $chip)
        <span @class([
            'flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold',
            'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300' => $chip['tone'] === 'indigo',
            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-400' => $chip['tone'] === 'emerald',
            'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-400' => $chip['tone'] === 'amber',
        ])
              title="{{ $chip['title'] }}">
            <i class="fa-solid {{ $chip['icon'] }}"></i>
            <span class="tabular-nums">{{ $chip['value'] }}</span>
        </span>
    @empty
        {{-- Sin ninguna racha viva, tres ceros solo dan pena: se ofrece el primer paso. --}}
        <a class="flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-bold text-gray-500 transition hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-indigo-400"
           href="{{ route('journal') }}"
           title="{{ __('streaks.empty.tooltip') }}">
            <i class="fa-solid fa-pen-nib"></i>
            <span>{{ __('streaks.empty.label') }}</span>
        </a>
    @endforelse
</div>
