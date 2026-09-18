@props(['href', 'icon', 'label'])

{{--
    Acceso secundario dentro de una pantalla.

    Es el sustituto de los iconos que salieron del carril lateral: sitios a los
    que se va de vez en cuando (importar, conexión, histórico, backtesting) y que
    no merecen sitio permanente en el menú, pero sí un botón visible en la
    pantalla con la que tienen que ver.
--}}
<a {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-indigo-400']) }}
   href="{{ $href }}"
   wire:navigate>
    <i class="{{ $icon }} text-xs"></i>
    <span>{{ $label }}</span>
</a>
