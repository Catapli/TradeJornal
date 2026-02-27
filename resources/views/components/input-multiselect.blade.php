@props(['id', 'options' => [], 'placeholder' => 'Seleccionar...', 'icono' => null])

<div class="relative col-span-12"
     x-data="{
         open: false,
         selected: @entangle($attributes->wire('model')),
     
         init() {
             // 👇 ESTO ES LO NUEVO:
             // Vigilamos cuando el menú se cierra
             this.$watch('open', value => {
                 if (value === false) {
                     // Cuando se cierra, forzamos la actualización al padre
                     this.$wire.$refresh();
                 }
             });
         },
     
         // Texto dinámico
         get label() {
             if (this.selected.includes('all')) return '{{ __('labels.all_accounts') }}';
             if (this.selected.length === 0) return '{{ $placeholder }}';
             if (this.selected.length === 1) return this.selected.length + ' {{ __('labels.account_selected') }}';
             return this.selected.length + ' {{ __('labels.accounts_selected') }}';
         },
     
     
         // Lógica de Selección Exclusiva
         toggle(value) {
             // CASO 1: Se pulsó 'TODAS'
             if (value === 'all') {
                 if (this.selected.includes('all')) {
                     this.selected = []; // Si ya estaba, se quita (todo vacío)
                 } else {
                     this.selected = ['all']; // Si no estaba, se marca y BORRA el resto
                 }
                 return;
             }
     
             // CASO 2: Se pulsó una cuenta específica
             // Si 'all' estaba seleccionado, lo quitamos primero
             if (this.selected.includes('all')) {
                 this.selected = [];
             }
     
             // Añadir o quitar el ID específico
             if (this.selected.includes(value)) {
                 this.selected = this.selected.filter(item => item !== value);
             } else {
                 this.selected.push(value);
             }
         },
     
         // Helper para saber si el checkbox debe estar pintado
         isChecked(value) {
             return this.selected.includes(value);
         }
     }"
     @click.away="open = false">

    {{-- BOTÓN TRIGGER --}}
    <div class="flex w-full cursor-pointer rounded-lg shadow-sm"
         @click="open = !open">

        <div class="inline-flex min-w-[55px] items-center justify-center rounded-s-md border border-e-0 border-gray-200 bg-gray-50 px-4 dark:border-neutral-600 dark:bg-neutral-700">
            <span class="text-lg text-gray-500 dark:text-neutral-400">
                {!! $icono ?? '<i class="fa-solid fa-list-check"></i>' !!}
            </span>
        </div>

        <div class="relative flex w-full min-w-52 items-center rounded-e-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 shadow-inner dark:border-neutral-700 dark:bg-neutral-900 dark:text-gray-300">
            <span x-text="label"
                  :class="selected.length === 0 ? 'text-gray-400 pr-5' : 'text-gray-800 font-semibold pr-5'"></span>
            <div class="absolute right-4 top-1/2 -translate-y-1/2 transition-transform duration-200"
                 :class="open ? 'rotate-180' : ''">
                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
            </div>
        </div>
    </div>

    {{-- LISTA DESPLEGABLE --}}
    <div class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 shadow-2xl dark:border-gray-700 dark:bg-gray-800"
         x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         style="display: none;">

        <div class="flex flex-col p-1">

            {{-- OPCIÓN: TODAS --}}
            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-b border-gray-100 px-3 py-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700">
                <input class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                       type="checkbox"
                       value="all"
                       :checked="isChecked('all')"
                       @change="toggle('all')">
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-gray-800 dark:text-white">
                        {{ __('labels.all_m') }}
                    </span>
                </div>
            </label>

            {{-- OPCIONES INDIVIDUALES --}}
            @foreach ($options as $option)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <input class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                           type="checkbox"
                           value="{{ $option['id'] }}"
                           :checked="isChecked({{ $option['id'] }})"
                           @change="toggle({{ $option['id'] }})">

                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ $option['name'] }}
                        </span>
                        @if (isset($option['subtext']))
                            <span class="text-xs text-gray-400">{{ $option['subtext'] }}</span>
                        @endif
                    </div>
                </label>
            @endforeach

            @if (count($options) === 0)
                <div class="px-4 py-3 text-center text-sm text-gray-500">
                    {{ __('labels.not_accounts_available') }}
                </div>
            @endif

            {{-- BOTÓN APLICAR FIJO AL FINAL --}}
            <div class="sticky bottom-0 border-t border-gray-100 bg-white p-2 dark:border-gray-700 dark:bg-gray-800">
                <button class="w-full rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300"
                        type="button"
                        @click="open = false; $wire.$refresh()">
                    {{ __('labels.apply_filters') }}
                </button>
            </div>
        </div>
    </div>
</div>
