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
        'primary' => ['icon' => 'text-indigo-600',  'bg' => 'bg-indigo-50'],
        'success' => ['icon' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
        'blue'    => ['icon' => 'text-blue-600',    'bg' => 'bg-blue-50'],
        'warning' => ['icon' => 'text-amber-500',   'bg' => 'bg-amber-50'],
        'purple'  => ['icon' => 'text-violet-600',  'bg' => 'bg-violet-50'],
    ];

    $colors = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
    <div class="mb-3 flex items-start justify-between">
        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ $label }}
        </span>
        <span class="{{ $colors['bg'] }} flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
            <i class="fa-solid fa-{{ $icon }} {{ $colors['icon'] }} text-sm"
               aria-hidden="true"></i>
        </span>
    </div>
    <div class="text-3xl font-black tabular-nums text-gray-900">
        {{ $value }}
    </div>
</div>
