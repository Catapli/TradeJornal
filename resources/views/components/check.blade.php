@props(['disabled' => false, 'id' => '', 'icono' => '', 'tooltip' => ''])

<div {!! $attributes->merge(['class' => 'px-2 py-1 relative justify-items-center']) !!}>
    <div class="peer flex items-center justify-center gap-2">
        <input id="{{ $id }}"
               {!! $attributes->merge([
                   'class' => ' op-full shadow-inner block cursor-pointer  rounded-lg border-gray-200 p-2 shadow-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:pointer-events-none disabled:opacity-50  ',
               ]) !!}
               {{ $disabled ? 'disabled' : '' }}
               type="checkbox">
        <span class="cursor-pointer text-4xl"
              @click="document.getElementById('{{ $id }}').click()">{!! $icono !!}</span>
    </div>

    <div class="absolute -bottom-5 left-1/2 z-20 -translate-x-1/2 translate-y-2 scale-95 cursor-default whitespace-nowrap rounded bg-neutral-950 px-2 py-1 text-center text-sm text-white opacity-0 transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 peer-focus:translate-y-0 peer-focus:scale-100 peer-focus:opacity-100 dark:bg-white dark:text-neutral-900"
         role="tooltip">
        {{ $tooltip }}
    </div>
</div>
