{{--
    Dos caras y ninguna se ve cuando no hace falta: la invitación a crear datos de
    ejemplo (solo si el usuario no tiene operaciones propias) y el aviso permanente
    de que lo que está mirando es inventado.
--}}
<div>
    @if ($hasSample)
        <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 dark:border-amber-500/40 dark:bg-amber-500/10 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-flask mt-1 text-amber-600 dark:text-amber-400"></i>
                <div>
                    <p class="text-sm font-bold text-amber-900 dark:text-amber-200">{{ __('sample.banner_title') }}</p>
                    <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300/90">{{ __('sample.banner_text') }}</p>
                </div>
            </div>

            <button class="shrink-0 rounded-lg border border-amber-400 px-4 py-2 text-sm font-bold text-amber-900 transition hover:bg-amber-100 disabled:opacity-50 dark:border-amber-500/50 dark:text-amber-200 dark:hover:bg-amber-500/20"
                    type="button"
                    wire:click="destroySample"
                    wire:loading.attr="disabled"
                    wire:target="destroySample">
                <span wire:loading.remove wire:target="destroySample">
                    <i class="fa-solid fa-trash-can mr-1"></i>{{ __('sample.delete') }}
                </span>
                <span wire:loading wire:target="destroySample">
                    <i class="fa-solid fa-circle-notch fa-spin mr-1"></i>{{ __('sample.deleting') }}
                </span>
            </button>
        </div>
    @elseif (! $hasTrades)
        <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-indigo-200 bg-indigo-50 px-5 transition-all dark:border-indigo-500/30 dark:bg-indigo-500/10 sm:flex-row sm:items-center sm:justify-between"
             x-data="tfMinimizable('sample')"
             :class="min ? 'py-2.5' : 'py-5'">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-wand-magic-sparkles text-indigo-600 dark:text-indigo-400"
                   :class="min ? 'mt-0.5' : 'mt-1'"></i>
                <div>
                    <p class="text-sm font-bold text-indigo-900 dark:text-indigo-200">{{ __('sample.cta_title') }}</p>
                    <p class="mt-0.5 max-w-2xl text-xs text-indigo-800 dark:text-indigo-300/90"
                       x-show="!min">{{ __('sample.cta_text') }}</p>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{-- Plegada se queda en un recordatorio de una línea: los dos botones
                     vuelven al desplegarla. --}}
                <button class="flex h-7 w-7 items-center justify-center rounded-lg text-indigo-400 transition hover:bg-white/60 hover:text-indigo-700 dark:hover:bg-indigo-500/20 dark:hover:text-indigo-200"
                        type="button"
                        @click="toggle()"
                        :aria-expanded="(!min).toString()"
                        :title="min ? @js(__('labels.card_expand')) : @js(__('labels.card_minimize'))"
                        :aria-label="min ? @js(__('labels.card_expand')) : @js(__('labels.card_minimize'))">
                    <i class="fa-solid fa-chevron-up text-xs transition-transform"
                       :class="min && 'rotate-180'"></i>
                </button>

                <a class="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-bold text-indigo-800 transition hover:bg-indigo-100 dark:border-indigo-500/40 dark:text-indigo-200 dark:hover:bg-indigo-500/20"
                   x-show="!min"
                   href="{{ route('trades.import') }}">
                    <i class="fa-solid fa-file-import mr-1"></i>{{ __('import.menu') }}
                </a>

                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:opacity-50"
                        type="button"
                        x-show="!min"
                        wire:click="create"
                        wire:loading.attr="disabled"
                        wire:target="create">
                    <span wire:loading.remove wire:target="create">{{ __('sample.cta_button') }}</span>
                    <span wire:loading wire:target="create">
                        <i class="fa-solid fa-circle-notch fa-spin mr-1"></i>{{ __('sample.creating') }}
                    </span>
                </button>
            </div>
        </div>
    @endif
</div>
