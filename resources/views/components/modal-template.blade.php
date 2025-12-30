@props(['show'])
<div class="absolute inset-0 z-50 flex justify-center rounded-br-xl rounded-tl-xl rounded-tr-xl bg-opacity-100 backdrop-blur-md transition-opacity duration-300 ease-out"
     data-modal-backdrop="static"
     x-show="{{ $show }}"
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
    <div class="relative w-full max-w-2xl p-8">
        <!-- Modal content -->
        <div class="relative rounded-lg border border-solid bg-white shadow-2xl dark:bg-gray-700"
             @click.away="{{ $show }} = false"
             :class="{ 'border-green-600': typeAlert === 'success', 'border-red-600': typeAlert === 'error', 'border-yellow-600': typeAlert === 'warn' }">
            <!-- Modal header -->
            <div class="flex min-h-14 items-center justify-between rounded-t-lg px-4 py-2"
                 :class="{ 'bg-green-600': typeAlert === 'success', 'bg-red-600': typeAlert === 'error', 'bg-yellow-600': typeAlert === 'warn' }">
                <div class="flex w-auto gap-x-3 rounded-t-md">
                    <img class="h-14"
                         src="{{ asset('img/detrafic_logo.png') }}"
                         alt="Icono" />

                </div>
                <div class="content-end text-2xl font-semibold text-white"
                     x-text="alertTitle"></div>

                <div>
                    <i class="fa-solid fa-xmark cursor-pointer text-3xl text-white transition-all duration-200 ease-in-out hover:scale-125"
                       @click="{{ $show }} = false"></i>
                </div>
            </div>
            <!-- Modal body -->
            <div class="min-h-15 flex h-auto w-full">
                <div class="flex w-full">
                    <span class="h-fit p-3 font-poppins text-lg"
                          x-text="bodyAlert"></span>
                </div>
            </div>
            {{-- Modal Footer --}}
            <div class="flex h-auto w-full justify-end rounded-b-lg border-t border-gray-200 px-4 py-2">
                <i class="fa-solid fa-circle-check cursor-pointer text-3xl transition-all duration-200 ease-in-out hover:scale-125 hover:text-green-500"
                   @click="(e) => {
                        {{ $show }} = false;
                        if(typeButton != '') $wire.call(typeButton);
                     }"></i>
            </div>
        </div>
    </div>
</div>
