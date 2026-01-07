@props(['label' => '', 'icono' => '', 'key' => ''])

<div {!! $attributes->merge(['class' => 'grid grid-cols-12 rounded-lg border border-gray-500 py-2 m-2 ']) !!}>
    <div class="col-span-2 flex items-center justify-center text-xl">
        <span class="h-11 w-11 rounded-xl border border-green-700 p-2 text-center text-green-700">{!! $icono !!}</span>
    </div>
    <div class="col-span-7 flex flex-col">
        <span class="text-sm text-gray-500"> {{ $label }}
        </span>
        <span>
            {{ $key }}
        </span>
    </div>
    <div class="col-span-3 flex justify-end px-3">
        <i class="fa-solid fa-circle-info"></i>
    </div>
</div>
