<div class="min-h-screen bg-gray-50/50 p-6 dark:bg-gray-900"
     x-data="trades()">

    {{-- LOADER INICIAL --}}
    <div x-data="{
        initialLoad: true,
        init() {
            document.addEventListener('livewire:initialized', () => { this.initialLoad = false; });
            setTimeout(() => { this.initialLoad = false }, 500);
        }
    }">
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white dark:bg-gray-900"
             x-show="initialLoad"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex flex-col items-center">
                <x-loader />
                <span class="mt-4 animate-pulse text-sm font-bold text-gray-400 dark:text-gray-500">{{ __('labels.loading') }}</span>
            </div>
        </div>
    </div>

    {{-- MODAL CONFIRMACIÓN ELIMINAR --}}
    <x-modal-confirmation show="showDeleteModal"
                          title="{{ __('labels.¿delete_trade?') }}"
                          text="{{ __('labels.lost_data_trade') }}"
                          confirmText="{{ __('labels.confirm_delete') }}"
                          @confirm-action="executeDelete()" />

    {{-- MODAL CONFIRMACIÓN BORRADO MASIVO --}}
    <x-modal-confirmation show="showBulkDeleteModal"
                          title="{{ __('labels.¿delete_selection?') }}"
                          text="{{ __('labels.lost_multiple_trades') }}"
                          confirmText="{{ __('labels.confirm_delete') }}"
                          @confirm-action="executeBulkDelete()" />

    {{-- LOADER PARA ACCIONES PESADAS --}}
    <div wire:loading
         wire:target='delete, save, executeBulkUpdate, executeBulkDelete'>
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/30 backdrop-blur-[1px] dark:bg-gray-900/30">
            <x-loader></x-loader>
        </div>
    </div>

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-chart-simple text-2xl text-indigo-600 dark:text-indigo-400"></i>
                <h1 class="text-3xl font-black text-gray-900 dark:text-gray-100">{{ __('menu.trades') }}</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('menu.resume_trades') }}</p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Importar salió del carril lateral: su sitio es aquí, junto a las
                 operaciones que va a rellenar. El atajo «I» sigue funcionando. --}}
            <x-page-link :href="route('trades.import')"
                         icon="fa-solid fa-file-import"
                         :label="__('menu.go_import')" />

            {{-- BOTÓN NUEVA OPERACIÓN --}}
            {{-- data-shortcut-new: lo pulsa la tecla N (resources/js/core/shortcuts.js). --}}
            <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-indigo-700 hover:shadow-lg"
                    data-shortcut-new
                    title="{{ __('labels.shortcut_new') }}"
                    @click="openFormCreate">
                <i class="fa-solid fa-plus"></i> {{ __('labels.new') }}
            </button>

            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"></i>
                <input class="rounded-lg border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                       data-shortcut-search
                       wire:model.live.debounce.400ms="search"
                       type="text"
                       title="{{ __('labels.shortcut_search') }}"
                       placeholder="{{ __('labels.placeholder_search_ticket') }}">
            </div>

            <button class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                    :class="{ 'ring-2 ring-indigo-500 border-indigo-500 text-indigo-700 bg-indigo-50 dark:bg-indigo-500/10 dark:text-indigo-400': showFilters, 'bg-white dark:bg-gray-800': !showFilters }"
                    @click="toggleFilters">
                <i class="fa-solid fa-filter"></i> {{ __('labels.filters') }}
            </button>

            {{-- Exportar lo que hay en pantalla. El wire:key evita que el morphing
                 de Livewire reutilice este botón al repintar la barra. --}}
            <button class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    wire:key="export-csv"
                    wire:click="exportCsv"
                    wire:loading.attr="disabled"
                    wire:target="exportCsv"
                    title="{{ __('export.csv.hint') }}">
                <i class="fa-solid fa-file-csv" wire:loading.remove wire:target="exportCsv"></i>
                <i class="fa-solid fa-spinner fa-spin" wire:loading wire:target="exportCsv"></i>
                {{ __('export.csv.button') }}
            </button>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
         x-show="showFilters"
         x-transition
         style="display: none;">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.account') }}</label>
                <select class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        wire:model.live="filters.account_id">
                    <option value="">{{ __('labels.all') }}</option>
                    @foreach ($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            @if (Auth::user()->hasProAccess())
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.strategy') }}</label>
                    <select class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            wire:model.live="filters.strategy_id">
                        <option value="">{{ __('labels.all') }}</option>
                        @foreach ($strategiesList as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.detect_errors') }}</label>
                <select class="w-full rounded-md border-rose-200 text-sm font-medium text-rose-700 focus:border-rose-500 focus:ring-rose-500 dark:border-rose-500/40 dark:bg-gray-700 dark:text-rose-400"
                        wire:model.live="filters.mistake_id">
                    <option value="">{{ __('labels.anyone') }}</option>
                    @foreach ($mistakesList as $m)
                        <option value="{{ $m->id }}">⚠️ {{ $m->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.result') }}</label>
                <select class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        wire:model.live="filters.result">
                    <option value="">{{ __('labels.everyone') }}</option>
                    <option value="win">{{ __('labels.winners') }}</option>
                    <option value="loss">{{ __('labels.lossers') }}</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        wire:click="resetFilters">
                    {{ __('labels.clean_filters') }}
                </button>
            </div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                    <tr>
                        <th class="w-10 px-4 py-3 text-center">
                            <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                   wire:model.live="selectAll"
                                   type="checkbox">
                        </th>
                        <th class="px-4 py-3 text-left">{{ __('labels.ticket_active') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('labels.direction') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('labels.prices') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('labels.efficiency') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('labels.strategy_errors') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('labels.p&l') }}</th>
                        <th class="px-4 py-3 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white text-sm dark:divide-gray-700 dark:bg-gray-800">
                    @forelse ($this->trades as $trade)
                        <tr class="{{ in_array($trade->id, $selectedTrades) ? 'bg-indigo-50 dark:bg-indigo-500/10' : '' }} group transition hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5"
                            wire:key="row-{{ $trade->id }}">

                            <td class="px-4 py-3 text-center">
                                <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                       wire:model.live="selectedTrades"
                                       value="{{ $trade->id }}"
                                       type="checkbox">
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ $trade->tradeAsset->name ?? $trade->symbol }}</span>
                                        <span class="text-[10px] uppercase text-gray-400 dark:text-gray-500">#{{ $trade->ticket ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if (in_array(strtoupper($trade->direction), ['BUY', 'LONG']))
                                    <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('labels.long') }}</span>
                                @else
                                    <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">{{ __('labels.short') }}</span>
                                @endif
                                <div class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">{{ $trade->size }} {{ __('labels.lots') }}</div>
                            </td>

                            <td class="px-4 py-3 text-center text-xs text-gray-700 dark:text-gray-200">
                                <div><span class="text-gray-400 dark:text-gray-500">{{ __('labels.in') }}</span> {{ number_format($trade->entry_price, 5) }}</div>
                                <div><span class="text-gray-400 dark:text-gray-500">{{ __('labels.out') }}</span> {{ number_format($trade->exit_price, 5) }}</div>
                            </td>

                            <td class="px-4 py-3 align-middle">
                                @if ($trade->mae_price && $trade->mfe_price)
                                    @php
                                        // 1. Distancias Absolutas
                                        $distMae = abs($trade->entry_price - $trade->mae_price);
                                        $distMfe = abs($trade->entry_price - $trade->mfe_price);
                                        $distReal = abs($trade->entry_price - $trade->exit_price);

                                        // 2. Rango Visual
                                        $totalRange = $distMae + $distMfe;
                                        $totalRange = $totalRange > 0 ? $totalRange : 0.00001;

                                        $pctRed = ($distMae / $totalRange) * 100;
                                        $pctGreen = ($distMfe / $totalRange) * 100;

                                        // 3. Posición Marcador
                                        $isBetterThanEntry = $trade->direction == 'long' ? $trade->exit_price >= $trade->entry_price : $trade->exit_price <= $trade->entry_price;

                                        if ($isBetterThanEntry) {
                                            $markerPos = $pctRed + ($distReal / $totalRange) * 100;
                                        } else {
                                            $markerPos = $pctRed - ($distReal / $totalRange) * 100;
                                        }
                                        $markerPos = max(0, min(100, $markerPos));

                                        // 4. CÁLCULO MONETARIO INTELIGENTE
                                        $maeMoney = 0;
                                        $mfeMoney = 0;

                                        // Umbral de fiabilidad: 2 pips (0.0002) aprox.
                                        // Si el precio se movió MENOS que esto, el PnL es mayormente comisiones/swap
                                        // y no sirve para calcular el valor del punto matemáticamente.
                                        if ($distReal > 0.0002) {
                                            // Cálculo exacto basado en lo que pasó
                                            $valuePerPoint = abs($trade->pnl) / $distReal;
                                        } else {
                                            // FALLBACK: Estimación basada en Lotes (Size)
                                            // Asumimos estándar Forex (100k unidades).
                                            // Si operas Índices/Crypto esto será una aproximación, pero mucho mejor que 0 o Infinito.
                                            $valuePerPoint = $trade->size * 100000;
                                        }

                                        // Aplicamos el valor del punto a las distancias MAE/MFE
                                        $maeMoney = $distMae * $valuePerPoint;
                                        $mfeMoney = $distMfe * $valuePerPoint;
                                    @endphp
                                    <div class="relative mx-auto flex h-4 w-32 select-none items-center">
                                        <div class="pointer-events-none absolute inset-x-0 flex h-1.5 overflow-hidden rounded-full">
                                            <div class="h-full bg-rose-400"
                                                 style="width: {{ $pctRed }}%"></div>
                                            <div class="z-10 h-full w-[2px] bg-white opacity-50"></div>
                                            <div class="h-full bg-emerald-400"
                                                 style="width: {{ $pctGreen }}%"></div>
                                        </div>
                                        <div class="absolute inset-0 flex h-full w-full items-center">
                                            <div class="group/red relative h-4 cursor-help"
                                                 style="width: {{ $pctRed }}%">
                                                <div class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-rose-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/red:block">
                                                    {{ __('labels.max_risk') }} {{ number_format($maeMoney, 2) }} €
                                                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-rose-900"></div>
                                                </div>
                                            </div>
                                            <div class="group/green relative h-4 cursor-help"
                                                 style="width: {{ $pctGreen }}%">
                                                <div class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-emerald-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/green:block">
                                                    {{ __('labels.max_potencial') }} +{{ number_format($mfeMoney, 2) }} €
                                                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-emerald-900"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="pointer-events-none absolute z-20 h-full w-1 rounded-full bg-gray-900 shadow-sm dark:bg-gray-100"
                                             style="left: {{ $markerPos }}%; transform: translateX(-50%);"></div>
                                    </div>
                                @else
                                    <span class="block text-center text-xs text-gray-300 dark:text-gray-600">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-col items-center gap-1.5">
                                    @if ($trade->strategy)
                                        <span class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400">
                                            {{ $trade->strategy->name }}
                                        </span>
                                    @else
                                        <span class="text-[10px] italic text-gray-300 dark:text-gray-600">{{ __('labels.without_plan') }}</span>
                                    @endif
                                    @if ($trade->mistakes->count() > 0)
                                        <div class="flex flex-wrap justify-center gap-1">
                                            @foreach ($trade->mistakes as $mistake)
                                                <span class="rounded border border-rose-200 bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
                                                      title="{{ $mistake->display_description ?: $mistake->display_name }}">
                                                    ⚠️ {{ Str::limit($mistake->display_name, 12) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                {{-- Contenedor del PnL --}}
                                <div class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-mono font-black"
                                     {{-- x-data vacío para inicializar el ámbito si no lo hereda --}}
                                     x-data>

                                    {{-- 
            1. x-text: Llama a la función del store global pasándole los dos valores (Dinero y %).
            2. El contenido dentro del <span> es lo que se ve inicialmente (Blade).
            3. El '?? 0' en el porcentaje es por si tienes trades antiguos sin calcular aún, que no rompa el JS.
        --}}
                                    <span x-text="$store.viewMode.format({{ $trade->pnl }}, {{ $trade->pnl_percentage ?? 0 }})">
                                        {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }} $
                                    </span>
                                </div>

                                {{-- Fecha (Se queda igual) --}}
                                <div class="text-[10px] text-gray-400 dark:text-gray-500">
                                    {{ \Carbon\Carbon::parse($trade->exit_time)->format('d M H:i') }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2 opacity-0 transition group-hover:opacity-100">
                                    <button class="text-gray-400 dark:text-gray-500 transition hover:text-indigo-600 disabled:opacity-40 dark:hover:text-indigo-400"
                                            wire:click="openTradeDetail({{ $trade->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="openTradeDetail({{ $trade->id }})">
                                        <i class="fa-solid fa-eye"
                                           wire:loading.remove
                                           wire:target="openTradeDetail({{ $trade->id }})"></i>
                                        <i class="fa-solid fa-spinner fa-spin text-indigo-400"
                                           wire:loading
                                           wire:target="openTradeDetail({{ $trade->id }})"></i>
                                    </button>
                                    {{-- Botón Editar --}}
                                    <button class="relative rounded p-1 text-gray-400 dark:text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                                            wire:click="edit({{ $trade->id }})"
                                            wire:loading.attr="disabled">
                                        <i class="fa-solid fa-pen"
                                           wire:loading.remove
                                           wire:target="edit({{ $trade->id }})"></i>
                                        <i class="fa-solid fa-spinner fa-spin text-indigo-500"
                                           wire:loading
                                           wire:target="edit({{ $trade->id }})"></i>
                                    </button>
                                    <button class="rounded p-1 text-gray-400 dark:text-gray-500 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                            @click="showModalDelete({{ $trade->id }})">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Dos vacíos distintos: sin operaciones todavía (pide
                             importador) o sin resultados con estos filtros (pide
                             quitarlos). Antes ambos decían lo mismo, sin salida. --}}
                        <tr>
                            <td colspan="8">
                                @if ($this->hasAnyTrades)
                                    <x-empty-state icon="fa-filter-circle-xmark"
                                                   :title="__('labels.empty_trades_filtered_title')"
                                                   :text="__('labels.empty_trades_filtered_text')">
                                        <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700"
                                                type="button"
                                                wire:click="resetFilters">{{ __('labels.clean_filters') }}</button>
                                    </x-empty-state>
                                @else
                                    <x-empty-state icon="fa-list-check"
                                                   :title="__('labels.empty_trades_title')"
                                                   :text="__('labels.empty_trades_text')">
                                        <a class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700"
                                           href="{{ route('trades.import') }}">
                                            <i class="fa-solid fa-file-import mr-1"></i>{{ __('import.menu') }}
                                        </a>
                                        <button class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                                type="button"
                                                wire:click="create">{{ __('labels.new_trade') }}</button>
                                    </x-empty-state>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            {{-- Paginación segura --}}
            @if (method_exists($this->trades, 'links'))
                {{ $this->trades->links('vendor.livewire.tradeforge-pagination', data: ['scrollTo' => false]) }}
            @endif
        </div>
    </div>

    {{-- BARRA FLOTANTE ACCIONES --}}
    <div class="fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 items-center gap-4 rounded-full bg-gray-900 px-6 py-3 text-white shadow-2xl transition-transform duration-300"
         x-show="selectedCount > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         style="display: none;">

        <span class="text-sm font-bold"><span x-text="selectedCount"></span> {{ __('labels.selected') }}</span>
        <div class="h-4 w-px bg-gray-700"></div>

        <button class="flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold transition hover:bg-gray-800"
                @click="openBulkModal">
            <i class="fa-solid fa-pen"></i> {{ __('labels.set_plan_error') }}
        </button>

        {{-- NUEVO: Botón Eliminar Masivo --}}
        <button class="flex items-center gap-2 rounded-full bg-rose-600/20 px-3 py-1.5 text-xs font-bold text-rose-400 transition hover:bg-rose-600 hover:text-white"
                @click="showModalBulkDelete()">
            <i class="fa-solid fa-trash"></i> {{ __('labels.delete') }}
        </button>

        <button class="ml-2 rounded-full p-1 hover:bg-gray-700"
                wire:click="$set('selectedTrades', [])"><i class="fa-solid fa-times"></i></button>
    </div>

    {{-- MODAL BULK EDIT --}}
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm"
         x-show="showBulkModal"
         style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800"
             @click.away="closeBulkModal">
            <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('labels.bulk_edit') }}</h3>

            @if (Auth::user()->hasProAccess())
                <div class="mb-4">
                    <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.strategy') }}</label>
                    <select class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            wire:model.blur="bulkStrategyId">
                        <option value="">{{ __('labels.not_change') }}</option>
                        @foreach ($strategiesList as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif


            <div class="mb-6">
                <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.register_errors') }}</label>
                <div class="h-32 overflow-y-auto rounded-md border border-gray-300 p-2 dark:border-gray-600">
                    @foreach ($mistakesList as $mistake)
                        <label class="flex cursor-pointer items-center gap-2 rounded p-1 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input class="rounded text-rose-600 focus:ring-rose-500 dark:border-gray-500 dark:bg-gray-700"
                                   type="checkbox"
                                   wire:model.blur="bulkMistakes"
                                   value="{{ $mistake->id }}">
                            <span class="text-sm text-gray-700 dark:text-gray-300"
                                  title="{{ $mistake->display_description }}">{{ $mistake->display_name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        @click="closeBulkModal">{{ __('labels.cancel') }}</button>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700"
                        wire:click="executeBulkUpdate">
                    <span wire:loading.remove
                          wire:target="executeBulkUpdate">{{ __('labels.save_changes') }}</span>
                    <span wire:loading
                          wire:target="executeBulkUpdate">{{ __('labels.processing') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL CRUD (CREAR / EDITAR) --}}
    <div class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
         x-show="showFormModal"
         x-transition
         style="display: none;">

        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-gray-800"
             @click.away="closeFormModal">

            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    <span x-text="$wire.isEditMode ? '{{ __('labels.edit_operation') }}' : '{{ __('labels.new_operation') }}'"></span>
                </h3>
                <button class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-200"
                        @click="closeFormModal">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.account') }}</label>
                        <select class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                wire:model.blur="form.account_id">
                            <option value="">{{ __('labels.select') }}</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('form.account_id')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.ticket_id') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="text"
                               placeholder="{{ __('labels.optional') }}"
                               wire:model.blur="form.ticket">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.active_symbol') }}</label>
                        <select class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                wire:model.blur="form.trade_asset_id">
                            <option value="">{{ __('labels.select_active') }}</option>
                            @foreach ($assetsList as $asset)
                                <option value="{{ $asset->id }}">{{ $asset->symbol }}</option>
                            @endforeach
                        </select>
                        @error('form.trade_asset_id')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.direction') }}</label>
                        <div class="flex gap-2">
                            <label class="flex-1 cursor-pointer">
                                <input class="peer sr-only"
                                       type="radio"
                                       value="long"
                                       wire:model.blur="form.direction">
                                <div class="rounded-md border border-gray-200 py-2 text-center text-sm font-bold text-gray-500 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 dark:border-gray-600 dark:text-gray-400 dark:peer-checked:bg-emerald-500/10 dark:peer-checked:text-emerald-400">LONG</div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input class="peer sr-only"
                                       type="radio"
                                       value="short"
                                       wire:model.blur="form.direction">
                                <div class="rounded-md border border-gray-200 py-2 text-center text-sm font-bold text-gray-500 peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 dark:border-gray-600 dark:text-gray-400 dark:peer-checked:bg-rose-500/10 dark:peer-checked:text-rose-400">SHORT</div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.entry_price') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="number"
                               step="0.00001"
                               wire:model.blur="form.entry_price">
                        @error('form.entry_price')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.exit_price') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="number"
                               step="0.00001"
                               wire:model.blur="form.exit_price">
                        @error('form.exit_price')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.size_lots') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="number"
                               step="0.01"
                               wire:model.blur="form.size">
                        @error('form.size')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.p&l') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="number"
                               step="0.01"
                               wire:model.blur="form.pnl">
                        @error('form.pnl')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.entry_date') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="datetime-local"
                               wire:model.blur="form.entry_time">
                        @error('form.entry_time')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.exit_date') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="datetime-local"
                               wire:model.blur="form.exit_time">
                        @error('form.exit_time')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.mae_price_worse') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="number"
                               step="0.00001"
                               wire:model.blur="form.mae_price"
                               placeholder="{{ __('labels.optional') }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.mfe_price_best') }}</label>
                        <input class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                               type="number"
                               step="0.00001"
                               wire:model.blur="form.mfe_price"
                               placeholder="{{ __('labels.optional') }}">
                    </div>


                    @if (Auth::user()->hasProAccess())
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.used_strategy') }}</label>
                            <select class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    wire:model.blur="form.strategy_id">
                                <option value="">{{ __('labels.without_strategy') }}</option>
                                @foreach ($strategiesList as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif


                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">{{ __('labels.notes_journal') }}</label>
                        <textarea class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                  rows="3"
                                  wire:model.blur="form.notes"></textarea>
                    </div>

                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                <button class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        @click="closeFormModal">{{ __('labels.cancel') }}</button>
                <button class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-bold text-white shadow hover:bg-indigo-700"
                        wire:click="save"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove
                          wire:target="save">{{ __('labels.save_operation') }}</span>
                    <span wire:loading
                          wire:target="save"><i class="fa-solid fa-spinner fa-spin"></i> {{ __('labels.saving') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
