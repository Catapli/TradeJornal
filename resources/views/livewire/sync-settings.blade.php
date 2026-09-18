{{-- Estado de la sincronización con MetaTrader: token, cuentas y diagnóstico. --}}
<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ __('sync.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('sync.subtitle') }}</p>
    </div>

    {{-- ── Token ────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
         x-data="{ copied: false }">

        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('sync.token.title') }}</h2>
        <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-400">{{ __('sync.token.help') }}</p>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-lg bg-gray-100 px-4 py-3 font-mono text-sm text-gray-800 dark:bg-gray-900 dark:text-gray-200"
                  x-ref="token">{{ $tokenVisible ? $this->token() : $this->maskedToken() }}</code>

            <button class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                    type="button"
                    wire:click="toggleToken">
                <i class="fa-solid {{ $tokenVisible ? 'fa-eye-slash' : 'fa-eye' }} mr-1"></i>
                {{ $tokenVisible ? __('sync.token.hide') : __('sync.token.show') }}
            </button>

            {{-- Se copia el token completo aunque esté enmascarado en pantalla. --}}
            <button class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                    type="button"
                    @click="navigator.clipboard.writeText(@js($this->token())).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                <i class="fa-solid fa-copy mr-1"></i>
                <span x-text="copied ? @js(__('sync.token.copied')) : @js(__('sync.token.copy'))"></span>
            </button>
        </div>

        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
            <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50 dark:border-red-500/40 dark:text-red-400 dark:hover:bg-red-500/10"
                    type="button"
                    wire:click="regenerate"
                    wire:confirm="{{ __('sync.token.regenerate_warning') }}">
                <i class="fa-solid fa-rotate mr-1"></i>{{ __('sync.token.regenerate') }}
            </button>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('sync.token.regenerate_warning') }}</p>
        </div>
    </div>

    {{-- ── Cuentas ──────────────────────────────────────────────── --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('sync.accounts.title') }}</h2>
        </div>

        @if ($this->accounts->isEmpty())
            <x-empty-state compact
                           icon="fa-wallet"
                           :title="__('sync.accounts.empty')">
                <a class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700"
                   href="{{ route('cuentas') }}">{{ __('sync.accounts.create') }}</a>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-gray-50 text-left dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('sync.accounts.account') }}</th>
                            <th class="px-6 py-3 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('sync.accounts.login') }}</th>
                            <th class="px-6 py-3 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('sync.accounts.status') }}</th>
                            <th class="px-6 py-3 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('sync.accounts.last_sync') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->accounts as $account)
                            @php
                                $days = $account->last_sync ? (int) $account->last_sync->diffInDays(now()) : null;
                                $stale = $account->sync && $days !== null && $days >= 2;
                            @endphp
                            <tr class="border-t border-gray-100 dark:border-gray-700/60">
                                <th class="px-6 py-4 text-left font-semibold text-gray-900 dark:text-gray-100" scope="row">
                                    {{ $account->name }}
                                    @if ($account->mt5_server)
                                        <span class="block text-xs font-normal text-gray-400 dark:text-gray-500">{{ $account->mt5_server }}</span>
                                    @endif
                                </th>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $account->mt5_login ?: '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($account->sync_error)
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700 dark:bg-red-500/15 dark:text-red-400">
                                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ __('sync.accounts.error') }}
                                        </span>
                                    @elseif ($account->sync)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                                            <i class="fa-solid fa-circle-check mr-1"></i>{{ __('sync.accounts.active') }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                            {{ __('sync.accounts.inactive') }}
                                        </span>
                                    @endif

                                    @if ($account->sync_error && $account->sync_error_message)
                                        <p class="mt-1 max-w-sm text-xs text-red-600 dark:text-red-400">{{ $account->sync_error_message }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $account->last_sync?->diffForHumans() ?? __('sync.accounts.never') }}

                                    @if ($stale)
                                        <p class="mt-1 max-w-sm text-xs text-amber-600 dark:text-amber-400">
                                            {{ __('sync.accounts.stale', ['days' => $days]) }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── Diagnóstico ──────────────────────────────────────────── --}}
    <div class="mt-6 rounded-2xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-900/50">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('sync.help.title') }}</h2>

        <ul class="mt-3 space-y-2">
            @foreach (__('sync.help.items') as $item)
                <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <i class="fa-solid fa-circle mt-2 text-[5px] text-gray-400"></i>
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>

        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('sync.help.alternative') }}</p>
            <a class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-700"
               href="{{ route('trades.import') }}">
                <i class="fa-solid fa-file-import mr-1"></i>{{ __('sync.help.alternative_cta') }}
            </a>
        </div>
    </div>
</div>
