<div class="max-w-fullxl mx-auto grid grid-cols-12 sm:px-6 lg:px-8">
    <div class="col-span-12 space-y-8">
        {{-- STATS CARDS --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <x-stat-card title="Winrate"
                         :value="$winrate ?? '0%'"
                         icon="📊"
                         color="emerald" />
            <x-stat-card title="P&L Mes"
                         :value="$pnl ?? '€0'"
                         icon="💰"
                         color="amber" />
            <x-stat-card title="Cuentas Activas"
                         :value="$accounts ?? 0"
                         icon="🏦"
                         color="blue" />
            <x-stat-card title="Trades Mes"
                         :value="$trades ?? 0"
                         icon="📈"
                         color="indigo" />
        </div>

        {{-- RECIENT TRADES --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-xl">
                <h3 class="mb-6 flex items-center text-xl font-bold text-gray-900">
                    📊 Últimos Trades
                    <span class="ml-auto text-sm text-gray-500">{{ $recentTrades->count() }} trades</span>
                </h3>
                <div class="space-y-3">
                    @forelse($recentTrades as $trade)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 transition-all hover:bg-gray-100">
                            <div class="flex items-center space-x-3">
                                <span class="{{ $trade->pnl > 0 ? 'from-emerald-400 to-green-500' : 'from-red-400 to-rose-500' }} flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r text-sm font-bold text-white">
                                    {{ $trade->pnl > 0 ? 'WIN' : 'LOSS' }}
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $trade->asset->symbol ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-500">{{ $trade->direction }} • {{ $trade->rr_ratio }} R:R</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="{{ $trade->pnl > 0 ? 'text-emerald-600' : 'text-red-600' }} text-xl font-bold">
                                    {{ $trade->pnl > 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }}€
                                </p>
                                <p class="text-xs text-gray-500">{{ $trade->created_at->format('d/m') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-gray-500">
                            <p>💼 Sin trades aún</p>
                            <p class="mt-1 text-sm">¡Registra tu primera operación!</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- CHARTS --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-xl">
                <h3 class="mb-6 text-xl font-bold text-gray-900">📈 Evolución Mes</h3>
                <div class="flex h-80 items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50">
                    <div class="text-center text-gray-500">
                        <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-blue-500 shadow-lg">
                            <svg class="h-12 w-12 text-white"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="1.5"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <p class="text-lg font-semibold">Gráficos próximamente</p>
                        <p class="mt-1 text-sm">Winrate, P&L, Drawdown</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
