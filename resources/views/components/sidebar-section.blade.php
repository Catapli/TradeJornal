@props(['title'])

{{--
    Separador entre bloques del carril.

    Con el carril cerrado es una rayita centrada; al desplegarse se cambia por el
    nombre del bloque. Los dos van posicionados en absoluto dentro de una altura
    fija para que el menú no dé un salto al pasar el ratón.
--}}
<li class="relative h-8 w-full"
    aria-hidden="true">
    <span class="absolute left-1/2 top-1/2 h-px w-8 -translate-x-1/2 -translate-y-1/2 bg-gray-800 transition-opacity duration-150 group-hover/sidebar:opacity-0"></span>

    <span class="absolute left-[18px] top-1/2 -translate-y-1/2 whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-gray-500 opacity-0 transition-opacity duration-200 group-hover/sidebar:opacity-100">
        {{ $title }}
    </span>
</li>
