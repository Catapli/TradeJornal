<div class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 p-[1px] shadow-sm">
    <div class="flex h-full flex-col justify-between rounded-[11px] bg-white px-4 py-1">

        {{-- Cabecera --}}
        <div class="mb-2 flex items-center justify-between">
            <h4 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-600">
                <i class="fa-solid fa-lightbulb text-yellow-400"></i> Tip del Día
            </h4>

            {{-- ACCIONES (Solo visibles si hay tip) --}}
            @if ($tip)
                <div class="flex items-center gap-3">
                    {{-- 1. Botón Regenerar --}}
                    <button class="text-gray-300 transition-colors hover:text-indigo-500 disabled:opacity-50"
                            wire:click="generateTip"
                            wire:loading.attr="disabled"
                            title="Regenerar consejo">
                        <i class="fa-solid fa-rotate text-xs"
                           wire:loading.remove
                           wire:target="generateTip"></i>
                        <i class="fa-solid fa-circle-notch fa-spin text-xs"
                           wire:loading
                           wire:target="generateTip"></i>
                    </button>

                    {{-- 2. Botón Cerrar (Borra caché y oculta) --}}
                    <button class="text-gray-300 transition-colors hover:text-red-500"
                            wire:click="closeTip"
                            title="Cerrar y borrar">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
            @endif
        </div>

        {{-- Contenido --}}
        <div class="flex-grow">

            {{-- 1. SKELETON LOADER --}}
            <div class="flex w-full items-center gap-3 py-2"
                 wire:loading.flex
                 wire:target="generateTip">
                <div class="h-8 w-8 flex-shrink-0 animate-spin rounded-full border-2 border-indigo-100 border-t-indigo-500"></div>
                <div class="w-full space-y-2">
                    <div class="h-2 w-3/4 animate-pulse rounded bg-gray-100"></div>
                    <div class="h-2 w-1/2 animate-pulse rounded bg-gray-100"></div>
                </div>
            </div>

            {{-- 2. CONTENIDO REAL --}}
            <div wire:loading.remove
                 wire:target="generateTip">
                @if ($tip)
                    <p class="animate-in fade-in text-sm font-medium leading-relaxed text-gray-700 duration-500">
                        {!! Str::markdown($tip) !!}
                    </p>
                @else
                    {{-- Estado inicial / Botón Generar --}}
                    <div class="animate-in fade-in flex items-center justify-between py-1">
                        <p class="text-xs text-gray-400">Descubre patrones ocultos en tu operativa reciente.</p>
                        <button class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600 transition-colors hover:bg-indigo-100"
                                wire:click="generateTip">
                            Generar <i class="fa-solid fa-wand-magic-sparkles ml-1"></i>
                        </button>
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>
