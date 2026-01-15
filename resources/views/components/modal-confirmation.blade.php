@props(['show', 'title' => '¿Estás seguro?', 'text' => 'Esta acción no se puede deshacer.', 'confirmText' => 'Sí, eliminar', 'cancelText' => 'Cancelar'])

<div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity"
     {{ $attributes }}
     x-show="{{ $show }}"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     x-cloak>

    <div class="relative w-full max-w-md p-4"
         @click.away="{{ $show }} = false">

        <div class="relative rounded-2xl bg-white shadow-2xl dark:bg-gray-800">

            {{-- Icono de Advertencia --}}
            <div class="flex items-center justify-center pt-6">
                <div class="rounded-full bg-red-100 p-4 dark:bg-red-900">
                    <i class="fa-solid fa-triangle-exclamation text-3xl text-red-600 dark:text-red-400"></i>
                </div>
            </div>

            {{-- Texto --}}
            <div class="p-6 text-center">
                <h3 class="mb-2 text-xl font-bold text-gray-800 dark:text-white">{{ $title }}</h3>
                <p class="text-gray-500 dark:text-gray-300">{{ $text }}</p>
            </div>

            {{-- Botones --}}
            <div class="flex items-center justify-center gap-4 rounded-b-2xl bg-gray-50 p-4 dark:bg-gray-700">
                <button class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                        type="button"
                        @click="{{ $show }} = false">
                    {{ $cancelText }}
                </button>

                <button class="rounded-lg bg-red-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                        type="button"
                        @click="$dispatch('confirm-action')">
                    {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>
