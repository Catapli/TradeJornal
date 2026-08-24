{{--
    Componente: <x-admin.kpi-card>

    Props:
      - label  : string  — texto descriptivo bajo el número
      - value  : string  — valor ya formateado (ej: "1,234")
      - icon   : string  — nombre del icono FontAwesome (ej: "users")
      - color  : string  — primary | success | blue | warning | purple
--}}
@props([
    'label' => '',
    'value' => '0',
    'icon' => 'chart-bar',
    'color' => 'primary',
])

@php
    $colorMap = [
        'primary' => ['icon' => 'text-indigo-600 dark:text-indigo-400',  'bg' => 'bg-indigo-50 dark:bg-indigo-900/40'],
        'success' => ['icon' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/40'],
        'blue'    => ['icon' => 'text-blue-600 dark:text-blue-400',    'bg' => 'bg-blue-50 dark:bg-blue-900/40'],
        'warning' => ['icon' => 'text-amber-500 dark:text-amber-400',   'bg' => 'bg-amber-50 dark:bg-amber-900/40'],
        'purple'  => ['icon' => 'text-violet-600 dark:text-violet-400',  'bg' => 'bg-violet-50 dark:bg-violet-900/40'],
    ];

    $colors = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition-shadow hover:shadow-md">
    <div class="mb-3 flex items-start justify-between">
        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ $label }}
        </span>
        <span class="{{ $colors['bg'] }} flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
            <i class="fa-solid fa-{{ $icon }} {{ $colors['icon'] }} text-sm"
               aria-hidden="true"></i>
        </span>
    </div>
    <div class="text-3xl font-black tabular-nums text-gray-900 dark:text-gray-100">
        {{ $value }}
    </div>
</div>
