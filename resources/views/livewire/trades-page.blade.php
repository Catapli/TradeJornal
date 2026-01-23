<div class="min-h-screen bg-gray-50/50 p-6">

    {{-- CONTENEDOR PRINCIPAL CON ESTADO ALPINE --}}
    <div x-data="{
        initialLoad: true,
        init() {
            // Cuando Livewire termine de cargar sus scripts y efectos, quitamos el loader
            document.addEventListener('livewire:initialized', () => {
                this.initialLoad = false;
            });
    
            // Fallback de seguridad: por si Livewire ya cargó antes de este script
            setTimeout(() => { this.initialLoad = false }, 800);
        }
    }">

        {{-- 1. LOADER DE CARGA INICIAL (Pantalla completa al refrescar) --}}
        {{-- Se muestra mientras 'initialLoad' sea true. Tiene z-index máximo (z-50) --}}
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white"
             x-show="initialLoad"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Aquí tu componente loader --}}
            <div class="flex flex-col items-center">
                <x-loader />
                <span class="mt-4 animate-pulse text-sm font-bold text-gray-400">Cargando Dashboard...</span>
            </div>
        </div>
    </div>



    {{-- ? Loading --}}
    <div wire:loading
         wire:target='resetFilters'>
        <x-loader></x-loader>
    </div>

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Logbook</h1>
            <p class="text-sm text-gray-500">Gestión de operaciones y auditoría</p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Buscador --}}
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input class="rounded-lg border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Buscar ticket...">
            </div>

            {{-- Toggle Filtros --}}
            <button class="{{ $showFilters ? 'ring-2 ring-indigo-500 border-indigo-500 text-indigo-700 bg-indigo-50' : '' }} flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"
                    wire:click="$toggle('showFilters')">
                <i class="fa-solid fa-filter"></i> Filtros
            </button>
        </div>
    </div>

    {{-- PANEL DE FILTROS --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
         x-show="$wire.showFilters"
         x-transition>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">

            {{-- Cuenta --}}
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Cuenta</label>
                <select class="w-full rounded-md border-gray-300 text-sm"
                        wire:model.live="filters.account_id">
                    <option value="">Todas</option>
                    @foreach ($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Estrategia --}}
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Estrategia</label>
                <select class="w-full rounded-md border-gray-300 text-sm"
                        wire:model.live="filters.strategy_id">
                    <option value="">Todas</option>
                    @foreach ($strategiesList as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Errores (Mistakes) --}}
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Errores Detectados</label>
                <select class="w-full rounded-md border-rose-200 text-sm font-medium text-rose-700 focus:border-rose-500 focus:ring-rose-500"
                        wire:model.live="filters.mistake_id">
                    <option value="">-- Cualquiera --</option>
                    @foreach ($mistakesList as $m)
                        <option value="{{ $m->id }}">⚠️ {{ $m->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Resultado --}}
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Resultado</label>
                <select class="w-full rounded-md border-gray-300 text-sm"
                        wire:model.live="filters.result">
                    <option value="">Todos</option>
                    <option value="win">Ganadoras</option>
                    <option value="loss">Perdedoras</option>
                </select>
            </div>

            {{-- Botón Limpiar Seguro --}}
            <div class="flex items-end">
                <button class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100"
                        wire:click="resetFilters">
                    Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="w-10 px-4 py-3 text-center">
                            <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                   wire:model.live="selectAll"
                                   type="checkbox">
                        </th>
                        <th class="px-4 py-3 text-left">Ticket / Activo</th>
                        <th class="px-4 py-3 text-center">Dirección</th>
                        <th class="px-4 py-3 text-center">Precios</th>
                        <th class="px-4 py-3 text-center">Estrategia / Errores</th>
                        <th class="px-4 py-3 text-right">P&L ($)</th>
                        <th class="px-4 py-3 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white text-sm">
                    @forelse ($this->trades as $trade)
                        <tr class="{{ in_array($trade->id, $selectedTrades) ? 'bg-indigo-50' : '' }} group transition hover:bg-indigo-50/30">

                            {{-- Checkbox --}}
                            <td class="px-4 py-3 text-center">
                                <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                       wire:model.live="selectedTrades"
                                       value="{{ $trade->id }}"
                                       type="checkbox">
                            </td>

                            {{-- Ticket + Activo --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900">{{ $trade->tradeAsset->name ?? $trade->symbol }}</span>
                                        <span class="text-[10px] uppercase text-gray-400">#{{ $trade->ticket }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Dirección --}}
                            <td class="px-4 py-3 text-center">
                                @if (in_array(strtoupper($trade->direction), ['BUY', 'LONG']))
                                    <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">LONG</span>
                                @else
                                    <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">SHORT</span>
                                @endif
                                <div class="mt-1 text-[10px] text-gray-400">{{ $trade->size }} Lot</div>
                            </td>

                            {{-- Precios --}}
                            <td class="px-4 py-3 text-center text-xs">
                                <div><span class="text-gray-400">In:</span> {{ number_format($trade->entry_price, 5) }}</div>
                                <div><span class="text-gray-400">Out:</span> {{ number_format($trade->exit_price, 5) }}</div>
                            </td>

                            {{-- ESTRATEGIA Y ERRORES (MISTAKES) --}}
                            <td class="px-4 py-3">
                                <div class="flex flex-col items-center gap-1.5">

                                    {{-- Estrategia (Azul) --}}
                                    @if ($trade->strategy)
                                        <span class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                                            {{ $trade->strategy->name }}
                                        </span>
                                    @else
                                        <span class="text-[10px] italic text-gray-300">- Sin Plan -</span>
                                    @endif

                                    {{-- Mistakes (Rojo/Amarillo) --}}
                                    @if ($trade->mistakes->count() > 0)
                                        <div class="flex flex-wrap justify-center gap-1">
                                            @foreach ($trade->mistakes as $mistake)
                                                <span class="rounded border border-rose-200 bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700"
                                                      title="{{ $mistake->name }}">
                                                    ⚠️ {{ Str::limit($mistake->name, 12) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- PnL --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-mono font-black">
                                    {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }} $
                                </div>
                                <div class="text-[10px] text-gray-400">
                                    {{ \Carbon\Carbon::parse($trade->exit_time)->format('d M H:i') }}
                                </div>
                            </td>

                            {{-- Botón Detalles --}}
                            <td class="px-4 py-3 text-center">
                                <button class="text-gray-400 transition hover:text-indigo-600"
                                        wire:click="$dispatch('open-trade-detail', { tradeId: {{ $trade->id }} })">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-12 text-center text-gray-500"
                                colspan="8">No hay operaciones con estos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer Paginación --}}
        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4">
            {{ $this->trades->links('vendor.livewire.tradeforge-pagination', data: ['scrollTo' => false]) }}
        </div>
    </div>

    {{-- BARRA FLOTANTE DE ACCIONES --}}
    <div class="fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 items-center gap-4 rounded-full bg-gray-900 px-6 py-3 text-white shadow-2xl transition-transform duration-300"
         x-show="$wire.selectedTrades.length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         style="display: none;">

        <span class="text-sm font-bold"><span x-text="$wire.selectedTrades.length"></span> seleccionados</span>
        <div class="h-4 w-px bg-gray-700"></div>

        <button class="flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold transition hover:bg-gray-800"
                wire:click="$set('showBulkModal', true)">
            <i class="fa-solid fa-pen"></i> Asignar Plan / Error
        </button>

        <button class="ml-2 rounded-full p-1 hover:bg-gray-700"
                wire:click="$set('selectedTrades', [])"><i class="fa-solid fa-times"></i></button>
    </div>

    {{-- MODAL BULK EDIT --}}
    @if ($showBulkModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-bold text-gray-900">Edición Masiva</h3>

                {{-- ESTRATEGIA --}}
                <div class="mb-4">
                    <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Estrategia (El Plan)</label>
                    <select class="w-full rounded-md border-gray-300 text-sm"
                            wire:model="bulkStrategyId">
                        <option value="">-- No cambiar --</option>
                        @foreach ($strategiesList as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ERRORES (MISTAKES) --}}
                <div class="mb-6">
                    <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Registrar Errores (Psicología)</label>
                    <div class="h-32 overflow-y-auto rounded-md border border-gray-300 p-2">
                        @foreach ($mistakesList as $mistake)
                            <label class="flex cursor-pointer items-center gap-2 rounded p-1 hover:bg-gray-50">
                                <input class="rounded text-rose-600 focus:ring-rose-500"
                                       type="checkbox"
                                       wire:model="bulkMistakes"
                                       value="{{ $mistake->id }}">
                                <span class="text-sm text-gray-700">{{ $mistake->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-[10px] text-gray-400">* Se añadirán a los errores que ya tengan las operaciones.</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700"
                            wire:click="$set('showBulkModal', false)">Cancelar</button>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700"
                            wire:click="executeBulkUpdate">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
