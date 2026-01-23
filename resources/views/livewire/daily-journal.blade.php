<div class="flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    {{-- Cabecera --}}
    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-5 py-4">
        <h3 class="flex items-center gap-2 font-bold text-gray-800">
            <i class="fa-solid fa-book text-indigo-500"></i> Bitácora
        </h3>

        <div class="flex rounded-lg border border-gray-200 bg-white p-1 shadow-sm">
            @foreach (['fire' => '🔥', 'happy' => '🙂', 'neutral' => '😐', 'sad' => '😡'] as $key => $emoji)
                <button class="{{ $mood === $key ? 'bg-indigo-100 scale-110 shadow-sm' : 'opacity-60 grayscale' }} flex h-8 w-8 items-center justify-center rounded transition-all hover:bg-gray-100"
                        wire:click="$set('mood', '{{ $key }}')">
                    {{ $emoji }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Textarea con DEFER (Clave para rendimiento) --}}
    <div class="relative flex-grow bg-white p-4">
        <textarea class="h-full w-full resize-none border-0 bg-transparent p-0 text-sm leading-relaxed text-gray-700 placeholder-gray-400 focus:ring-0"
                  wire:model.defer="content"
                  placeholder="Escribe aquí tus notas..."></textarea>

        {{-- Mensaje de guardado --}}
        <div class="absolute bottom-4 right-4 rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 shadow-sm"
             x-data="{ show: @entangle('isSaved') }"
             x-show="show"
             x-init="$watch('show', value => { if (value) setTimeout(() => show = false, 2000) })"
             x-transition>
            Guardado
        </div>
    </div>

    {{-- Footer --}}
    <div class="flex justify-end border-t border-gray-100 bg-gray-50 p-4">
        <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm transition-colors hover:bg-indigo-700"
                wire:click="save"
                wire:loading.attr="disabled">
            <span wire:loading.remove>Guardar</span>
            <span wire:loading><i class="fa-solid fa-circle-notch fa-spin"></i></span>
        </button>
    </div>
</div>
