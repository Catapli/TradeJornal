@php
    $pendientes = app(\App\Actions\Mistakes\CountPendingReview::class)->execute((int) auth()->id());
@endphp

{{--
    Puerta de entrada al repaso de errores.

    Antes esto era una lista con chips para etiquetar dentro del Laboratorio, y
    convivía con el selector del detalle de la operación: dos formas distintas de
    hacer lo mismo, ninguna cómoda para vaciar setenta pendientes. El etiquetado
    se fue entero a `/repaso`, y aquí queda lo único que aporta estar en el
    Laboratorio: **cuántas faltan y por qué importa**, pegado al coste que sale
    justo encima.

    Se pinta siempre: la cola vacía es una buena noticia y hay que darla.
--}}
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800']) }}
     id="repaso-errores">

    <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-4">
        <div class="flex min-w-0 items-start gap-3">
            <i class="fa-solid fa-clipboard-check mt-1 shrink-0 {{ $pendientes > 0 ? 'text-rose-500' : 'text-emerald-500' }}"></i>

            <div class="min-w-0">
                <h3 class="text-sm font-black text-gray-900 dark:text-gray-100">
                    {{ __('mistake_cost.review.title') }}
                </h3>

                @if ($pendientes > 0)
                    <p class="mt-0.5 max-w-2xl text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                        {{ __('review.queue_lead') }}
                    </p>
                @else
                    <p class="mt-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <i class="fa-solid fa-circle-check mr-1"></i>{{ __('mistake_cost.review.empty') }}
                    </p>
                @endif
            </div>
        </div>

        @if ($pendientes > 0)
            <div class="flex shrink-0 items-center gap-3">
                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                    {{ trans_choice('mistake_cost.review.pending', $pendientes, ['count' => $pendientes]) }}
                </span>

                <a class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700"
                   href="{{ route('review') }}"
                   wire:navigate>
                    <i class="fa-solid fa-play text-[10px]"></i>
                    {{ __('review.cta_pending', ['count' => $pendientes]) }}
                </a>
            </div>
        @endif
    </div>
</div>
