@props(['objective', 'currency' => '$'])

@php
    // 1. CONFIGURACIÓN VISUAL
    // Detectamos si es una regla de "No perder" (Loss) o de "Ganar" (Target)
    $isLossRule = in_array($objective['type'], ['max_daily_loss', 'max_total_loss']);

    // Iconos según el tipo
    $icon = match ($objective['type']) {
        'profit_target' => 'fa-solid fa-trophy',
        'max_daily_loss' => 'fa-solid fa-fire',
        'max_total_loss' => 'fa-solid fa-skull-crossbones',
        'min_trading_days' => 'fa-regular fa-calendar-check',
        default => 'fa-solid fa-scale-balanced',
    };

    // Colores del Badge de Estado
    $statusClasses = match ($objective['status']) {
        'passed' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30',
        'failed' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/30',
        'passing' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/30', // En regla (para pérdidas)
        default => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600', // En progreso
    };

    // Traducción de estado
    $statusLabel = match ($objective['status']) {
        'passed' => __('labels.surpassed'),
        'failed' => __('labels.failed'),
        'passing' => __('labels.passing'),
        'ongoing' => __('labels.ongoing'),
        default => '---',
    };

    // 2. CÁLCULO DE LA BARRA DE PROGRESO
    $pct = 0;
    if ($objective['target_value'] > 0) {
        $pct = ($objective['current_value'] / $objective['target_value']) * 100;
        $pct = min(100, max(0, $pct)); // Limitar entre 0% y 100%
    }

    // Color de la barra
    $barColor = 'bg-blue-500';

    if ($isLossRule) {
        // Para pérdidas: Verde (seguro) -> Naranja (cuidado) -> Rojo (peligro)
        if ($pct < 50) {
            $barColor = 'bg-emerald-500';
        } elseif ($pct < 85) {
            $barColor = 'bg-amber-400';
        } else {
            $barColor = 'bg-red-500';
        }
    } else {
        // Para ganancias: Azul -> Verde al completar
        if ($objective['status'] === 'passed') {
            $barColor = 'bg-emerald-500';
        }
    }
@endphp

<div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">

    {{-- Fondo decorativo hover --}}
    <div class="absolute right-0 top-0 -mr-4 -mt-4 h-24 w-24 rounded-full bg-gray-50 transition-transform duration-500 group-hover:scale-150 dark:bg-gray-700/30"></div>

    {{-- HEADER --}}
    <div class="relative z-10 flex items-start justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 shadow-inner dark:bg-gray-700 dark:text-gray-300">
                <i class="{{ $icon }}"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ $objective['label'] }}</h4>
                @if ($objective['is_hard_rule'])
                    <span class="text-[10px] font-bold uppercase tracking-wider text-red-500">{{ __('labels.strict_rule') }}</span>
                @endif
            </div>
        </div>

        <span class="{{ $statusClasses }} rounded-lg border px-2 py-1 text-[10px] font-bold uppercase tracking-wide">
            {{ $statusLabel }}
        </span>
    </div>

    {{-- BODY: VALORES --}}
    <div class="relative z-10 mt-5 flex items-baseline gap-1">
        @if ($objective['unit'] === 'money')
            <span class="text-2xl font-black text-gray-900 dark:text-gray-100">
                {{ number_format($objective['current_value'], 2) }} . {{ $currency }}
            </span>
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">
                / {{ number_format($objective['target_value'], 0) }} {{ $currency }}
            </span>
        @elseif($objective['unit'] === 'days')
            <span class="text-2xl font-black text-gray-900 dark:text-gray-100">
                {{ $objective['current_value'] }}
            </span>
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">
                / {{ $objective['target_value'] }} {{ __('labels.days') }}
            </span>
        @else
            <span class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $objective['target_value'] }}</span>
        @endif
    </div>

    {{-- FOOTER: BARRA DE PROGRESO --}}
    @if (in_array($objective['unit'], ['money', 'days']))
        <div class="relative z-10 mt-3">
            <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-400 dark:text-gray-500">
                <span>{{ __('labels.progress') }}</span>
                <span>{{ number_format($pct, 1) }}%</span>
            </div>

            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 shadow-inner dark:bg-gray-700">
                <div class="{{ $barColor }} h-full rounded-full transition-all duration-700 ease-out"
                     style="width: {{ $pct }}%"></div>
            </div>

            {{-- Texto de ayuda para reglas de pérdida --}}
            @if ($isLossRule && $objective['status'] !== 'failed')
                <p class="mt-1 text-right text-[10px] text-gray-400 dark:text-gray-500">
                    {{ __('labels.you_have_left') }} <span class="font-bold text-gray-600 dark:text-gray-300">{{ number_format($objective['target_value'] - $objective['current_value'], 2) }} {{ $currency }}</span>
                </p>
            @endif
        </div>
    @endif
</div>
