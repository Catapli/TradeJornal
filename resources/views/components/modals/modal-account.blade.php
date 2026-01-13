@props(['show', 'labelTitle', 'event' => 'checkForm'])

<div class="fixed inset-0 z-50 flex justify-center overflow-auto rounded-br-xl rounded-tl-xl rounded-tr-xl bg-opacity-100 backdrop-blur-md transition-opacity duration-300 ease-out"
     data-modal-backdrop="static"
     x-show="{{ $show }}"
     {{-- 👇 AQUÍ ESTÁ LA MAGIA: Vigila la variable y bloquea/desbloquea el scroll del body --}}
     x-init="$watch('{{ $show }}', value => {
         if (value) {
             document.body.classList.add('overflow-hidden');
         } else {
             document.body.classList.remove('overflow-hidden');
         }
     })"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-90"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-90"
     @keydown.window.escape="{{ $show }} = false"
     @keydown.window.enter="{{ $show }} = false"
     x-cloak
     tabindex="-1">

    <div class="relative mt-9 w-full max-w-7xl overflow-y-visible p-8">

        <!-- Modal content -->
        <div class="relative rounded-lg border border-solid border-secondary bg-secondary shadow-2xl dark:bg-gray-700">

            {{-- ? Loading --}}
            <div wire:loading
                 wire:target='getRegisterTable'>
                <x-loader-modal></x-loader-modal>
            </div>

            <!-- Modal header -->
            <div class="flex min-h-14 items-center justify-between rounded-t-lg border-secondary bg-secondary px-4 py-2">
                <div class="flex w-auto gap-x-3 rounded-t-md">
                </div>
                <div class="content-end text-2xl font-semibold text-white"
                     x-text="{{ $labelTitle }}">

                </div>

                <div>
                    <i class="fa-solid fa-xmark cursor-pointer text-3xl text-white transition-all duration-200 ease-in-out hover:scale-125"
                       @click="{{ $show }} = false"></i>
                </div>
            </div>

            <!-- Modal body -->
            <div class="min-h-15 flex h-auto min-h-16 w-full bg-white">
                {{-- Contenido del body --}}
                {{ $slot }}
            </div>

            {{-- Modal Footer --}}
            <div class="flex h-auto w-full justify-end rounded-b-lg border-t border-gray-200 bg-white px-4 py-2">
                <i class="fa-solid fa-circle-check cursor-pointer text-3xl transition-all duration-200 ease-in-out hover:scale-125 hover:text-green-500"
                   @click="$dispatch('{{ $event }}')"></i>
            </div>
        </div>
    </div>
</div>
