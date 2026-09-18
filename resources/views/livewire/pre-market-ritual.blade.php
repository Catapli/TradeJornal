{{-- Ritual pre-mercado (R5): tarjeta en el panel + modal de tres pasos. --}}
<div>
    @if ($this->shouldShow())
        <div class="mb-5 overflow-hidden rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white dark:border-indigo-500/30 dark:from-indigo-500/10 dark:to-gray-800"
             x-data="tfMinimizable('ritual')">
            <div class="flex flex-wrap items-center justify-between gap-4 px-5 transition-all"
                 :class="min ? 'py-2.5' : 'py-4'">

                <div class="flex items-start gap-3">
                    <span class="flex shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white transition-all"
                          :class="min ? 'mt-0 h-7 w-7 text-xs' : 'mt-0.5 h-9 w-9'">
                        <i class="fa-solid fa-mug-hot"></i>
                    </span>
                    <div>
                        <h2 class="font-black text-gray-900 transition-all dark:text-gray-100"
                            :class="min ? 'text-sm leading-7' : 'text-base'">{{ __('ritual.card.title') }}</h2>
                        <p class="mt-0.5 max-w-xl text-xs leading-relaxed text-gray-600 dark:text-gray-400"
                           x-show="!min">{{ __('ritual.card.lead') }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    {{-- El botón de empezar se queda aunque esté plegada: son 60
                         segundos y esconderlo convertiría el plegado en un cierre. --}}
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-indigo-700"
                            type="button"
                            wire:click="start">{{ __('ritual.card.cta') }}</button>

                    <button class="text-xs font-semibold text-gray-400 underline transition hover:text-gray-600 dark:hover:text-gray-200"
                            type="button"
                            x-show="!min"
                            wire:click="postpone">{{ __('ritual.card.skip') }}</button>

                    <button class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-white/60 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            type="button"
                            @click="toggle()"
                            :aria-expanded="(!min).toString()"
                            :title="min ? @js(__('labels.card_expand')) : @js(__('labels.card_minimize'))"
                            :aria-label="min ? @js(__('labels.card_expand')) : @js(__('labels.card_minimize'))">
                        <i class="fa-solid fa-chevron-up text-xs transition-transform"
                           :class="min && 'rotate-180'"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL. z-[60] para quedar por encima del navbar fijo (z-40). --}}
    @if ($open)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 px-4 py-8 backdrop-blur-sm">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800">

                {{-- Cabecera con progreso --}}
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-black text-gray-900 dark:text-gray-100">{{ __('ritual.modal.title') }}</h3>
                        <button class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                                type="button"
                                wire:click="close"
                                aria-label="{{ __('ritual.modal.close') }}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="mt-3 flex gap-1.5">
                        @for ($i = 1; $i <= 3; $i++)
                            <span @class([
                                'h-1.5 flex-1 rounded-full transition-all',
                                'bg-indigo-600' => $i <= $step,
                                'bg-gray-200 dark:bg-gray-700' => $i > $step,
                            ])></span>
                        @endfor
                    </div>
                </div>

                <div class="px-6 py-6"
                     wire:key="ritual-step-{{ $step }}">

                    {{-- PASO 1: cómo llegas --}}
                    @if ($step === 1)
                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('ritual.step1.title') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ritual.step1.hint') }}</p>

                        @error('mood')
                            <p class="mt-3 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="mt-4 grid grid-cols-4 gap-2">
                            @foreach (['calm' => '😌', 'anxious' => '😰', 'confident' => '😎', 'tired' => '😴'] as $key => $emoji)
                                <button @class([
                                    'flex flex-col items-center gap-1 rounded-xl border py-3 transition',
                                    'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/20' => $mood === $key,
                                    'border-gray-200 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700' => $mood !== $key,
                                ])
                                        type="button"
                                        wire:click="$set('mood', '{{ $key }}')">
                                    <span class="text-2xl">{{ $emoji }}</span>
                                    <span class="text-[11px] font-semibold text-gray-600 dark:text-gray-300">{{ __('ritual.moods.' . $key) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- PASO 2: reglas de hoy --}}
                    @if ($step === 2)
                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('ritual.step2.title') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ritual.step2.hint') }}</p>

                        <div class="mt-4 space-y-2">
                            @foreach ($objectives as $index => $objective)
                                <div class="flex items-center gap-2"
                                     wire:key="objective-{{ $index }}">
                                    <input class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                           type="text"
                                           maxlength="120"
                                           wire:model.blur="objectives.{{ $index }}.text"
                                           placeholder="{{ __('ritual.step2.placeholder') }}">

                                    <button class="shrink-0 px-2 text-gray-400 transition hover:text-red-500"
                                            type="button"
                                            wire:click="removeObjective({{ $index }})"
                                            aria-label="{{ __('ritual.step2.remove') }}">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        @if (count($objectives) < 5)
                            <button class="mt-3 text-xs font-bold text-indigo-600 underline transition hover:text-indigo-800 dark:text-indigo-400"
                                    type="button"
                                    wire:click="addObjective">{{ __('ritual.step2.add') }}</button>
                        @endif
                    @endif

                    {{-- PASO 3: qué vigilas hoy --}}
                    @if ($step === 3)
                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('ritual.step3.title') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ritual.step3.hint') }}</p>

                        <textarea class="mt-4 w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                  rows="4"
                                  maxlength="1000"
                                  wire:model.blur="notes"
                                  placeholder="{{ __('ritual.step3.placeholder') }}"></textarea>
                    @endif
                </div>

                {{-- Pie de navegación --}}
                <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                    <button @class([
                        'text-xs font-semibold transition',
                        'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100' => $step > 1,
                        'invisible' => $step === 1,
                    ])
                            type="button"
                            wire:key="ritual-back"
                            wire:click="back">{{ __('ritual.modal.back') }}</button>

                    @if ($step < 3)
                        <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700"
                                type="button"
                                wire:key="ritual-next"
                                wire:click="next">{{ __('ritual.modal.next') }}</button>
                    @else
                        <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                                type="button"
                                wire:key="ritual-finish"
                                wire:click="finish"
                                wire:loading.attr="disabled">{{ __('ritual.modal.finish') }}</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
