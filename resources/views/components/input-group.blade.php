@props(['disabled' => false, 'id' => '', 'placeholder' => '', 'icono' => '', 'tooltip' => '', 'lenght' => ''])

<div {!! $attributes->merge(['class' => 'px-2 py-1 relative justify-items-center']) !!}>
    <div class="peer flex w-full rounded-lg shadow-sm">
        <div class="inline-flex min-w-[55px] items-center justify-center rounded-s-md border border-e-0 border-gray-800 bg-primary px-4 dark:border-neutral-600 dark:bg-neutral-700">
            <span class="text-lg text-white dark:text-neutral-400">{!! $icono !!}</span>
        </div>
        <input id="{{ $id }}"
               autocomplete="off"
               maxlength="{{ $lenght }}"
               {!! $attributes->merge([
                   'class' =>
                       'op-full shadow-inner block w-full text-black rounded-e-lg border-gray-200 px-3 py-3 text-sm  shadow-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:pointer-events-none disabled:opacity-50  dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600',
               ]) !!}
               placeholder="{{ $placeholder }}"
               {{ $disabled ? 'disabled' : '' }}
               type="text">
    </div>
    <div class="absolute -bottom-4 left-1/2 z-20 -translate-x-1/2 translate-y-2 scale-95 cursor-default whitespace-nowrap rounded bg-neutral-950 px-2 py-1 text-center text-sm text-white opacity-0 transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 peer-focus:translate-y-0 peer-focus:scale-100 peer-focus:opacity-100 dark:bg-white dark:text-neutral-900"
         role="tooltip">
        {{ $tooltip }}
    </div>
</div>
