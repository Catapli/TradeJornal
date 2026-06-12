{{-- ══ MODAL DETALLE TRADE ══════════════════════════════════ --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
     x-show="showTradeDetail"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click.self="closeTradeDetail()"
     @keydown.escape.window="showTradeDetail && (zoomImage ? zoomImage = false : closeTradeDetail())"
     @keydown.arrow-left.window="showTradeDetail && !zoomImage && detailNav('prev')"
     @keydown.arrow-right.window="showTradeDetail && !zoomImage && detailNav('next')"
     style="display:none">

    <div class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
         x-show="showTradeDetail"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-cloak>

        <template x-if="detailTrade">
            <div class="flex min-h-0 flex-1 flex-col">

                {{-- Header con color según resultado --}}
                <div class="flex shrink-0 items-center justify-between px-6 py-4"
                     :class="{
                         'bg-emerald-500': detailTrade.pnl_r > 0,
                         'bg-red-500': detailTrade.pnl_r < 0,
                         'bg-gray-400': !detailTrade.pnl_r || detailTrade.pnl_r === 0
                     }">
                    <div class="flex items-center gap-3">
                        {{-- Badge dirección --}}
                        <span class="rounded-lg bg-white/20 px-2.5 py-1 text-xs font-bold uppercase text-white"
                              x-text="detailTrade.direction"></span>
                        <div>
                            <p class="text-base font-bold text-white"
                               x-text="detailTrade.date"></p>
                            <p class="text-xs text-white/70"
                               x-text="detailTrade.session ? detailTrade.session.replace('_', ' ').toUpperCase() : '{{ __('labels.no_session') }}'"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- PnL R grande --}}
                        <div class="mr-1 text-right">
                            <p class="text-xs text-white/70">{{ __('labels.result') }}</p>
                            <p class="text-2xl font-black tabular-nums leading-none text-white"
                               x-text="detailTrade.pnl_r !== null ? (detailTrade.pnl_r > 0 ? '+' : '') + detailTrade.pnl_r + 'R' : '—'"></p>
                        </div>

                        {{-- Navegación ← → entre trades --}}
                        <div class="flex items-center gap-1 border-l border-white/20 pl-3">
                            <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 text-white transition-colors hover:bg-white/30 disabled:cursor-not-allowed disabled:opacity-30"
                                    type="button"
                                    title="{{ __('labels.previous') }} (←)"
                                    :disabled="!hasPrevTrade"
                                    @click="detailNav('prev')">
                                <svg class="h-4 w-4"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     stroke-width="2.5">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                            </button>
                            <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 text-white transition-colors hover:bg-white/30 disabled:cursor-not-allowed disabled:opacity-30"
                                    type="button"
                                    title="{{ __('labels.next') }} (→)"
                                    :disabled="!hasNextTrade"
                                    @click="detailNav('next')">
                                <svg class="h-4 w-4"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     stroke-width="2.5">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </button>
                        </div>

                        <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 text-white transition-colors hover:bg-white/30"
                                @click="closeTradeDetail()">
                            <svg class="h-4 w-4"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor"
                                 stroke-width="2.5">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="grid min-h-0 flex-1 grid-cols-1 lg:grid-cols-7">

                    {{-- Columna izquierda: datos --}}
                    <div class="order-2 col-span-1 space-y-4 overflow-y-auto border-t border-gray-100 p-5 lg:order-1 lg:col-span-2 lg:border-r lg:border-t-0">

                        {{-- Precios --}}
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('labels.prices') }}</p>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ __('labels.entry') }}</span>
                                    <span class="text-sm font-semibold tabular-nums text-gray-900"
                                          x-text="detailTrade.entry_price"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ __('labels.exit_label') }}</span>
                                    <span class="text-sm font-semibold tabular-nums text-gray-900"
                                          x-text="detailTrade.exit_price"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ __('labels.stop_loss') }}</span>
                                    <span class="text-sm font-medium tabular-nums text-red-400"
                                          x-text="detailTrade.stop_loss ?? '—'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Calidad --}}
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('labels.setup') }}</p>
                            <div class="space-y-2">
                                {{-- Stars --}}
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ __('labels.rating') }}</span>
                                    <div class="flex gap-0.5">
                                        <template x-for="i in 5"
                                                  :key="i">
                                            <span class="text-sm"
                                                  :class="i <= detailTrade.setup_rating ? 'text-amber-400' : 'text-gray-200'">★</span>
                                        </template>
                                    </div>
                                </div>
                                {{-- Siguió reglas --}}
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ __('labels.followed_rules_label') }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                          :class="detailTrade.followed_rules ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'"
                                          x-text="detailTrade.followed_rules ? '{{ __('labels.yes') }}' : '{{ __('labels.no') }}'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Confluencias --}}
                        <div x-show="detailTrade.confluences && detailTrade.confluences.length > 0">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('labels.confluences') }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="tag in detailTrade.confluences"
                                          :key="tag">
                                    <span class="rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs text-blue-700"
                                          x-text="tag"></span>
                                </template>
                            </div>
                        </div>

                        {{-- Notas --}}
                        <div x-show="detailTrade.notes">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('labels.notes') }}</p>
                            <p class="whitespace-pre-line rounded-xl border border-amber-100 bg-amber-50/50 p-3 text-sm leading-relaxed text-gray-600"
                               x-text="detailTrade.notes"></p>
                        </div>

                        {{-- Acciones --}}
                        <div class="flex gap-2 border-t border-gray-100 pt-3">
                            <button class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-200 py-2 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50"
                                    @click="closeTradeDetail(); openTradePanel(detailTrade.id)">
                                <svg class="h-3.5 w-3.5"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     stroke-width="2">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                                {{ __('labels.edit') }}
                            </button>
                            <button class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-red-200 py-2 text-xs font-semibold text-red-500 transition-colors hover:bg-red-50"
                                    @click="closeTradeDetail(); confirmDeleteTrade(detailTrade.id)">
                                <svg class="h-3.5 w-3.5"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     stroke-width="2">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916" />
                                </svg>
                                {{ __('labels.delete') }}
                            </button>
                        </div>
                    </div>

                    {{-- Columna derecha: screenshot en grande --}}
                    <div class="relative order-1 col-span-1 flex min-h-[300px] items-center justify-center bg-gray-900/95 lg:order-2 lg:col-span-5 lg:min-h-[60vh]">
                        <template x-if="detailTrade.screenshot">
                            <div class="group relative flex h-full w-full items-center justify-center p-2">
                                <img class="max-h-[38vh] w-auto max-w-full cursor-zoom-in rounded-lg object-contain lg:max-h-[74vh]"
                                     :src="detailTrade.screenshot"
                                     :alt="'Chart del trade ' + detailTrade.date"
                                     @click="zoomImage = true">
                                {{-- Hint de zoom --}}
                                <span class="pointer-events-none absolute bottom-3 right-3 flex items-center gap-1.5 rounded-lg bg-black/60 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                                    <svg class="h-3.5 w-3.5"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         stroke-width="2">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" />
                                    </svg>
                                    {{ __('labels.click_to_zoom') }}
                                </span>
                            </div>
                        </template>
                        <template x-if="!detailTrade.screenshot">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="h-12 w-12 text-gray-600"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     stroke-width="1">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M13.5 12h.008M3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">{{ __('labels.no_screenshot') }}</p>
                                <p class="text-xs text-gray-500">{{ __('labels.edit_trade_to_add_screenshot') }}</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- ══ LIGHTBOX ZOOM PANTALLA COMPLETA ══════════════════════ --}}
<div class="fixed inset-0 z-[70] flex cursor-zoom-out items-center justify-center bg-black/90 p-4"
     x-show="zoomImage && detailTrade && detailTrade.screenshot"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="zoomImage = false"
     style="display:none">
    <img class="max-h-[95vh] max-w-[95vw] rounded-lg object-contain shadow-2xl"
         :src="detailTrade?.screenshot"
         :alt="detailTrade ? 'Chart del trade ' + detailTrade.date : ''">
    <button class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20"
            @click.stop="zoomImage = false">
        <svg class="h-5 w-5"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2.5">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>
