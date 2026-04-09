<div class="space-y-6 p-6"
     x-data="backtestingPage">

    {{-- Modal Confirmar Eliminar Trade --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-show="showDeleteConfirm"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
             @click="cancelDelete()"></div>

        {{-- Panel --}}
        <div class="relative z-10 w-full max-w-sm rounded-xl bg-white p-6 shadow-xl"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            {{-- Icono --}}
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>

            <h3 class="mb-1 text-center text-base font-semibold text-gray-900">
                Eliminar trade
            </h3>
            <p class="mb-6 text-center text-sm text-gray-500">
                Esta acción no se puede deshacer. Se eliminará el trade y su screenshot asociado.
            </p>

            <div class="flex gap-3">
                <button class="flex-1 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        type="button"
                        @click="cancelDelete()">
                    Cancelar
                </button>
                <button class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                        type="button"
                        @click="executeDelete()"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove
                          wire:target="deleteTrade">Eliminar</span>
                    <span wire:loading
                          wire:target="deleteTrade">Eliminando...</span>
                </button>
            </div>
        </div>
    </div>


    {{-- TOGGLE ARCHIVADAS --}}
    <div class="mt-8 border-t border-gray-100 pt-6">
        <button class="flex items-center gap-2 text-sm text-gray-400 transition-colors hover:text-gray-600"
                wire:click="toggleArchived">
            <svg class="h-4 w-4"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            {{ $showArchived ? 'Ocultar archivadas' : 'Ver estrategias archivadas' }}
            @if (!$showArchived)
                <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500">
                    {{ \App\Models\BacktestStrategy::where('user_id', auth()->id())->where('status', 'archived')->count() }}
                </span>
            @endif
        </button>

        @if ($showArchived)
            @if ($archivedStrategies->isEmpty())
                <p class="mt-4 text-sm text-gray-400">No hay estrategias archivadas.</p>
            @else
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($archivedStrategies as $strategy)
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-600">{{ $strategy->name }}</p>
                                <p class="text-xs text-gray-400">{{ $strategy->symbol }} · {{ $strategy->timeframe }} · {{ $strategy->trades_count }} trades</p>
                            </div>
                            <button class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:border-blue-300 hover:text-blue-600"
                                    type="button"
                                    wire:click="unarchive({{ $strategy->id }})">
                                <svg class="h-3.5 w-3.5"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     stroke-width="2">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                                Reactivar
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    {{-- MODAL CONFIRMAR ARCHIVO --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-show="showArchiveConfirm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="cancelArchive()"
         style="display:none">
        <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-6 shadow-xl"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-amber-50">
                <svg class="h-5 w-5 text-amber-500"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
            <h3 class="mb-1 text-sm font-bold text-gray-900">Archivar estrategia</h3>
            <p class="mb-5 text-sm text-gray-500">La estrategia se ocultará del listado principal. Podrás reactivarla en cualquier momento desde "Ver estrategias archivadas".</p>
            <div class="flex justify-end gap-3">
                <button class="rounded-lg px-4 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-gray-700"
                        type="button"
                        @click="cancelArchive()">
                    Cancelar
                </button>
                <button class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-amber-600"
                        type="button"
                        @click="executeArchive()">
                    Archivar
                </button>
            </div>
        </div>
    </div>

    {{-- LISTADO --}}
    <div x-show="!$wire.selectedStrategyId">
        @include('livewire.backtesting._list')
    </div>

    {{-- DETALLE --}}
    <div x-show="$wire.selectedStrategyId"
         style="display:none">
        @include('livewire.backtesting._detail')
    </div>

    {{-- MODAL CREAR/EDITAR ESTRATEGIA --}}
    @include('livewire.backtesting._strategy-modal')

    {{-- PANEL TRADE --}}
    @include('livewire.backtesting._trade-panel')

    @include('livewire.backtesting._trade-detail-modal')

</div>
