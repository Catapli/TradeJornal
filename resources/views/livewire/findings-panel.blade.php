{{-- De hallazgo a regla (Fase 5 · P7). --}}
<div class="space-y-4">

    {{-- ── HALLAZGOS ─────────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <h3 class="flex items-center gap-2 text-sm font-black text-gray-900 dark:text-gray-100">
                <i class="fa-solid fa-lightbulb text-amber-500"></i>{{ __('rules.findings.title') }}
            </h3>
            <p class="mt-0.5 max-w-3xl text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                {{ __('rules.findings.lead') }}
            </p>
        </div>

        @if (empty($this->findings))
            <p class="px-5 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                {{ __('rules.findings.empty', ['min' => App\Actions\Rules\DetectFindings::MIN_SAMPLE]) }}
            </p>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($this->findings as $finding)
                    <div class="px-5 py-4"
                         wire:key="finding-{{ $finding['key'] }}">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold leading-relaxed text-gray-800 dark:text-gray-100">
                                    {{ $finding['sentence'] }}
                                </p>

                                {{-- La muestra va con la frase: una conclusión sin
                                     saber de cuántas operaciones sale no es una
                                     conclusión, es una corazonada con formato. --}}
                                <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">
                                    {{ __('rules.findings.sample', ['count' => $finding['sample']]) }}
                                </p>
                            </div>

                            <div class="shrink-0">
                                @if ($finding['adopted'])
                                    <span class="flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        <i class="fa-solid fa-check"></i>{{ __('rules.findings.adopted') }}
                                    </span>
                                @else
                                    <button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-indigo-700"
                                            type="button"
                                            wire:key="adopt-{{ $finding['key'] }}"
                                            wire:click="startAdopting('{{ $finding['key'] }}')">
                                        {{ __('rules.findings.adopt') }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Diálogo de adopción, en línea --}}
                        @if ($adopting === $finding['key'])
                            <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10"
                                 wire:key="adopting-{{ $finding['key'] }}">

                                <label class="mb-1 block text-[11px] font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300"
                                       for="rule-text">{{ __('rules.adopt.text_label') }}</label>

                                <input class="w-full rounded-lg border-gray-300 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                       id="rule-text"
                                       type="text"
                                       maxlength="255"
                                       wire:model="ruleText">

                                <p class="mb-1 mt-3 text-[11px] font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                    {{ __('rules.adopt.scope') }}
                                </p>

                                <div class="flex flex-wrap items-center gap-3">
                                    <select class="rounded-lg border-gray-300 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                            wire:model.live="scope">
                                        <option value="account">{{ __('rules.adopt.scope_one') }}</option>
                                        <option value="all">{{ __('rules.adopt.scope_all') }}</option>
                                    </select>

                                    @if ($scope === 'account')
                                        <select class="rounded-lg border-gray-300 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                                wire:model="scopeAccountId">
                                            <option value="">—</option>
                                            @foreach ($this->accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>

                                <p class="mt-2 text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">
                                    {{ __('rules.adopt.scope_hint') }}
                                </p>

                                <div class="mt-3 flex items-center gap-2">
                                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-indigo-700"
                                            type="button"
                                            wire:click="adopt"
                                            wire:loading.attr="disabled">
                                        {{ __('rules.adopt.confirm') }}
                                    </button>

                                    <button class="text-xs font-semibold text-gray-500 underline transition hover:text-gray-700 dark:text-gray-400"
                                            type="button"
                                            wire:click="cancelAdopting">
                                        {{ __('rules.adopt.cancel') }}
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── MIS REGLAS ────────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <h3 class="flex items-center gap-2 text-sm font-black text-gray-900 dark:text-gray-100">
                <i class="fa-solid fa-scale-balanced text-indigo-500"></i>{{ __('rules.mine.title') }}
            </h3>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('rules.mine.lead') }}</p>
        </div>

        @if ($this->rules->isEmpty())
            <p class="px-5 py-8 text-center text-xs text-gray-500 dark:text-gray-400">{{ __('rules.mine.empty') }}</p>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($this->rules as $rule)
                    <div @class([
                        'px-5 py-4',
                        'opacity-50' => ! $rule->is_active,
                    ])
                         wire:key="rule-{{ $rule->id }}">

                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $rule->text }}</p>

                                <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px]">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $rule->isGlobal() ? __('rules.mine.global') : $rule->account?->name }}
                                    </span>

                                    <span @class([
                                        'rounded-full px-2 py-0.5 font-semibold',
                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $rule->isEnforceable() && ! $rule->isGlobal(),
                                        'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => ! ($rule->isEnforceable() && ! $rule->isGlobal()),
                                    ])>
                                        {{ $rule->isEnforceable() && ! $rule->isGlobal() ? __('rules.mine.enforced') : __('rules.mine.checklist_only') }}
                                    </span>
                                </div>

                                {{-- La procedencia: por qué esta regla existe. --}}
                                <p class="mt-1.5 text-[11px] leading-relaxed text-gray-400 dark:text-gray-500">
                                    {{ __('rules.mine.origin', [
                                        'summary' => $rule->source_summary,
                                        'sample' => trans_choice('rules.mine.origin_sample', $rule->source_sample, ['count' => $rule->source_sample]),
                                    ]) }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <button class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                        type="button"
                                        wire:key="toggle-{{ $rule->id }}-{{ $rule->is_active ? 'on' : 'off' }}"
                                        wire:click="toggle({{ $rule->id }})">
                                    {{ $rule->is_active ? __('rules.mine.deactivate') : __('rules.mine.activate') }}
                                </button>

                                <button class="rounded-lg px-2 py-1.5 text-xs font-bold text-rose-500 transition hover:bg-rose-50 dark:hover:bg-rose-500/10"
                                        type="button"
                                        title="{{ __('rules.mine.delete') }}"
                                        wire:key="remove-{{ $rule->id }}"
                                        wire:click="remove({{ $rule->id }})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
