{{-- resources/views/components/confirm-modal.blade.php --}}
<div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
     x-data="{
         show: false,
         title: '',
         text: '',
         type: 'indigo', // indigo, red, warning
         action: null,
         params: null,
     
         // Método para abrir el modal (llamado desde eventos globales)
         open(detail) {
             this.show = true;
             this.title = detail.title || 'Confirmar acción';
             this.text = detail.text || '¿Estás seguro de continuar?';
             this.type = detail.type || 'indigo';
             this.action = detail.action; // Nombre del método Livewire
             this.params = detail.params;
         },
     
         // Ejecutar la acción confirmada
         confirm() {
             if (this.action) {
                 // Llamada mágica a Livewire desde cualquier componente padre
                 this.$wire[this.action](this.params);
             }
             this.show = false;
         }
     }"
     x-on:open-confirm-modal.window="open($event.detail)"
     x-show="show"
     x-transition.opacity
     x-cloak
     style="display: none;">

    <div class="w-full max-w-md scale-100 transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all"
         @click.away="show = false">

        <div class="p-8 text-center">
            {{-- Icono Dinámico --}}
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full transition-colors"
                 :class="{
                     'bg-indigo-50 text-indigo-600': type === 'indigo',
                     'bg-red-50 text-red-600': type === 'red',
                     'bg-amber-50 text-amber-600': type === 'warning'
                 }">
                <i class="fa-solid text-3xl"
                   :class="{
                       'fa-circle-question': type === 'indigo',
                       'fa-triangle-exclamation': type === 'red' || type === 'warning'
                   }"></i>
            </div>

            <h3 class="mb-3 text-2xl font-black text-gray-900"
                x-text="title"></h3>
            <p class="text-gray-500"
               x-text="text"></p>
        </div>

        {{-- Footer Botones --}}
        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-6">
            <button class="flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white py-3 font-bold text-gray-700 transition hover:bg-gray-100 hover:text-gray-900"
                    @click="show = false">
                Cancelar
            </button>

            <button class="flex w-full items-center justify-center rounded-xl py-3 font-bold text-white shadow-lg transition hover:shadow-xl focus:ring-4"
                    @click="confirm()"
                    :class="{
                        'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-200': type === 'indigo',
                        'bg-red-600 hover:bg-red-700 focus:ring-red-200': type === 'red',
                        'bg-amber-500 hover:bg-amber-600 focus:ring-amber-200': type === 'warning'
                    }">
                Confirmar
            </button>
        </div>
    </div>
</div>
