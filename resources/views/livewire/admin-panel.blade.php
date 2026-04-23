{{--
    Admin Panel — TradeForge
    Tabs gestionados 100% por Alpine (x-show, nunca @if).
    Lógica de negocio 100% en Livewire.
--}}
<div class="min-h-screen bg-gray-50/50 p-6"
     x-data="adminPanel()"
     x-init="syncTab(@js($activeTab))">

    {{-- ─── HEADER ─────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 shadow-md">
                    <i class="fa-solid fa-shield-halved text-lg text-white"
                       aria-hidden="true"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900">Panel de Administración</h1>
                    <p class="text-sm text-gray-500">TradeForge · Vista de sistema</p>
                </div>
            </div>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm">
            <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
            Sistema activo
        </span>
    </div>

    {{-- ─── TABS NAV ────────────────────────────────────────────────────── --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-1 overflow-x-auto"
             role="tablist"
             aria-label="Secciones del panel">
            <template x-for="tab in tabs"
                      :key="tab.id">
                <button class="flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                        role="tab"
                        :id="`tab-${tab.id}`"
                        :aria-selected="isActive(tab.id)"
                        :aria-controls="`panel-${tab.id}`"
                        @click="switchTab(tab.id)"
                        type="button"
                        :class="isActive(tab.id) ?
                            'border-indigo-600 text-indigo-600' :
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                    <i :class="'fa-solid ' + tab.icon"
                       aria-hidden="true"></i>
                    <span x-text="tab.label"></span>
                </button>
            </template>
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
     TAB: OVERVIEW
══════════════════════════════════════════════════════════════════ --}}
    <div id="panel-overview"
         x-show="isActive('overview')"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         role="tabpanel"
         aria-labelledby="tab-overview">

        {{-- Skeleton mientras carga --}}
        <div class="space-y-6"
             wire:loading.delay>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach (range(1, 4) as $_)
                    <div class="border-border bg-surface animate-pulse rounded-lg border p-5">
                        <div class="bg-surface-dynamic mb-3 h-3 w-20 rounded"></div>
                        <div class="bg-surface-dynamic h-8 w-16 rounded"></div>
                        <div class="bg-surface-dynamic mt-2 h-2 w-12 rounded"></div>
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach (range(1, 3) as $_)
                    <div class="border-border bg-surface animate-pulse rounded-lg border p-5">
                        <div class="bg-surface-dynamic mb-3 h-3 w-20 rounded"></div>
                        <div class="bg-surface-dynamic h-8 w-16 rounded"></div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Contenido real --}}
        <div class="space-y-6"
             wire:loading.remove>

            {{-- Fila 1: Usuarios --}}
            <div>
                <p class="text-faint mb-2 text-xs font-medium uppercase tracking-wide">Usuarios</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <x-admin.kpi-card label="Total"
                                      icon="users"
                                      color="primary"
                                      :value="number_format($kpis['total_users'])" />
                    <x-admin.kpi-card label="Nuevos hoy"
                                      icon="user-plus"
                                      color="success"
                                      :value="number_format($kpis['new_today'])" />
                    <x-admin.kpi-card label="Activos 7d"
                                      icon="bolt"
                                      color="blue"
                                      :value="number_format($kpis['active_7d'])" />
                    <x-admin.kpi-card label="Activos 30d"
                                      icon="calendar"
                                      color="blue"
                                      :value="number_format($kpis['active_30d'])" />
                </div>
            </div>

            {{-- Fila 2: Plataforma --}}
            <div>
                <p class="text-faint mb-2 text-xs font-medium uppercase tracking-wide">Plataforma</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <x-admin.kpi-card label="Trades"
                                      icon="arrow-trend-up"
                                      color="primary"
                                      :value="number_format($kpis['total_trades'])" />
                    <x-admin.kpi-card label="Cuentas"
                                      icon="wallet"
                                      color="warning"
                                      :value="number_format($kpis['total_accounts'])" />
                    <x-admin.kpi-card label="Sesiones"
                                      icon="wave-square"
                                      color="purple"
                                      :value="number_format($kpis['total_sessions'])" />
                </div>
            </div>

            {{-- Fila 3: Suscripciones --}}
            <div>
                <p class="text-faint mb-2 text-xs font-medium uppercase tracking-wide">Suscripciones</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <x-admin.kpi-card label="Pro"
                                      icon="crown"
                                      color="success"
                                      :value="number_format($kpis['pro_users'])" />
                    <x-admin.kpi-card label="Free"
                                      icon="star"
                                      color="primary"
                                      :value="number_format($kpis['free_users'])" />

                    {{-- Card conversión + retención --}}
                    @php
                        $conversionRate = $kpis['total_users'] > 0 ? round(($kpis['pro_users'] / $kpis['total_users']) * 100, 1) : 0;
                        $retentionRate = $kpis['total_users'] > 0 ? round(($kpis['active_30d'] / $kpis['total_users']) * 100, 1) : 0;
                    @endphp

                    <div class="border-border bg-surface rounded-lg border p-5">
                        <div class="mb-3 flex items-start justify-between">
                            <span class="text-muted text-xs font-medium uppercase tracking-wide">Conversión</span>
                            <span class="bg-success/10 flex h-7 w-7 shrink-0 items-center justify-center rounded-md">
                                <i class="fa-solid fa-percent text-success text-sm"
                                   aria-hidden="true"></i>
                            </span>
                        </div>
                        <div class="text-text text-3xl font-bold tabular-nums">{{ $conversionRate }}%</div>
                        <div class="text-muted mt-1 text-xs">
                            Retención 30d: <span class="text-text font-medium">{{ $retentionRate }}%</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         TAB: USUARIOS
    ══════════════════════════════════════════════════════════════════ --}}
    <div id="panel-users"
         x-show="isActive('users')"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         role="tabpanel"
         aria-labelledby="tab-users">

        {{-- FILTROS --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 gap-2">

                <div class="relative max-w-sm flex-1">
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"
                       aria-hidden="true"></i>
                    <input class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                           type="search"
                           placeholder="Buscar por nombre o email…"
                           x-model="localSearch"
                           @input.debounce.300ms="$wire.set('search', localSearch)"
                           aria-label="Buscar usuario" />
                </div>

                <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        x-model="localStatus"
                        @change="$wire.set('filterStatus', localStatus)"
                        aria-label="Filtrar por estado">
                    <option value="all">Todos</option>
                    <option value="active">Activos</option>
                    <option value="banned">Baneados</option>
                </select>
            </div>

            <button class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 transition hover:border-indigo-300 hover:text-indigo-600"
                    x-show="localSearch !== '' || localStatus !== 'all'"
                    x-transition
                    @click="localSearch = ''; localStatus = 'all'; $wire.resetFilters()"
                    type="button">
                <i class="fa-solid fa-xmark"
                   aria-hidden="true"></i>
                Limpiar filtros
            </button>
        </div>

        {{-- TABLA --}}
        <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            {{-- Overlay mientras Livewire recarga --}}
            <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-[1px]"
                 wire:loading.delay
                 wire:target="search, filterStatus, resetFilters, banUser, unbanUser">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <i class="fa-solid fa-spinner fa-spin"
                       aria-hidden="true"></i>
                    Cargando…
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm"
                       aria-label="Tabla de usuarios">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Última conexión</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Trades</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Cuentas</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($this->users as $user)
                            <tr class="transition-colors duration-100 hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-600">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-400">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-400">
                                    {{ $user->last_seen_at?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums text-gray-700">
                                    {{ number_format($user->trades_count) }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums text-gray-700">
                                    {{ number_format($user->accounts_count) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($user->is_banned)
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-600">
                                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Baneado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-600">
                                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Activo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($user->id !== auth()->id())
                                        @if ($user->is_banned)
                                            <button class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-emerald-600 transition hover:bg-emerald-50"
                                                    wire:click="unbanUser({{ $user->id }})"
                                                    wire:confirm="¿Desbanear a {{ $user->name }}? El usuario recuperará el acceso inmediatamente."
                                                    wire:loading.attr="disabled"
                                                    type="button">
                                                <i class="fa-solid fa-shield-check"
                                                   aria-hidden="true"></i>
                                                Desbanear
                                            </button>
                                        @else
                                            <button class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-500 transition hover:bg-red-50"
                                                    wire:click="banUser({{ $user->id }})"
                                                    wire:confirm="¿Banear a {{ $user->name }}? El usuario perderá el acceso inmediatamente."
                                                    wire:loading.attr="disabled"
                                                    type="button">
                                                <i class="fa-solid fa-shield-slash"
                                                   aria-hidden="true"></i>
                                                Banear
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">Tú</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-16 text-center"
                                    colspan="6">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                            <i class="fa-solid fa-users text-xl text-gray-400"
                                               aria-hidden="true"></i>
                                        </div>
                                        <p class="text-sm font-medium text-gray-500">No se encontraron usuarios</p>
                                        <button class="text-xs text-indigo-600 hover:underline"
                                                @click="localSearch = ''; localStatus = 'all'; $wire.resetFilters()"
                                                type="button">
                                            Limpiar filtros
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->users->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $this->users->links() }}
                </div>
            @endif

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         TAB: ALMACENAMIENTO
    ══════════════════════════════════════════════════════════════════ --}}
    <div id="panel-storage"
         x-show="isActive('storage')"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         role="tabpanel"
         aria-labelledby="tab-storage">

        {{-- Skeleton --}}
        <div class="space-y-4"
             wire:loading.delay
             wire:target="render">
            <div class="h-28 w-full animate-pulse rounded-xl bg-gray-100"></div>
            <div class="h-48 w-full animate-pulse rounded-xl bg-gray-100"></div>
        </div>

        <div wire:loading.remove
             wire:target="render">

            {{-- Aviso si la API de Cloudflare falló --}}
            @if ($storageStats['error'] ?? null)
                <div class="mb-4 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <i class="fa-solid fa-triangle-exclamation shrink-0"
                       aria-hidden="true"></i>
                    {{ $storageStats['error'] }}
                </div>
            @endif

            {{-- Barra de uso del sistema --}}
            <div class="mb-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Uso del sistema</h2>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ $storageStats['formatted_total'] }} usados de {{ $storageStats['formatted_limit'] }}
                            · Datos vía Cloudflare R2 Analytics
                        </p>
                    </div>
                    <span class="text-2xl font-black tabular-nums"
                          :class="{
                              'text-emerald-600': {{ $storageStats['used_percent'] }} < 60,
                              'text-amber-500': {{ $storageStats['used_percent'] }} >= 60 && {{ $storageStats['used_percent'] }} < 80,
                              'text-red-500': {{ $storageStats['used_percent'] }} >= 80
                          }">{{ $storageStats['used_percent'] }}%</span>
                </div>

                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100"
                     role="progressbar"
                     aria-valuenow="{{ $storageStats['used_percent'] }}"
                     aria-valuemin="0"
                     aria-valuemax="100">
                    <div class="h-full rounded-full transition-all duration-700"
                         style="width: {{ min($storageStats['used_percent'], 100) }}%"
                         :class="{
                             'bg-emerald-500': {{ $storageStats['used_percent'] }} < 60,
                             'bg-amber-400': {{ $storageStats['used_percent'] }} >= 60 && {{ $storageStats['used_percent'] }} < 80,
                             'bg-red-500': {{ $storageStats['used_percent'] }} >= 80
                         }">
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-6 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-file"
                           aria-hidden="true"></i>
                        {{ number_format($storageStats['total_files']) }} objetos
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-hard-drive"
                           aria-hidden="true"></i>
                        {{ $storageStats['formatted_total'] }} / {{ $storageStats['formatted_limit'] }}
                    </span>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                        <i class="fa-solid fa-circle-info text-sm text-blue-500"
                           aria-hidden="true"></i>
                    </div>
                    <p class="text-sm text-gray-500">
                        Cloudflare R2 Analytics proporciona métricas agregadas por bucket.
                        Para ver el desglose por usuario necesitarías una tabla de metadatos en BD.
                    </p>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         TAB: COLAS / JOBS
    ══════════════════════════════════════════════════════════════════ --}}
    <div id="panel-queues"
         x-show="isActive('queues')"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         role="tabpanel"
         aria-labelledby="tab-queues">

        {{-- Skeleton --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3"
             wire:loading.delay
             wire:target="render">
            @foreach (range(1, 3) as $_)
                <div class="h-28 animate-pulse rounded-xl bg-gray-100"></div>
            @endforeach
        </div>

        <div wire:loading.remove
             wire:target="render">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                {{-- Card: Jobs fallidos --}}
                <div class="rounded-xl border p-5 shadow-sm transition-colors"
                     :class="{{ $queueStats['failed_count'] > 0 ? 'true' : 'false' }}
                         ?
                         'border-red-200 bg-red-50' :
                         'border-gray-200 bg-white'">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Jobs Fallidos</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                             :class="{{ $queueStats['failed_count'] > 0 ? 'true' : 'false' }} ? 'bg-red-100' : 'bg-gray-100'">
                            <i class="fa-solid fa-triangle-exclamation text-sm"
                               :class="{{ $queueStats['failed_count'] > 0 ? 'true' : 'false' }} ? 'text-red-500' : 'text-gray-400'"
                               aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-black tabular-nums"
                         :class="{{ $queueStats['failed_count'] > 0 ? 'true' : 'false' }} ? 'text-red-600' : 'text-gray-900'">
                        {{ number_format($queueStats['failed_count']) }}
                    </div>
                    @if ($queueStats['failed_count'] > 0)
                        <button class="mt-3 flex items-center gap-1.5 text-xs font-semibold text-red-500 transition hover:text-red-700 hover:underline"
                                wire:click="clearFailedJobs"
                                wire:confirm="¿Eliminar todos los jobs fallidos? Esta acción no se puede deshacer."
                                wire:loading.attr="disabled"
                                type="button">
                            <i class="fa-solid fa-trash"
                               aria-hidden="true"></i>
                            Limpiar jobs fallidos
                        </button>
                    @else
                        <p class="mt-2 text-xs text-gray-400">Sin errores en cola</p>
                    @endif
                </div>

                {{-- Card: Jobs pendientes --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Pendientes</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50">
                            <i class="fa-solid fa-clock text-sm text-amber-500"
                               aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-black tabular-nums text-gray-900">
                        {{ number_format($queueStats['pending_count']) }}
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Jobs disponibles para procesar</p>
                </div>

                {{-- Card: Último job --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Último Job</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50">
                            <i class="fa-solid fa-wave-square text-sm text-indigo-500"
                               aria-hidden="true"></i>
                        </div>
                    </div>
                    @if ($queueStats['last_job'])
                        <div class="text-sm font-semibold text-gray-900">{{ $queueStats['last_job']['name'] }}</div>
                        <div class="mt-1 text-xs text-gray-400">Cola: {{ $queueStats['last_job']['queue'] }}</div>
                        @if ($queueStats['last_job']['created'])
                            <div class="mt-1 text-xs text-gray-400">
                                {{ \Carbon\Carbon::createFromTimestamp($queueStats['last_job']['created'])->diffForHumans() }}
                            </div>
                        @endif
                    @else
                        <div class="text-sm text-gray-400">Sin jobs registrados</div>
                    @endif

                    @if ($queueStats['horizon_stats'])
                        <div class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-600">
                            <i class="fa-solid fa-bolt"
                               aria-hidden="true"></i>
                            Horizon activo
                        </div>
                    @endif
                </div>

            </div>

            <p class="mt-4 flex items-center gap-1.5 text-xs text-gray-400">
                <i class="fa-solid fa-circle-info"
                   aria-hidden="true"></i>
                Datos calculados en tiempo real desde las tablas
                <code class="rounded bg-gray-100 px-1 py-0.5 font-mono">jobs</code>
                y
                <code class="rounded bg-gray-100 px-1 py-0.5 font-mono">failed_jobs</code>.
            </p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         TAB: MONITOR MT5
    ══════════════════════════════════════════════════════════════════ --}}
    <div id="panel-mt5"
         x-show="isActive('mt5')"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         role="tabpanel"
         aria-labelledby="tab-mt5">

        {{-- KPIs de estado --}}
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Con Sync</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50">
                        <i class="fa-solid fa-rotate text-sm text-indigo-500"
                           aria-hidden="true"></i>
                    </div>
                </div>
                <div class="text-3xl font-black tabular-nums text-gray-900">{{ $mt5Stats['totals']['sync_enabled'] ?? 0 }}</div>
                <div class="mt-1 text-xs text-gray-400">de {{ $mt5Stats['totals']['total'] ?? 0 }} cuentas</div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-emerald-700">Saludables</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                        <i class="fa-solid fa-circle-check text-sm text-emerald-600"
                           aria-hidden="true"></i>
                    </div>
                </div>
                <div class="text-3xl font-black tabular-nums text-emerald-700">{{ $mt5Stats['totals']['healthy'] ?? 0 }}</div>
                <div class="mt-1 text-xs text-emerald-600">sync &lt; {{ $mt5Stats['stale_hours'] }}h</div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-amber-700">Inactivas</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                        <i class="fa-solid fa-clock text-sm text-amber-500"
                           aria-hidden="true"></i>
                    </div>
                </div>
                <div class="text-3xl font-black tabular-nums text-amber-600">{{ $mt5Stats['totals']['stale'] ?? 0 }}</div>
                <div class="mt-1 text-xs text-amber-600">sin sync &gt; {{ $mt5Stats['stale_hours'] }}h</div>
            </div>

            <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-red-700">Con Error</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100">
                        <i class="fa-solid fa-triangle-exclamation text-sm text-red-500"
                           aria-hidden="true"></i>
                    </div>
                </div>
                <div class="text-3xl font-black tabular-nums text-red-600">{{ $mt5Stats['totals']['with_errors'] ?? 0 }}</div>
                <div class="mt-1 text-xs text-red-500">sync_error activo</div>
            </div>

        </div>

        {{-- Cuentas con error --}}
        @if (count($mt5Stats['error_accounts']) > 0)
            <div class="mb-6 overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-red-100 bg-red-50 px-4 py-3">
                    <i class="fa-solid fa-triangle-exclamation text-sm text-red-500"
                       aria-hidden="true"></i>
                    <h2 class="text-sm font-semibold text-red-700">Cuentas con error activo</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Cuenta</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Plataforma</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Último sync</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($mt5Stats['error_accounts'] as $acc)
                                <tr class="transition-colors duration-100 hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ $acc->name }}</div>
                                        <div class="font-mono text-xs text-gray-400">{{ $acc->mt5_login }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-gray-700">{{ $acc->user_name }}</div>
                                        <div class="text-xs text-gray-400">{{ $acc->user_email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium uppercase text-gray-600">
                                            {{ $acc->platform }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-400">
                                        {{ $acc->last_sync ? \Carbon\Carbon::parse($acc->last_sync)->diffForHumans() : 'Nunca' }}
                                    </td>
                                    <td class="max-w-xs px-4 py-3">
                                        <p class="truncate text-xs text-red-500"
                                           title="{{ $acc->sync_error_message }}">
                                            {{ $acc->sync_error_message ?? 'Error desconocido' }}
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Cuentas inactivas --}}
        @if (count($mt5Stats['stale_accounts']) > 0)
            <div class="mb-6 overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-amber-100 bg-amber-50 px-4 py-3">
                    <i class="fa-solid fa-clock text-sm text-amber-500"
                       aria-hidden="true"></i>
                    <h2 class="text-sm font-semibold text-amber-700">Cuentas sin sincronizar (+{{ $mt5Stats['stale_hours'] }}h)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Cuenta</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Broker</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Último sync</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($mt5Stats['stale_accounts'] as $acc)
                                <tr class="transition-colors duration-100 hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ $acc->name }}</div>
                                        <div class="font-mono text-xs text-gray-400">{{ $acc->mt5_login }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-gray-700">{{ $acc->user_name }}</div>
                                        <div class="text-xs text-gray-400">{{ $acc->user_email }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-400">
                                        {{ $acc->broker_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-amber-600">
                                        {{ $acc->last_sync ? \Carbon\Carbon::parse($acc->last_sync)->diffForHumans() : 'Nunca' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Últimas sincronizaciones exitosas --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-gray-100 bg-gray-50 px-4 py-3">
                <i class="fa-solid fa-circle-check text-sm text-emerald-500"
                   aria-hidden="true"></i>
                <h2 class="text-sm font-semibold text-gray-900">Últimas sincronizaciones exitosas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Cuenta</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Usuario</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Balance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Hace</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($mt5Stats['recent_syncs'] as $acc)
                            <tr class="transition-colors duration-100 hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900">{{ $acc->name }}</div>
                                    <div class="font-mono text-xs text-gray-400">{{ $acc->mt5_login }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $acc->user_name }}</td>
                                <td class="px-4 py-3 text-right font-mono tabular-nums text-gray-900">
                                    {{ number_format($acc->current_balance, 2) }} {{ $acc->currency }}
                                </td>
                                <td class="px-4 py-3 text-xs font-semibold text-emerald-600">
                                    {{ \Carbon\Carbon::parse($acc->last_sync)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-12 text-center text-sm text-gray-400"
                                    colspan="4">
                                    No hay sincronizaciones recientes
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

@script
    <script>
        $wire.on('notify', ({
            type,
            message
        }) => {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type,
                    message
                }
            }));
        });
    </script>
@endscript
