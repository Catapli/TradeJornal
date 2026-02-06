<div class="min-h-screen bg-gray-50 p-6 font-sans text-gray-900"
     x-data="sessionHistory">

    {{-- HEADER & STATS --}}
    <div class="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-gray-900">Diario de Sesiones</h1>
            <p class="text-sm text-gray-500">Historial de rendimiento y disciplina.</p>
        </div>
        <div class="flex gap-4">
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-center shadow-sm">
                <span class="block text-[10px] font-bold uppercase text-gray-400">Total</span>
                <span class="text-xl font-black text-gray-800">{{ $stats['total'] }}</span>
            </div>
        </div>
    </div>

    {{-- BARRA DE FILTROS --}}
    <div class="mb-6 flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm lg:flex-row">

        {{-- Buscador --}}
        <div class="flex-1">
            <input class="w-full rounded-lg border-gray-200 bg-gray-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                   wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="Buscar estrategia, notas...">
        </div>

        {{-- Selects --}}
        <div class="flex gap-2">
            <select class="rounded-lg border-gray-200 bg-gray-50 text-sm"
                    wire:model.live="filterAccount">
                <option value="">Todas las Cuentas</option>
                @foreach ($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                @endforeach
            </select>
            <select class="rounded-lg border-gray-200 bg-gray-50 text-sm"
                    wire:model.live="filterMood">
                <option value="">Cualquier Mood</option>
                <option value="satisfied">😊 Satisfecho</option>
                <option value="tired">😴 Cansado</option>
                <option value="frustrated">🤬 Frustrado</option>
            </select>
        </div>

        {{-- Filtro Calendario (Mejora 5) --}}
        <div class="flex items-center gap-2 border-t border-gray-100 pt-2 lg:border-l lg:border-t-0 lg:pl-2 lg:pt-0">
            <input class="rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-500"
                   wire:model.live="dateFrom"
                   type="date">
            <span class="text-gray-400">-</span>
            <input class="rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-500"
                   wire:model.live="dateTo"
                   type="date">
        </div>
    </div>

    {{-- GRID DE TARJETAS --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($sessions as $session)
            <div class="{{ $session->session_pnl >= 0 ? 'border-gray-200' : 'border-rose-100' }} group relative flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-md">

                {{-- HEADER TARJETA --}}
                <div class="flex flex-col border-b border-gray-100 bg-gray-50/50 p-4">

                    {{-- Fila Superior: Fecha y Cuenta --}}
                    <div class="mb-2 flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-gray-400">{{ $session->start_time->format('d M, Y') }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wide text-indigo-600">{{ $session->strategy->name ?? 'N/A' }}</span>
                        </div>
                        {{-- Mejora 1: Badge de Cuenta --}}
                        <span class="rounded border border-gray-200 bg-white px-2 py-0.5 text-[9px] font-bold uppercase text-gray-500">
                            {{ Str::limit($session->account->name ?? 'Deleted', 12) }}
                        </span>
                    </div>

                    {{-- Fila Inferior: Horario y Estado --}}
                    <div class="flex items-center justify-between">
                        {{-- Mejora 2: Horario Start - End --}}
                        <div class="flex items-center gap-1.5 rounded border border-gray-200 bg-white px-2 py-0.5 font-mono text-[10px] text-gray-500">
                            <i class="fa-regular fa-clock text-[9px]"></i>
                            <span>{{ $session->start_time->format('H:i') }}</span>
                            <span class="text-gray-300">-</span>
                            <span>{{ $session->end_time?->format('H:i') ?? '...' }}</span>
                        </div>

                        @if ($session->status === 'active')
                            <span class="flex animate-pulse items-center gap-1 text-[9px] font-bold text-emerald-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> LIVE
                            </span>
                        @endif
                    </div>
                </div>

                {{-- BODY TARJETA --}}
                <div class="flex flex-1 flex-col items-center justify-center p-5">
                    <div class="text-center">
                        <div class="{{ $session->session_pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} text-3xl font-black tracking-tighter">
                            {{ $session->session_pnl >= 0 ? '+' : '' }}{{ number_format($session->session_pnl, 2) }}$
                        </div>
                        <span class="{{ $session->session_pnl >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} rounded px-1.5 py-0.5 text-xs font-bold">
                            {{ $session->session_pnl_percent >= 0 ? '+' : '' }}{{ $session->session_pnl_percent }}%
                        </span>
                    </div>
                </div>

                {{-- BOTÓN ACCIÓN --}}
                <button class="flex w-full items-center justify-center gap-2 border-t border-gray-50 bg-white py-3 text-xs font-bold text-gray-500 transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                        @click="openSession({{ $session->id }})">
                    ANALIZAR <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        @endforeach
    </div>

    {{-- PAGINACIÓN (Mejora 3) --}}
    <div class="mt-8">
        {{ $sessions->links() }}
    </div>

    {{-- ========================================= --}}
    {{-- MODAL / SLIDE-OVER (CONTROLADO POR ALPINE) --}}
    {{-- ========================================= --}}
    <div class="relative z-50"
         x-show="isOpen"
         x-on:keydown.escape.window="close()"
         style="display: none;"
         x-cloak>

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
             x-show="isOpen"
             x-transition.opacity.duration.300ms></div>

        {{-- Panel --}}
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">

                    <div class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl"
                         x-show="isOpen"
                         x-transition:enter="transform transition ease-in-out duration-500"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-500"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full">

                        {{-- LOADING STATE --}}
                        <div class="flex h-full items-center justify-center"
                             x-show="isLoading">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-500"></i>
                                <span class="text-xs font-bold text-gray-400">CARGANDO DATOS...</span>
                            </div>
                        </div>

                        {{-- CONTENT --}}
                        <div class="flex h-full flex-col overflow-y-scroll"
                             x-show="!isLoading && detail">

                            {{-- HEADER MODAL --}}
                            <div class="bg-gray-900 px-6 py-6 text-white">
                                <div class="flex justify-between">
                                    <h2 class="text-lg font-medium">Análisis de Sesión</h2>
                                    <button class="text-gray-400 hover:text-white"
                                            @click="close()"><i class="fa-solid fa-xmark text-xl"></i></button>
                                </div>
                                <div class="mt-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-4xl font-black tracking-tight"
                                              :class="detail?.pnl >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                              x-text="(detail?.pnl > 0 ? '+' : '') + detail?.pnl + '$'"></span>
                                        <span class="rounded bg-gray-800 px-2 py-1 text-xs font-bold text-gray-300"
                                              x-text="detail?.pnl_percent + '%'"></span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-400"
                                           x-text="detail?.duration"></p>
                                        <p class="text-xs text-gray-500"
                                           x-text="detail?.start_time + ' - ' + detail?.end_time"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- BODY MODAL --}}
                            <div class="p-6">

                                {{-- Auditoría --}}
                                <div class="mb-8 rounded-xl border p-4 shadow-sm"
                                     :class="detail?.is_overtraded ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50'">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-sm">
                                            <i class="fa-solid text-lg"
                                               :class="detail?.is_overtraded ? 'fa-ban text-rose-500' : 'fa-check text-emerald-500'"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold uppercase text-gray-500">Regla Max Trades</p>
                                            <p class="font-bold text-gray-900"
                                               x-text="detail?.is_overtraded ? 'VIOLADA (' + detail.total_trades + '/' + detail.limit_trades + ')' : 'RESPETADA'"></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Narrativa --}}
                                <div class="mb-8">
                                    <h3 class="mb-4 text-xs font-bold uppercase text-gray-500">Notas</h3>
                                    <div class="space-y-4 border-l-2 border-gray-100 pl-4">
                                        {{-- CORRECCIÓN: Usar note.id como key --}}
                                        <template x-for="note in detail?.notes"
                                                  :key="note.id">
                                            <div class="relative">
                                                <div class="absolute -left-[21px] mt-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-indigo-400"></div>
                                                <p class="text-xs font-bold text-gray-400">
                                                    <span x-text="note.time"></span> • <span class="uppercase text-indigo-500"
                                                          x-text="note.mood"></span>
                                                </p>
                                                <p class="text-sm text-gray-700"
                                                   x-text="note.text"></p>
                                            </div>
                                        </template>

                                        {{-- Mensaje si no hay notas --}}
                                        <div class="py-4 text-center text-xs text-gray-400"
                                             x-show="!detail?.notes || detail.notes.length === 0">
                                            Sin notas registradas
                                        </div>
                                    </div>
                                </div>


                                {{-- Trades --}}
                                <div>
                                    <h3 class="mb-4 text-xs font-bold uppercase text-gray-500">Ejecución Técnica</h3>
                                    <div class="overflow-hidden rounded-xl border border-gray-200">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-500">Hora</th>
                                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-500">Símbolo</th>
                                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-500">Dir</th>
                                                    <th class="px-4 py-3 text-right text-xs font-bold uppercase text-gray-500">PnL</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                {{-- CORRECCIÓN: Usar trade.id como key --}}
                                                <template x-for="trade in detail?.trades"
                                                          :key="trade.id">
                                                    <tr>
                                                        <td class="px-4 py-3 font-mono text-xs text-gray-500"
                                                            x-text="trade.time"></td>
                                                        <td class="px-4 py-3 text-xs font-bold text-gray-900"
                                                            x-text="trade.symbol"></td>
                                                        <td class="px-4 py-3 text-xs">
                                                            <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                                                  :class="trade.direction === 'long' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                                                  x-text="trade.direction"></span>
                                                        </td>
                                                        <td class="px-4 py-3 text-right text-xs font-bold"
                                                            :class="trade.pnl >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                                                            x-text="(trade.pnl > 0 ? '+' : '') + trade.pnl + '$'">
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>

                                        {{-- Mensaje si no hay trades --}}
                                        <div class="p-8 text-center text-xs text-gray-400"
                                             x-show="!detail?.trades || detail.trades.length === 0">
                                            Sin trades registrados
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
