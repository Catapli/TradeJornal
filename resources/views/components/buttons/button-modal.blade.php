@props([
    'disabled' => 'false', // Prop opcional con valor predeterminado
    'icon' => 'fa-solid fa-eraser',
    'tooltip' => 'Reiniciar Filtros',
    'text_icon' => '',
])

<div {!! $attributes->merge(['class' => 'relative justify-items-center']) !!}>
    <button class="shadow_btn peer flex w-full items-center justify-center gap-x-2 rounded-md border border-transparent bg-primary px-5 py-1 text-base font-medium text-white transition-all duration-500 hover:scale-110 hover:bg-secondary focus:outline-none dark:bg-white dark:text-neutral-800"
            type="button"
            :disabled="{{ $disabled }}">
        <i class="{{ $icon }} text-2xl"></i>
        @if ($text_icon != '')
            {{ $text_icon }}
        @endif
    </button>
    <div class="absolute -bottom-8 left-1/2 z-10 -translate-x-1/2 translate-y-2 scale-95 whitespace-nowrap rounded bg-neutral-950 px-2 py-1 text-center text-sm text-white opacity-0 transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 dark:bg-white dark:text-neutral-900"
         role="tooltip">
        {{ $tooltip }}
    </div>
</div>
