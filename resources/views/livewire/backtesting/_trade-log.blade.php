{{-- FILTROS --}}
<div class="mb-4 flex items-center gap-3">
    <div class="flex overflow-hidden rounded-lg border border-gray-200">
        @foreach (['' => 'Todos', 'win' => 'Win', 'loss' => 'Loss', 'be' => 'BE'] as $val => $label)
            <button class="px-3 py-1.5 text-xs font-medium transition-colors"
                    type="button"
                    @click="setFilterOutcome('{{ $val }}')"
                    :class="filterOutcome === '{{ $val }}'
                        ?
                        'bg-blue-600 text-white' :
                        'bg-white text-gray-600 hover:bg-gray-50'">
                {{ $label }}
            </button>
        @endforeach
    </div>
    <div class="flex overflow-hidden rounded-lg border border-gray-200">
        @foreach (['' => 'Todas', 'london' => 'LON', 'new_york' => 'NY', 'asia' => 'ASIA', 'other' => 'Otra'] as $val => $label)
            <button class="px-3 py-1.5 text-xs font-medium transition-colors"
                    type="button"
                    @click="setFilterSession('{{ $val }}')"
                    :class="filterSession === '{{ $val }}'
                        ?
                        'bg-blue-600 text-white' :
                        'bg-white text-gray-600 hover:bg-gray-50'">
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>

{{-- EMPTY STATE --}}
@if ($trades->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-20 text-center">
        <svg class="mb-3 h-10 w-10 text-gray-300"
             xmlns="http://www.w3.org/2000/svg"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="1.5">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
        </svg>
        <p class="text-sm font-medium text-gray-600">Sin trades todavía</p>
        <p class="mt-1 text-xs text-gray-400">Pulsa "Añadir Trade" para registrar tu primera operación</p>
    </div>

    {{-- TABLA --}}
@else
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-4 py-3 text-left">
                        <button class="flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-900"
                                wire:click="sortColumn('trade_date')">
                            Fecha
                            @if ($sortBy === 'trade_date')
                                <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Dir.</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Entrada</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Salida</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">SL</th>
                    <th class="px-4 py-3 text-right">
                        <button class="ml-auto flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-900"
                                wire:click="sortColumn('pnl_r')">
                            R
                            @if ($sortBy === 'pnl_r')
                                <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Sesión</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Rating</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Reglas</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($trades as $trade)
                    <tr class="transition-colors hover:cursor-pointer hover:bg-gray-50"
                        @click.stop="openTradeDetail({{ Js::from([
                            'id' => $trade->id,
                            'date' => $trade->trade_date->format('d/m/Y'),
                            'direction' => $trade->direction,
                            'entry_price' => $trade->entry_price,
                            'exit_price' => $trade->exit_price,
                            'stop_loss' => $trade->stop_loss,
                            'pnl_r' => $trade->pnl_r,
                            'pnl' => $trade->pnl,
                            'session' => $trade->session,
                            'setup_rating' => $trade->setup_rating,
                            'followed_rules' => $trade->followed_rules,
                            'confluences' => $trade->confluences ?? [],
                            'notes' => $trade->notes,
                            'screenshot' => $trade->screenshot_url,
                        ]) }})">
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-gray-700">
                            {{ $trade->trade_date->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $trade->direction === 'long' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }} rounded-full px-2 py-0.5 text-xs font-medium">
                                {{ strtoupper($trade->direction) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-600">{{ $trade->entry_price }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-600">{{ $trade->exit_price }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $trade->stop_loss ?? '—' }}</td>
                        <td class="{{ $trade->pnl_r > 0 ? 'text-emerald-600' : ($trade->pnl_r < 0 ? 'text-red-500' : 'text-gray-400') }} px-4 py-3 text-right font-semibold tabular-nums">
                            {{ $trade->pnl_r ? ($trade->pnl_r > 0 ? '+' : '') . number_format($trade->pnl_r, 2) . 'R' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs capitalize text-gray-500">
                            {{ $trade->session ? str_replace('_', ' ', $trade->session) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-amber-400">
                            {{ $trade->setup_rating ? str_repeat('★', $trade->setup_rating) . str_repeat('☆', 5 - $trade->setup_rating) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="{{ $trade->followed_rules ? 'bg-emerald-500' : 'bg-red-400' }} inline-block h-2 w-2 rounded-full">
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button class="rounded p-1 text-gray-400 transition-colors hover:text-gray-700"
                                        @click.stop="openTradePanel({{ $trade->id }})">
                                    <svg class="h-3.5 w-3.5"
                                         xmlns="http://www.w3.org/2000/svg"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         stroke-width="2">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>
                                <button class="rounded p-1 text-gray-400 transition-colors hover:text-red-500"
                                        @click.stop="confirmDeleteTrade({{ $trade->id }})">
                                    <svg class="h-3.5 w-3.5"
                                         xmlns="http://www.w3.org/2000/svg"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         stroke-width="2">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
