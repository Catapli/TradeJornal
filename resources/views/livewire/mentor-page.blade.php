{{--
    Mentor con memoria (Fase 6 · P5).

    Tres bloques en este orden, y el orden importa: primero lo que se repite
    (el perfil), después la única cosa a cambiar este mes (el objetivo) y al final
    el historial de meses cerrados, que es lo que le da peso a los dos anteriores.
--}}
<div class="mx-auto max-w-5xl px-4 pb-16 pt-16 sm:px-6 lg:px-8">

    @php
        $perfil = $this->profile;
        $objetivo = $this->goal;
        $propuesta = $this->proposal;
        $avance = $this->progress;
    @endphp

    {{-- CABECERA --}}
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ __('mentor.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('mentor.lead', ['months' => $perfil['months']]) }}
        </p>
    </div>

    @if (! $perfil['has_enough_data'])
        {{-- Sin muestra no hay perfil, y se dice cuánto falta y por qué. --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="text-sm font-bold text-amber-900 dark:text-amber-200">
                <i class="fa-solid fa-hourglass-half mr-1"></i>{{ __('mentor.not_enough_title') }}
            </p>
            <p class="mt-2 text-sm text-amber-800 dark:text-amber-300">
                {{ __('mentor.not_enough_text', [
                    'marks' => $perfil['marks'],
                    'min' => $perfil['min_marks'],
                    'missing' => $perfil['missing_marks'],
                ]) }}
            </p>
            <p class="mt-3 text-xs leading-relaxed text-amber-700/90 dark:text-amber-300/80">
                {{ __('mentor.not_enough_why') }}
            </p>

            {{-- Al Laboratorio, no a /trades: etiquetar operación a operación desde
                 la lista no lo hace nadie. La cola de repaso las pone en fila con
                 los errores a un clic. --}}
            <a class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-amber-700"
               href="{{ route('reports') }}#repaso-errores">
                <i class="fa-solid fa-list-check text-xs"></i>
                {{ __('mentor.not_enough_cta') }}
            </a>
        </div>
    @else

        {{-- ────────────────────────────────────────────────────────────
             BLOQUE 1 · EL PERFIL
        ──────────────────────────────────────────────────────────── --}}
        <section class="mb-8">
            <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-black text-gray-900 dark:text-gray-100">{{ __('mentor.profile_title') }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('mentor.coverage', [
                        'reviewed' => $perfil['reviewed'],
                        'trades' => $perfil['trades'],
                        'coverage' => number_format($perfil['coverage'], 1, ',', '.'),
                    ]) }}

                    {{-- Con una sola cuenta sobra decirlo; con varias, el importe
                         en euros mezcla tamaños y hay que avisarlo. --}}
                    @if (($perfil['accounts'] ?? 1) > 1)
                        · {{ __('mentor.accounts_mixed', ['accounts' => $perfil['accounts']]) }}
                    @endif
                </p>
            </div>

            <div class="space-y-2">
                @foreach (array_slice($perfil['mistakes'], 0, 5) as $error)
                    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
                         wire:key="error-{{ $error['id'] }}">

                        <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                              style="background-color: {{ $error['color'] }}"></span>

                        <div class="min-w-[10rem] flex-1">
                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $error['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('mentor.mistake_count', ['count' => $error['count']]) }}
                                ·
                                {{ __('mentor.mistake_cost', ['amount' => number_format($error['cost'], 2, ',', '.') . ' €']) }}
                            </p>
                        </div>

                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                            {{ __('mentor.this_month', ['count' => $error['this_month']]) }}
                        </p>

                        <span @class([
                            'rounded-md px-2 py-1 text-[11px] font-bold',
                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => $error['trend'] === 'down',
                            'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300' => $error['trend'] === 'up',
                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $error['trend'] === 'flat',
                        ])>
                            {{ __('mentor.trend_' . $error['trend']) }}
                        </span>
                    </div>
                @endforeach
            </div>

            <p class="mt-2 text-[11px] leading-relaxed text-gray-400 dark:text-gray-500">
                {{ __('mentor.trend_hint') }} {{ __('mentor.coverage_hint') }}
            </p>
        </section>

        {{-- ────────────────────────────────────────────────────────────
             BLOQUE 2 · EL OBJETIVO DEL MES
        ──────────────────────────────────────────────────────────── --}}
        <section class="mb-8">
            <div class="mb-3">
                <h2 class="text-lg font-black text-gray-900 dark:text-gray-100">{{ __('mentor.goal_title') }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('mentor.goal_lead') }}</p>
            </div>

            @if ($objetivo)
                {{-- Objetivo ya fijado: solo seguimiento, no se edita. --}}
                <div @class([
                    'rounded-2xl border p-5',
                    'border-red-200 bg-red-50 dark:border-red-500/30 dark:bg-red-500/10' => $avance['blown'],
                    'border-indigo-200 bg-indigo-50 dark:border-indigo-500/30 dark:bg-indigo-500/10' => ! $avance['blown'],
                ])>
                    <p class="text-base font-black text-gray-900 dark:text-gray-100">{{ $objetivo->statement }}</p>

                    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-white/70 dark:bg-gray-900/40">
                        <div @class([
                            'h-full rounded-full',
                            'bg-red-500' => $avance['blown'],
                            'bg-indigo-500' => ! $avance['blown'],
                        ])
                             style="width: {{ $avance['progress'] }}%"></div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <p @class([
                            'text-sm font-bold',
                            'text-red-700 dark:text-red-300' => $avance['blown'],
                            'text-indigo-800 dark:text-indigo-200' => ! $avance['blown'],
                        ])>
                            @if ($avance['blown'])
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>
                                {{ __('mentor.progress_blown', ['count' => $avance['so_far'], 'target' => $avance['target']]) }}
                            @else
                                <i class="fa-solid fa-circle-check mr-1"></i>
                                {{ __('mentor.progress_so_far', ['count' => $avance['so_far'], 'target' => $avance['target']]) }}
                            @endif
                        </p>

                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            @if ($avance['days_left'] > 0)
                                {{ __('mentor.progress_days', ['days' => $avance['days_left']]) }}
                            @else
                                {{ __('mentor.progress_last_day') }}
                            @endif
                        </p>
                    </div>

                    @if ($avance['blown'])
                        <p class="mt-2 text-xs leading-relaxed text-red-700/80 dark:text-red-300/80">
                            {{ __('mentor.progress_blown_hint') }}
                        </p>
                    @endif

                    <p class="mt-3 border-t border-white/60 pt-3 text-[11px] text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        {{ __('mentor.history_sample', ['sample' => $objetivo->sample]) }}
                    </p>
                </div>
            @elseif ($propuesta)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-base font-black text-gray-900 dark:text-gray-100">
                        {{ __('mentor.goal.statement', [
                            'name' => $propuesta['mistake_name'],
                            'baseline' => $propuesta['baseline'],
                            'target' => $propuesta['target'],
                        ]) }}
                    </p>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('mentor.goal.propose_intro', [
                            'baseline' => $propuesta['baseline'],
                            'sample' => $propuesta['sample'],
                        ]) }}
                    </p>

                    @if ($confirming)
                        {{-- Confirmación explícita: el objetivo no se puede deshacer. --}}
                        <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10"
                             wire:key="confirmar-objetivo">
                            <p class="text-sm font-bold text-indigo-900 dark:text-indigo-200">{{ __('mentor.goal.confirm_title') }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-indigo-800/90 dark:text-indigo-300/90">
                                {{ __('mentor.goal.confirm_text') }}
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-700"
                                        type="button"
                                        wire:key="objetivo-si"
                                        wire:click="setGoal">
                                    {{ __('mentor.goal.confirm_yes') }}
                                </button>

                                <button class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                        type="button"
                                        wire:key="objetivo-no"
                                        wire:click="$set('confirming', false)">
                                    {{ __('mentor.goal.confirm_no') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <button class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700"
                                type="button"
                                wire:key="objetivo-fijar"
                                wire:click="$set('confirming', true)">
                            <i class="fa-solid fa-bullseye text-xs"></i>
                            {{ __('mentor.goal.set') }}
                        </button>
                    @endif
                </div>
            @else
                <x-empty-state icon="fa-bullseye"
                               :title="__('mentor.goal.none_title')"
                               :text="__('mentor.goal.none_text', ['min' => \App\Actions\Mentor\ProposeMonthlyGoal::MIN_REFERENCE_TRADES])" />
            @endif
        </section>

        {{-- ────────────────────────────────────────────────────────────
             BLOQUE 3 · LOS MESES CERRADOS
        ──────────────────────────────────────────────────────────── --}}
        <section>
            <h2 class="mb-3 text-lg font-black text-gray-900 dark:text-gray-100">{{ __('mentor.history_title') }}</h2>

            @if ($this->history->isEmpty())
                <p class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                    {{ __('mentor.history_empty') }}
                </p>
            @else
                <div class="space-y-2">
                    @foreach ($this->history as $pasado)
                        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
                             wire:key="pasado-{{ $pasado->id }}">

                            <span class="min-w-[6rem] text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ \Carbon\CarbonImmutable::parse($pasado->month)->translatedFormat('F Y') }}
                            </span>

                            <p class="min-w-[12rem] flex-1 text-sm text-gray-900 dark:text-gray-100">{{ $pasado->statement }}</p>

                            <span class="text-xs font-semibold tabular-nums text-gray-600 dark:text-gray-300">
                                {{ __('mentor.history_result', ['result' => $pasado->result, 'target' => $pasado->target]) }}
                            </span>

                            <span @class([
                                'rounded-md px-2 py-1 text-[11px] font-bold',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => $pasado->status === \App\Models\ImprovementGoal::STATUS_ACHIEVED,
                                'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300' => $pasado->status === \App\Models\ImprovementGoal::STATUS_MISSED,
                            ])>
                                {{ $pasado->status === \App\Models\ImprovementGoal::STATUS_ACHIEVED ? __('mentor.history_achieved') : __('mentor.history_missed') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
