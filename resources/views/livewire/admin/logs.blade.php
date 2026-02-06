<div class="p-6"
     x-data="logs">

    {{-- HEADER & TOOLBAR --}}
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">System Logs</h1>
            <p class="text-sm text-gray-500">Monitorización de errores y auditoría de acciones.</p>
        </div>
        <div class="flex gap-2">
            <button class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    wire:click="clearOldLogs"
                    wire:confirm="¿Borrar logs de INFO/SUCCESS de más de 30 días?">
                <i class="fa-solid fa-broom mr-2"></i> Limpiar Antiguos
            </button>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-6">
        {{-- Search --}}
        <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-bold text-gray-500">Buscar</label>
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                <input class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Buscar por acción, error...">
            </div>
        </div>

        {{-- Type --}}
        <div>
            <label class="mb-1 block text-xs font-bold text-gray-500">Tipo</label>
            <select class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    wire:model.live="type">
                <option value="">Todos</option>
                <option value="error">❌ Errores</option>
                <option value="warning">⚠️ Avisos</option>
                <option value="info">ℹ️ Info</option>
                <option value="success">✅ Éxitos</option>
            </select>
        </div>

        {{-- Status --}}
        <div>
            <label class="mb-1 block text-xs font-bold text-gray-500">Estado</label>
            <select class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    wire:model.live="status">
                <option value="">Todos</option>
                <option value="unresolved">🔴 Pendientes</option>
                <option value="resolved">🟢 Resueltos</option>
            </select>
        </div>

        {{-- User --}}
        <div>
            <label class="mb-1 block text-xs font-bold text-gray-500">Usuario</label>
            <select class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    wire:model.live="user_id">
                <option value="">Todos</option>
                @foreach ($users as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Dates --}}
        <div>
            <label class="mb-1 block text-xs font-bold text-gray-500">Fecha</label>
            <input class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                   wire:model.live="dateFrom"
                   type="date">
        </div>
    </div>

    {{-- TABLA --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Mensaje / Acción</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Origen</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Fecha</th>
                        <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($logs as $log)
                        <tr class="{{ $log->type === 'error' && !$log->resolved ? 'bg-red-50/50' : '' }} transition-colors hover:bg-gray-50">
                            {{-- Tipo Badge --}}
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                      :class="getStatusColor('{{ $log->type }}')">
                                    <i class="fa-solid"
                                       :class="getStatusIcon('{{ $log->type }}')"></i>
                                    {{ ucfirst($log->type) }}
                                </span>
                                @if ($log->type === 'error' && $log->resolved)
                                    <div class="mt-1 flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                                        <i class="fa-solid fa-check"></i> Resuelto
                                    </div>
                                @endif
                            </td>

                            {{-- Mensaje --}}
                            <td class="px-6 py-4">
                                <div class="max-w-md">
                                    <div class="text-sm font-bold text-gray-900">{{ $log->action }}</div>
                                    <div class="truncate text-xs text-gray-500"
                                         title="{{ $log->description ?? $log->exception_message }}">
                                        {{ Str::limit($log->description ?? $log->exception_message, 60) }}
                                    </div>
                                    @if ($log->exception_class)
                                        <div class="mt-1 inline-block rounded bg-red-50 px-1 font-mono text-[10px] text-red-500">
                                            {{ class_basename($log->exception_class) }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Origen --}}
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                <div class="font-bold">{{ $log->form ?? 'System' }}</div>
                                <div class="text-gray-400">{{ $log->method }} {{ Str::limit($log->url, 20) }}</div>
                            </td>

                            {{-- Usuario --}}
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($log->user)
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700">
                                            {{ substr($log->user->name, 0, 2) }}
                                        </div>
                                        <span class="text-sm text-gray-700">{{ $log->user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-xs italic text-gray-400">Sistema / Guest</span>
                                @endif
                            </td>

                            {{-- Fecha --}}
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                <div>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}</div>
                                <div class="text-gray-400">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}que</div>
                            </td>

                            {{-- Acciones --}}
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <button class="text-indigo-600 hover:text-indigo-900"
                                            @click="viewDetails({{ $log }})"
                                            title="Ver Detalles">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    @if ($log->type === 'error' && !$log->resolved)
                                        <button class="text-emerald-600 hover:text-emerald-900"
                                                @click="openResolve({{ $log }})"
                                                title="Marcar como Resuelto">
                                            <i class="fa-solid fa-check-to-slot"></i>
                                        </button>
                                    @endif

                                    <button class="text-gray-400 hover:text-red-600"
                                            wire:click="deleteLog({{ $log->id }})"
                                            wire:confirm="¿Borrar este log?">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-12 text-center text-gray-500"
                                colspan="6">
                                <i class="fa-solid fa-check-circle mb-2 text-4xl text-gray-200"></i>
                                <p>No hay logs registrados con estos filtros.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
            {{ $logs->links() }}
        </div>
    </div>

    {{-- MODAL: DETALLES --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
         x-show="showDetailModal"
         x-cloak>
        <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
             @click.away="showDetailModal = false">

            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900">
                    <i class="fa-solid fa-terminal text-gray-400"></i> Log Details
                    <span class="text-xs font-normal text-gray-500"
                          x-text="'ID: ' + selectedLog?.id"></span>
                </h3>
                <button class="text-gray-400 hover:text-gray-600"
                        @click="showDetailModal = false">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto p-6">
                {{-- Info Principal --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <span class="block text-xs font-bold uppercase text-gray-500">Acción</span>
                        <span class="text-gray-900"
                              x-text="selectedLog?.action"></span>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <span class="block text-xs font-bold uppercase text-gray-500">Contexto</span>
                        <span class="text-gray-900"
                              x-text="selectedLog?.form"></span>
                    </div>
                </div>

                {{-- Descripción --}}
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                    <span class="mb-1 block text-xs font-bold uppercase text-blue-500">Descripción / Mensaje</span>
                    <p class="font-medium text-blue-900"
                       x-text="selectedLog?.description || selectedLog?.exception_message"></p>
                </div>

                {{-- Stack Trace (Solo si es error) --}}
                <template x-if="selectedLog?.exception_trace">
                    <div>
                        <h4 class="mb-2 text-sm font-bold text-gray-900">Stack Trace & Excepción</h4>
                        <div class="overflow-x-auto rounded-lg bg-gray-900 p-4">
                            <div class="mb-2 border-b border-gray-700 pb-2 font-mono text-xs text-red-400">
                                <span x-text="selectedLog?.exception_class"></span> en
                                <span x-text="selectedLog?.file + ':' + selectedLog?.line"></span>
                            </div>
                            <pre class="font-mono text-xs leading-relaxed text-gray-300"
                                 x-text="selectedLog?.exception_trace"></pre>
                        </div>
                    </div>
                </template>

                {{-- Metadata Técnica --}}
                <div class="grid grid-cols-3 gap-4 border-t border-gray-100 pt-4 text-xs text-gray-500">
                    <div><span class="font-bold">IP:</span> <span x-text="selectedLog?.ip_address"></span></div>
                    <div><span class="font-bold">Method:</span> <span x-text="selectedLog?.method"></span></div>
                    <div class="col-span-3"><span class="font-bold">User Agent:</span> <span x-text="selectedLog?.user_agent"></span></div>
                    <div class="col-span-3"><span class="font-bold">URL:</span> <span x-text="selectedLog?.url"></span></div>
                </div>

                {{-- Resolución Info --}}
                <template x-if="selectedLog?.resolved">
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                        <h4 class="flex items-center gap-2 text-sm font-bold text-emerald-800">
                            <i class="fa-solid fa-check-circle"></i> Incidencia Resuelta
                        </h4>
                        <p class="mt-1 text-sm text-emerald-700"
                           x-text="selectedLog?.resolution_notes"></p>
                        <p class="mt-2 text-right text-xs text-emerald-600"
                           x-text="selectedLog?.resolved_at"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- MODAL: RESOLVER INCIDENCIA --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
         x-show="showResolveModal"
         x-cloak>
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
             @click.away="showResolveModal = false">
            <div class="p-6">
                <div class="mb-4 flex items-center gap-3 text-emerald-600">
                    <i class="fa-solid fa-check-to-slot text-2xl"></i>
                    <h3 class="text-lg font-bold text-gray-900">Resolver Incidencia</h3>
                </div>

                <p class="mb-4 text-sm text-gray-500">
                    Añade una nota sobre cómo se solucionó este error para futuras referencias.
                </p>

                <textarea class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                          wire:model="resolutionNotes"
                          rows="4"
                          placeholder="Ej: Se corrigió el bug en la línea 40 actualizando el array..."></textarea>

                @error('resolutionNotes')
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror

                <div class="mt-6 flex justify-end gap-3">
                    <button class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                            @click="showResolveModal = false">Cancelar</button>
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-emerald-700"
                            wire:click="markAsResolved">
                        Marcar como Resuelto
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
