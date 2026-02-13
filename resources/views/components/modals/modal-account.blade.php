@props(['show', 'labelTitle', 'event' => 'checkForm'])

<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm transition-all duration-300 ease-out"
     x-show="{{ $show }}"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click.self="{{ $show }} = false"
     @keydown.escape.window="{{ $show }} = false"
     x-init="$watch('{{ $show }}', value => {
         if (value) {
             document.body.classList.add('overflow-hidden');
         } else {
             document.body.classList.remove('overflow-hidden');
         }
     })"
     x-cloak
     style="display: none;">

    <div class="relative mx-4 w-full max-w-3xl"
         x-transition:enter="transition ease-out duration-300 delay-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Modal Container -->
        <div class="relative overflow-hidden rounded-2xl bg-white shadow-2xl">

            {{-- Loading Overlay --}}
            <div class="absolute inset-0 z-50 flex items-center justify-center bg-white/90 backdrop-blur-sm"
                 wire:loading
                 wire:target='insertAccount, updateAccount'>
                <div class="flex flex-col items-center gap-3">
                    <x-loader></x-loader>
                    <span class="text-sm font-medium text-gray-600">{{ __('labels.saving') }}...</span>
                </div>
            </div>

            <!-- Header -->
            <div class="relative overflow-hidden border-b border-gray-100 bg-gradient-to-r from-gray-800 to-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 backdrop-blur-sm">
                            <i class="fa-solid fa-wallet text-lg text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white"
                                x-text="{{ $labelTitle }}"></h3>
                            <p class="text-xs text-gray-300">{{ __('labels.account_configuration') }}</p>
                        </div>
                    </div>
                    <button class="rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white"
                            type="button"
                            @click="{{ $show }} = false">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="max-h-[70vh] overflow-y-auto p-6">
                {{ $slot }}
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-6 py-4">
                <button class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                        type="button"
                        @click="{{ $show }} = false">
                    <i class="fa-solid fa-xmark text-gray-400"></i>
                    {{ __('labels.cancel') }}
                </button>

                <button class="flex items-center gap-2 rounded-lg bg-gray-800 px-6 py-2.5 text-sm font-bold text-white shadow-lg transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                        type="button"
                        @click="$dispatch('{{ $event }}')">
                    <i class="fa-solid fa-check"></i>
                    {{ __('labels.save_account') }}
                </button>
            </div>
        </div>
    </div>
</div>
