<div class="mt-[55px] space-y-6 p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('labels.alerts') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('labels.alerts_description') }}
            </p>
        </div>
    </div>

    {{-- Tabla de Incidencias --}}
    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('labels.date') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('labels.account') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('labels.symbol') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('labels.type') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('labels.rule') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('labels.message') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('labels.pnl') }}
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('labels.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($violations as $violation)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        {{-- Fecha --}}
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ $violation->trade->exit_time->format('d/m/Y H:i') }}
                        </td>

                        {{-- Cuenta --}}
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ $violation->trade->account->name }}
                        </td>

                        {{-- Símbolo --}}
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $violation->trade->tradeAsset->symbol }}
                        </td>

                        {{-- Tipo (Long/Short) --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            @if ($violation->trade->direction === 'long')
                                <span class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    {{ __('labels.long') }}
                                </span>
                            @else
                                <span class="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                    {{ __('labels.short') }}
                                </span>
                            @endif
                        </td>

                        {{-- Regla violada --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            <span
                                  class="@if ($violation->rule_key === 'min_duration') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400
                                @elseif($violation->rule_key === 'news_trading') 
                                    bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400
                                @else 
                                    bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400 @endif rounded-full px-2 py-1 text-xs font-semibold">
                                {{ __('labels.' . $violation->rule_key) }}
                            </span>
                        </td>

                        {{-- Mensaje --}}
                        <td class="max-w-md px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $violation->message }}
                        </td>

                        {{-- PnL --}}
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                            <span class="{{ $violation->trade->pnl >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $violation->trade->pnl >= 0 ? '+' : '' }}{{ number_format($violation->trade->pnl, 2) }} $
                            </span>
                        </td>

                        {{-- Acciones --}}
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <a class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                               href="{{ route('trades') }}?highlight={{ $violation->trade_id }}"
                               wire:navigate>
                                <svg class="mr-1 h-4 w-4"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{ __('labels.view') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-12 text-center"
                            colspan="8">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="h-16 w-16 text-gray-400 dark:text-gray-600"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                                    {{ __('labels.no_violations') }}
                                </p>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('labels.no_violations_message') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Resumen Simple --}}
    <div class="flex items-center justify-between rounded-lg bg-blue-50 px-6 py-4 dark:bg-blue-900/20">
        <div class="flex items-center space-x-2">
            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm text-blue-900 dark:text-blue-300">
                <strong>{{ $violations->count() }}</strong> {{ __('labels.violations_detected') }}
            </span>
        </div>
        <a class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
           href="{{ route('cuentas') }}"
           wire:navigate>
            {{ __('labels.configure_rules') }} →
        </a>
    </div>
</div>
