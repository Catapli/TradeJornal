<div class="flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    {{-- Cabecera --}}
    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-5 py-4">
        <h3 class="flex items-center gap-2 font-bold text-gray-800">
            <i class="fa-solid fa-book text-indigo-500"></i> {{ __('labels.placeholder_bitacora_dashboard') }}
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

    <style>
        #daily-journal-wrapper trix-toolbar { display: none !important; }
        #daily-journal-wrapper trix-editor { border: none !important; box-shadow: none !important; padding: 1rem !important; font-size: 0.875rem; color: #374151; line-height: 1.7; }
    </style>

    {{-- Editor Trix --}}
    <div id="daily-journal-wrapper"
         class="relative flex-grow bg-white"
         wire:ignore
         x-data="{
             init() {
                 this.$nextTick(() => {
                     let trix = this.$refs.trix;
                     if (trix && trix.editor) {
                         trix.editor.loadHTML(@js($content ?? ''));
                     }
                 });

                 addEventListener('trix-attachment-add', (e) => {
                     e.attachment.remove();
                 });
             }
         }">
        <input id="daily-journal-input"
               type="hidden">
        <trix-editor class="trix-content h-full min-h-[200px] border-none focus:outline-none"
                     input="daily-journal-input"
                     x-ref="trix"></trix-editor>

        {{-- Mensaje de guardado --}}
        <div class="absolute bottom-4 right-4 rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 shadow-sm"
             x-data="{ show: @entangle('isSaved') }"
             x-show="show"
             x-init="$watch('show', value => { if (value) setTimeout(() => show = false, 2000) })"
             x-transition>
            {{ __('labels.saved') }}
        </div>
    </div>

    {{-- Footer --}}
    <div class="flex justify-end border-t border-gray-100 bg-gray-50 p-4">
        <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm transition-colors hover:bg-indigo-700"
                @click="
                    let trix = document.querySelector('#daily-journal-input + trix-editor') || document.querySelector('trix-editor[input=daily-journal-input]');
                    $wire.set('content', document.getElementById('daily-journal-input').value);
                    $wire.save();
                "
                wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('labels.save') }}</span>
            <span wire:loading><i class="fa-solid fa-circle-notch fa-spin"></i></span>
        </button>
    </div>
</div>
