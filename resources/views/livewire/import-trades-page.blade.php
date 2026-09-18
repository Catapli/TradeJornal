{{-- Asistente de importación de historiales. Cuatro pasos, un solo componente. --}}
<div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- ── Cabecera ─────────────────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ __('import.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('import.subtitle') }}</p>
    </div>

    {{-- ── Pasos ────────────────────────────────────────────────── --}}
    @php
        $steps = ['upload', 'map', 'preview', 'done'];
        $currentIndex = array_search($step, $steps, true);
    @endphp

    <ol class="mb-8 flex items-center gap-2 overflow-x-auto">
        @foreach ($steps as $i => $name)
            <li class="flex shrink-0 items-center gap-2">
                <span @class([
                    'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                    'bg-indigo-600 text-white' => $i === $currentIndex,
                    'bg-emerald-500 text-white' => $i < $currentIndex,
                    'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => $i > $currentIndex,
                ])>
                    @if ($i < $currentIndex)
                        <i class="fa-solid fa-check text-[10px]"></i>
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                <span @class([
                    'text-sm font-semibold',
                    'text-gray-900 dark:text-gray-100' => $i === $currentIndex,
                    'text-gray-400 dark:text-gray-500' => $i !== $currentIndex,
                ])>{{ __('import.steps.' . $name) }}</span>

                @if (! $loop->last)
                    <span class="mx-2 h-px w-8 bg-gray-200 dark:bg-gray-700"></span>
                @endif
            </li>
        @endforeach
    </ol>

    {{-- ═══════════════════════════════════════════════════════════
         PASO 1 · SUBIR
    ═══════════════════════════════════════════════════════════ --}}
    @if ($step === 'upload')
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">

            @if ($this->accounts->isEmpty())
                <div class="py-10 text-center">
                    <i class="fa-solid fa-wallet mb-3 text-3xl text-gray-300 dark:text-gray-600"></i>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('import.upload.no_accounts') }}</p>
                    <a class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700"
                       href="{{ route('cuentas') }}">
                        {{ __('import.upload.create_account') }}
                    </a>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2">

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400"
                               for="import-account">{{ __('import.upload.account') }}</label>
                        <select class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                id="import-account"
                                wire:model="accountId">
                            @foreach ($this->accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} · {{ $account->currency }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('import.upload.account_help') }}</p>
                        @error('accountId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400"
                               for="import-file">{{ __('import.upload.file') }}</label>
                        <input class="w-full rounded-lg border border-gray-300 text-sm file:mr-3 file:rounded-l-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:file:bg-gray-600 dark:file:text-gray-100"
                               id="import-file"
                               type="file"
                               accept=".csv,.tsv,.txt,.html,.htm"
                               wire:model="file">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('import.upload.file_help') }}</p>
                        @error('file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

                        <div wire:loading wire:target="file">
                            <p class="mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                <i class="fa-solid fa-circle-notch fa-spin mr-1"></i>{{ __('import.upload.analyzing') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-gray-50 p-4 dark:bg-gray-900/50">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('import.upload.supported') }}</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ __('import.upload.supported_list') }}</p>
                </div>

                <div class="mt-6 flex justify-end">
                    <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:opacity-50"
                            type="button"
                            wire:click="analyze"
                            wire:loading.attr="disabled"
                            wire:target="analyze,file">
                        <span wire:loading.remove wire:target="analyze">{{ __('import.upload.analyze') }}</span>
                        <span wire:loading wire:target="analyze"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i>{{ __('import.upload.analyzing') }}</span>
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         PASO 2 · EMPAREJAR COLUMNAS
    ═══════════════════════════════════════════════════════════ --}}
    @if ($step === 'map')
        @php($missing = $this->missingRequired())

        <div class="space-y-6">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('import.map.detected') }}</p>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                            @if ($presetKey === 'generic')
                                {{ __('import.map.detected_generic') }}
                            @else
                                {{ __('import.map.detected_platform', ['platform' => $this->presets[$presetKey]->label]) }}
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400"
                                   for="import-preset">{{ __('import.map.platform') }}</label>
                            <select class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    id="import-preset"
                                    wire:model.live="presetKey">
                                @foreach ($this->presets as $key => $preset)
                                    <option value="{{ $key }}">{{ $preset->label }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($this->profiles->isNotEmpty())
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400"
                                       for="import-profile">{{ __('import.map.load_profile') }}</label>
                                <div class="flex gap-2">
                                    <select class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                            id="import-profile"
                                            wire:model="loadedProfileId">
                                        <option value="">—</option>
                                        @foreach ($this->profiles as $profile)
                                            <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                            type="button"
                                            wire:click="applyProfile">
                                        <i class="fa-solid fa-arrow-rotate-left"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($missing !== [])
                    <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        {{ __('import.map.missing_required', ['fields' => implode(', ', $missing)]) }}
                    </div>
                @endif
            </div>

            {{-- Emparejamiento --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('import.map.columns') }}</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px] text-sm">
                        <thead class="bg-gray-50 text-left dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.map.field') }}</th>
                                <th class="px-6 py-3 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.map.column') }}</th>
                                <th class="px-6 py-3 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.map.sample') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($sample = $this->parsed()['rows'][0] ?? [])
                            @foreach (\App\Services\Import\ImportPreset::FIELDS as $field => $meta)
                                <tr class="border-t border-gray-100 dark:border-gray-700/60">
                                    <th class="px-6 py-3 text-left font-medium text-gray-700 dark:text-gray-300" scope="row">
                                        {{ __('import.fields.' . $field) }}
                                        @if ($meta['required'])
                                            <span class="ml-1 text-xs font-bold text-red-500">*</span>
                                        @endif
                                    </th>
                                    <td class="px-6 py-3">
                                        <select class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                                wire:model.live="mapping.{{ $field }}"
                                                aria-label="{{ __('import.fields.' . $field) }}">
                                            <option value="">{{ __('import.map.ignore') }}</option>
                                            @foreach ($headers as $header)
                                                <option value="{{ $header }}">{{ $header }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-6 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                        {{ $mapping[$field] ? \Illuminate\Support\Str::limit($sample[$mapping[$field]] ?? '', 24) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Opciones --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('import.map.options') }}</h2>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400"
                               for="import-decimal">{{ __('import.map.decimal') }}</label>
                        <select class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                id="import-decimal"
                                wire:model.live="decimalMode">
                            <option value="auto">{{ __('import.map.decimal_auto') }}</option>
                            <option value="dot">{{ __('import.map.decimal_dot') }}</option>
                            <option value="comma">{{ __('import.map.decimal_comma') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400"
                               for="import-strategy">{{ __('import.map.strategy') }}</label>
                        <select class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                id="import-strategy"
                                wire:model="strategyId">
                            <option value="">{{ __('import.map.strategy_none') }}</option>
                            @foreach ($this->strategies as $strategy)
                                <option value="{{ $strategy->id }}">{{ $strategy->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="flex items-start gap-3 md:col-span-2">
                        <input class="mt-1 rounded border-gray-300 text-indigo-600 dark:border-gray-600 dark:bg-gray-700"
                               type="checkbox"
                               wire:model.live="pnlIncludesFees">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('import.map.fees_included') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('import.map.fees_included_help') }}</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 md:col-span-2">
                        <input class="mt-1 rounded border-gray-300 text-indigo-600 dark:border-gray-600 dark:bg-gray-700"
                               type="checkbox"
                               wire:model="recalculateBalance">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('import.map.recalculate_balance') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('import.map.recalculate_balance_help') }}</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input class="mt-1 rounded border-gray-300 text-indigo-600 dark:border-gray-600 dark:bg-gray-700"
                               type="checkbox"
                               wire:model.live="saveProfile">
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('import.map.save_profile') }}</span>
                    </label>

                    @if ($saveProfile)
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-gray-500 dark:text-gray-400"
                                   for="import-profile-name">{{ __('import.map.profile_name') }}</label>
                            <input class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                   id="import-profile-name"
                                   type="text"
                                   maxlength="60"
                                   wire:model="profileName"
                                   placeholder="{{ $this->presets[$presetKey]->label }}">
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap justify-between gap-3">
                <button class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                        type="button"
                        wire:click="reset_">
                    {{ __('import.map.back') }}
                </button>
                <button class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:opacity-50"
                        type="button"
                        @disabled($missing !== [])
                        wire:click="goToPreview">
                    {{ __('import.map.continue') }}
                </button>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         PASO 3 · VISTA PREVIA
    ═══════════════════════════════════════════════════════════ --}}
    @if ($step === 'preview')
        @php($preview = $this->preview())

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('import.preview.title') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('import.preview.rows_found', ['count' => $preview['total']]) }}
                </p>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead class="bg-gray-50 text-left dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-2 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.fields.symbol') }}</th>
                                <th class="px-4 py-2 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.fields.direction') }}</th>
                                <th class="px-4 py-2 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.fields.entry_time') }}</th>
                                <th class="px-4 py-2 font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.fields.exit_time') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.fields.size') }}</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-500 dark:text-gray-400" scope="col">{{ __('import.fields.pnl') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($preview['rows'] as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-700/60">
                                    <td class="px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">{{ $row['symbol'] }}</td>
                                    <td class="px-4 py-2">
                                        <span @class([
                                            'rounded px-2 py-0.5 text-xs font-bold',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $row['direction'] === 'long',
                                            'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400' => $row['direction'] === 'short',
                                        ])>{{ strtoupper($row['direction']) }}</span>
                                    </td>
                                    <td class="px-4 py-2 tabular-nums text-gray-600 dark:text-gray-400">{{ $row['entry_time']->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-2 tabular-nums text-gray-600 dark:text-gray-400">{{ $row['exit_time']->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums text-gray-600 dark:text-gray-400">{{ $row['size'] }}</td>
                                    <td @class([
                                        'px-4 py-2 text-right font-bold tabular-nums',
                                        'text-emerald-600 dark:text-emerald-400' => $row['pnl'] >= 0,
                                        'text-red-600 dark:text-red-400' => $row['pnl'] < 0,
                                    ])>{{ number_format($row['pnl'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400" colspan="6">
                                        {{ __('import.done.nothing') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('import.preview.sample_note', ['count' => count($preview['rows'])]) }}
                </p>
            </div>

            @if ($preview['errors'] !== [])
                <div class="rounded-2xl border border-red-200 bg-red-50 p-6 dark:border-red-500/30 dark:bg-red-500/10">
                    <h3 class="text-sm font-bold text-red-800 dark:text-red-300">{{ __('import.done.errors_title') }}</h3>
                    <ul class="mt-3 space-y-2 text-sm text-red-700 dark:text-red-300">
                        @foreach ($preview['errors'] as $error)
                            <li>
                                <span class="font-semibold">{{ __('import.done.row', ['number' => $error['row']]) }}:</span>
                                {{ implode(' ', $error['errors']) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-wrap justify-between gap-3">
                <button class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                        type="button"
                        wire:click="$set('step', 'map')">
                    {{ __('import.preview.back') }}
                </button>
                <button class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                        type="button"
                        wire:click="import"
                        wire:loading.attr="disabled"
                        wire:target="import">
                    <span wire:loading.remove wire:target="import"><i class="fa-solid fa-file-import mr-1"></i>{{ __('import.preview.confirm') }}</span>
                    <span wire:loading wire:target="import"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i>{{ __('import.preview.importing') }}</span>
                </button>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         PASO 4 · RESULTADO
    ═══════════════════════════════════════════════════════════ --}}
    @if ($step === 'done' && $result)
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                <span @class([
                    'mx-auto flex h-14 w-14 items-center justify-center rounded-full text-2xl',
                    'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400' => $result['imported'] > 0,
                    'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => $result['imported'] === 0,
                ])>
                    <i class="fa-solid {{ $result['imported'] > 0 ? 'fa-check' : 'fa-circle-info' }}"></i>
                </span>

                <h2 class="mt-4 text-xl font-black text-gray-900 dark:text-gray-100">{{ __('import.done.title') }}</h2>

                @if ($result['imported'] === 0 && $result['skipped'] > 0)
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('import.done.all_duplicated') }}</p>
                @elseif ($result['imported'] === 0)
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('import.done.nothing') }}</p>
                @endif

                <div class="mx-auto mt-6 grid max-w-md grid-cols-3 gap-3">
                    @foreach ([['imported', 'text-emerald-600 dark:text-emerald-400'], ['skipped', 'text-gray-500 dark:text-gray-400'], ['failed', 'text-red-600 dark:text-red-400']] as [$key, $tone])
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900/50">
                            <p class="text-2xl font-black tabular-nums {{ $tone }}">{{ $result[$key] }}</p>
                            <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('import.done.' . $key) }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($result['newSymbols'] !== [])
                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('import.done.new_symbols', ['symbols' => implode(', ', $result['newSymbols'])]) }}
                    </p>
                @endif

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700"
                       href="{{ route('trades') }}">{{ __('import.done.go_trades') }}</a>
                    <a class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                       href="{{ route('dashboard') }}">{{ __('import.done.go_dashboard') }}</a>
                    <button class="rounded-lg px-5 py-2.5 text-sm font-bold text-indigo-600 underline transition hover:text-indigo-800 dark:text-indigo-400"
                            type="button"
                            wire:click="reset_">{{ __('import.done.import_another') }}</button>
                </div>
            </div>

            @if ($result['errors'] !== [])
                <div class="rounded-2xl border border-red-200 bg-red-50 p-6 dark:border-red-500/30 dark:bg-red-500/10">
                    <h3 class="text-sm font-bold text-red-800 dark:text-red-300">{{ __('import.done.errors_title') }}</h3>
                    <ul class="mt-3 space-y-2 text-sm text-red-700 dark:text-red-300">
                        @foreach ($result['errors'] as $error)
                            <li>
                                <span class="font-semibold">{{ __('import.done.row', ['number' => $error['row']]) }}:</span>
                                {{ implode(' ', $error['errors']) }}
                            </li>
                        @endforeach
                    </ul>

                    @if ($result['failed'] > count($result['errors']))
                        <p class="mt-3 text-xs text-red-700 dark:text-red-400">
                            {{ __('import.done.more_errors', ['count' => $result['failed'] - count($result['errors'])]) }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
