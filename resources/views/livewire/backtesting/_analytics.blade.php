@if (!$metricsLoaded)
    <div class="flex items-center justify-center py-20">
        <div class="flex items-center gap-3 text-sm text-gray-400">
            <svg class="h-5 w-5 animate-spin"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24">
                <circle class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"></circle>
                <path class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ __('labels.calculating_metrics') }}
        </div>
    </div>
@elseif($metrics['total_trades'] === 0)
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
        <p class="text-sm font-medium text-gray-600">{{ __('labels.no_trades_yet') }}</p>
        <p class="mt-1 text-xs text-gray-400">{{ __('labels.register_trade_to_see_metrics') }}</p>
    </div>
@else
    @php
        $m = $metrics;
        $unit = $m['r_mode'] ? 'R' : $selectedStrategy->currency ?? 'USD';
        $dd = $m['max_drawdown'];
        $eff = $m['trader_efficiency'];

        $sqnLabel = match (true) {
            $m['sqn'] >= 5 => [__('labels.label_excellent'), 'emerald'],
            $m['sqn'] >= 3 => [__('labels.label_good'), 'emerald'],
            $m['sqn'] >= 2 => [__('labels.label_acceptable'), 'amber'],
            $m['sqn'] >= 1 => [__('labels.label_weak'), 'orange'],
            default => [__('labels.label_bad'), 'red'],
        };

        $effLabel = match (true) {
            $eff['score'] >= 80 => [__('labels.label_excellent'), 'emerald'],
            $eff['score'] >= 60 => [__('labels.label_good'), 'blue'],
            $eff['score'] >= 40 => [__('labels.label_regular'), 'amber'],
            default => [__('labels.label_improvable'), 'red'],
        };
    @endphp

    {{-- ═══ FILA 1: Estadísticas ══════════════════════════════════ --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="mb-1 text-xs text-gray-400">{{ __('labels.trades') }}</p>
            <p class="text-2xl font-bold tabular-nums text-gray-900">{{ $m['total_trades'] }}</p>
            <p class="mt-1 text-xs tabular-nums text-gray-400">
                <span class="text-emerald-500">{{ $m['max_consecutive_wins'] }}W</span>
                · <span class="text-red-400">{{ $m['max_consecutive_losses'] }}L</span> racha máx.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="mb-1 text-xs text-gray-400">{{ __('labels.win_rate') }}</p>
            <p class="{{ $m['win_rate'] >= 50 ? 'text-emerald-600' : 'text-red-500' }} text-2xl font-bold tabular-nums">
                {{ $m['win_rate'] }}%
            </p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100">
                <div class="{{ $m['win_rate'] >= 50 ? 'bg-emerald-500' : 'bg-red-400' }} h-full rounded-full"
                     style="width: {{ $m['win_rate'] }}%"></div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="mb-1 text-xs text-gray-400">{{ __('labels.profit_factor') }}</p>
            @php
                $pf = $m['profit_factor'];
                $pfNumeric = is_numeric($pf) ? (float) $pf : null;
                $pfColor = $pf === '∞' || ($pfNumeric !== null && $pfNumeric >= 1.5) ? 'text-emerald-600' : ($pfNumeric !== null && $pfNumeric >= 1 ? 'text-amber-500' : 'text-red-500');
                $pfLabel = $pf === '∞' || ($pfNumeric !== null && $pfNumeric >= 2) ? __('labels.label_excellent') : ($pfNumeric !== null && $pfNumeric >= 1.5 ? __('labels.label_good') : ($pfNumeric !== null && $pfNumeric >= 1 ? __('labels.label_acceptable') : __('labels.label_negative')));
            @endphp
            <p class="{{ $pfColor }} text-2xl font-bold tabular-nums">{{ $pf }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $pfLabel }}</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="mb-1 text-xs text-gray-400">{{ __('labels.avg_r') }}</p>
            <p class="{{ $m['avg_r'] >= 0 ? 'text-emerald-600' : 'text-red-500' }} text-2xl font-bold tabular-nums">
                {{ $m['avg_r'] > 0 ? '+' : '' }}{{ $m['avg_r'] }}R
            </p>
            <p class="mt-1 text-xs text-gray-400">Expectancy: {{ $m['expectancy'] > 0 ? '+' : '' }}{{ $m['expectancy'] }}R</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="mb-1 text-xs text-gray-400">{{ __('labels.avg_profit_trade') }}</p>
            <p class="text-xl font-bold tabular-nums text-emerald-600">
                {{ $m['avg_win'] > 0 ? '+' : '' }}{{ $m['avg_win'] }}{{ $unit }}
            </p>
            <p class="mt-1 text-xs text-gray-400">Mayor: <span class="font-medium text-emerald-500">+{{ $m['biggest_win'] }}{{ $unit }}</span></p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="mb-1 text-xs text-gray-400">{{ __('labels.avg_losser_trade') }}</p>
            <p class="text-xl font-bold tabular-nums text-red-500">{{ $m['avg_loss'] }}{{ $unit }}</p>
            <p class="mt-1 text-xs text-gray-400">Mayor: <span class="font-medium text-red-400">{{ $m['biggest_loss'] }}{{ $unit }}</span></p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="mb-1 text-xs text-gray-400">{{ __('labels.max_drawdown') }}</p>
            <p class="text-xl font-bold tabular-nums text-red-500">{{ $dd['percent'] }}%</p>
            <p class="mt-1 text-xs tabular-nums text-gray-400">{{ $dd['amount'] }}{{ $unit }}</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="mb-1 text-xs text-gray-400">
                {{ $m['r_mode'] ? 'SQN' : 'ARR' }}
            </p>
            @if ($m['r_mode'])
                <div class="flex items-baseline gap-2">
                    <p class="text-{{ $sqnLabel[1] }}-600 text-xl font-bold tabular-nums">{{ $m['sqn'] }}</p>
                    <span class="bg-{{ $sqnLabel[1] }}-50 text-{{ $sqnLabel[1] }}-600 rounded-full px-1.5 py-0.5 text-xs font-medium">{{ $sqnLabel[0] }}</span>
                </div>
            @else
                <p class="{{ $m['arr'] >= 0 ? 'text-emerald-600' : 'text-red-500' }} text-xl font-bold tabular-nums">
                    {{ $m['arr'] > 0 ? '+' : '' }}{{ $m['arr'] }}%
                </p>
                <p class="mt-1 text-xs text-gray-400">{{ __('labels.annualized_return') }}</p>
            @endif
        </div>

    </div>

    {{-- ═══ FILA 2: Eficiencia + Curva de capital ══════════════════ --}}
    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- Eficiencia del trader --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="mb-4 text-sm font-semibold text-gray-900">{{ __('labels.efficiency_of_trader') }}</h3>

            {{-- Score circular --}}
            <div class="mb-4 flex items-center gap-4">
                <div class="relative h-16 w-16 shrink-0">
                    <svg class="h-16 w-16 -rotate-90"
                         viewBox="0 0 64 64">
                        <circle cx="32"
                                cy="32"
                                r="26"
                                fill="none"
                                stroke="#f1f5f9"
                                stroke-width="6" />
                        <circle cx="32"
                                cy="32"
                                r="26"
                                fill="none"
                                stroke="{{ $eff['score'] >= 60 ? '#10b981' : ($eff['score'] >= 40 ? '#f59e0b' : '#ef4444') }}"
                                stroke-width="6"
                                stroke-dasharray="{{ round(($eff['score'] / 100) * 163.4, 1) }} 163.4"
                                stroke-linecap="round" />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-900">
                        {{ $eff['score'] }}
                    </span>
                </div>
                <div>
                    <p class="text-{{ $effLabel[1] }}-600 text-base font-semibold">{{ $effLabel[0] }}</p>
                    <p class="text-xs text-gray-400">{{ __('labels.score_over_100') }}</p>
                </div>
            </div>

            <div class="space-y-2.5">
                <div>
                    <div class="mb-1 flex justify-between text-xs">
                        <span class="text-gray-500">{{ __('labels.discipline_rules') }}</span>
                        <span class="font-medium text-gray-700">{{ $eff['rules_followed_pct'] }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-blue-500"
                             style="width: {{ $eff['rules_followed_pct'] }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex justify-between text-xs">
                        <span class="text-gray-500">{{ __('labels.quality_setups') }}</span>
                        <span class="font-medium text-gray-700">{{ $eff['high_quality_pct'] }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-amber-400"
                             style="width: {{ $eff['high_quality_pct'] }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex justify-between text-xs">
                        <span class="text-gray-500">Win Rate</span>
                        <span class="font-medium text-gray-700">{{ $m['win_rate'] }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-emerald-500"
                             style="width: {{ $m['win_rate'] }}%"></div>
                    </div>
                </div>
                <div class="flex justify-between border-t border-gray-100 pt-1 text-xs">
                    <span class="text-gray-400">{{ __('labels.avg_rr_real') }}</span>
                    <span class="font-semibold text-gray-700">{{ $eff['avg_rr'] }}R</span>
                </div>
            </div>
        </div>

        {{-- Curva de capital --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 sm:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('labels.equity_curve_title') }}</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">
                    {{ $m['r_mode'] ? 'Expresado en R' : $selectedStrategy->currency }}
                </span>
            </div>
            <div id="chart-equity"
                 style="min-height:180px"></div>
        </div>

    </div>

    {{-- ═══ FILA 3: Calendario + Detalle del día ══════════════════ --}}
    <div class="mb-4 overflow-hidden rounded-xl border border-gray-200 bg-white"
         x-data="{
             selectedDate: null,
             selectedDayData: null,
             calendarData: {{ Js::from($m['calendar_data']) }},
             trades: {{ Js::from($m['trades_list']) }},
         
             selectDay(dateKey, dayData) {
                 if (!dayData) return
                 this.selectedDate = dateKey
                 this.selectedDayData = dayData
             },
         
             get selectedTrades() {
                 if (!this.selectedDate) return []
                 return this.trades.filter(t => t.trade_date === this.selectedDate)
             },
         
             formatDate(dateStr) {
                 if (!dateStr) return ''
                 const [y, m, d] = dateStr.split('-')
                 return d + '/' + m + '/' + y
             }
         }">

        <div class="grid grid-cols-12 divide-x divide-gray-100"
             style="min-height: 340px">

            {{-- ── Columna izquierda: Calendario con navegación ── --}}
            <div class="col-span-6 p-5"
                 x-data="{
                     currentMonth: {{ now()->month }},
                     currentYear: {{ now()->year }},
                 
                     get monthLabel() {
                         const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
                         return months[this.currentMonth - 1] + ' ' + this.currentYear
                     },
                 
                     prevMonth() {
                         if (this.currentMonth === 1) {
                             this.currentMonth = 12;
                             this.currentYear--
                         } else this.currentMonth--
                     },
                 
                     nextMonth() {
                         if (this.currentMonth === 12) {
                             this.currentMonth = 1;
                             this.currentYear++
                         } else this.currentMonth++
                     },
                 
                     daysInMonth(month, year) {
                         return new Date(year, month, 0).getDate()
                     },
                 
                     firstDayOfMonth(month, year) {
                         let d = new Date(year, month - 1, 1).getDay()
                         return d === 0 ? 6 : d - 1
                     },
                 
                     dateKey(day) {
                         return this.currentYear + '-' +
                             String(this.currentMonth).padStart(2, '0') + '-' +
                             String(day).padStart(2, '0')
                     },
                 
                     isWeekend(day) {
                         const dow = new Date(this.currentYear, this.currentMonth - 1, day).getDay()
                         return dow === 0 || dow === 6
                     },
                 
                     isToday(day) {
                         const today = new Date()
                         return day === today.getDate() &&
                             this.currentMonth === today.getMonth() + 1 &&
                             this.currentYear === today.getFullYear()
                     },
                 
                     getDayData(day) {
                         return calendarData[this.dateKey(day)] ?? null
                     },
                 }">

                {{-- Cabecera con navegación --}}
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('labels.ops_calendar') }}</h3>
                    <div class="flex items-center gap-2">
                        <button class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700"
                                @click="prevMonth()">
                            <svg class="h-3.5 w-3.5"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor"
                                 stroke-width="2.5">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <span class="w-36 text-center text-sm font-semibold text-gray-700"
                              x-text="monthLabel"></span>
                        <button class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700"
                                @click="nextMonth()">
                            <svg class="h-3.5 w-3.5"
                                 xmlns="http://www.w3.org/2000/svg"
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
                </div>

                {{-- Header días semana --}}
                <div class="mb-2 grid grid-cols-7">
                    <template x-for="d in ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']">
                        <div class="py-1 text-center text-[10px] font-semibold uppercase tracking-wide text-gray-400"
                             x-text="d"></div>
                    </template>
                </div>

                {{-- Grid días --}}
                <div class="grid grid-cols-7 gap-1.5">

                    {{-- Celdas vacías inicio --}}
                    <template x-for="i in firstDayOfMonth(currentMonth, currentYear)"
                              :key="'empty-' + i">
                        <div class="h-16 rounded-xl border border-dashed border-gray-100 bg-gray-50/40"></div>
                    </template>

                    {{-- Días del mes --}}
                    <template x-for="day in daysInMonth(currentMonth, currentYear)"
                              :key="day">
                        <div>
                            {{-- Día CON operaciones --}}
                            <template x-if="getDayData(day)">
                                <div class="relative flex h-16 cursor-pointer select-none flex-col justify-between rounded-xl border p-2 transition-all duration-150"
                                     @click="selectDay(dateKey(day), getDayData(day))"
                                     :class="selectedDate === dateKey(day) ?
                                         (getDayData(day).pnl >= 0 ?
                                             'ring-2 ring-emerald-500 bg-emerald-100 border-emerald-300' :
                                             'ring-2 ring-red-400 bg-red-100 border-red-300') :
                                         (getDayData(day).pnl >= 0 ?
                                             'bg-emerald-50 border-emerald-200 hover:bg-emerald-100 hover:border-emerald-300 hover:shadow-sm' :
                                             'bg-red-50 border-red-200 hover:bg-red-100 hover:border-red-300 hover:shadow-sm')">

                                    {{-- Top: número + badge ops --}}
                                    <div class="flex items-start justify-between">
                                        <span class="text-sm font-bold leading-none"
                                              :class="getDayData(day).pnl >= 0 ? 'text-emerald-700' : 'text-red-600'"
                                              x-text="day"></span>
                                        <span class="rounded-full px-1 py-0.5 text-[9px] font-semibold leading-none"
                                              :class="getDayData(day).pnl >= 0 ? 'bg-emerald-200 text-emerald-700' : 'bg-red-200 text-red-600'"
                                              x-text="getDayData(day).total + 'op'"></span>
                                    </div>

                                    {{-- Bottom: PnL --}}
                                    <div class="flex items-end justify-between">
                                        <span class="text-xs font-bold tabular-nums leading-none"
                                              :class="getDayData(day).pnl >= 0 ? 'text-emerald-600' : 'text-red-500'"
                                              x-text="(getDayData(day).pnl > 0 ? '+' : '') + getDayData(day).pnl + 'R'"></span>
                                        <span class="text-[9px] leading-none text-gray-400"
                                              x-text="getDayData(day).winrate + '%'"></span>
                                    </div>

                                    {{-- Barra winrate inferior --}}
                                    <div class="absolute bottom-0 left-0 right-0 h-1 overflow-hidden rounded-b-xl">
                                        <div class="h-full transition-all duration-300"
                                             :class="getDayData(day).pnl >= 0 ? 'bg-emerald-400' : 'bg-red-400'"
                                             :style="'width: ' + getDayData(day).winrate + '%'"></div>
                                    </div>
                                </div>
                            </template>

                            {{-- Día SIN operaciones --}}
                            <template x-if="!getDayData(day)">
                                <div class="relative flex h-16 flex-col justify-between rounded-xl border p-2 transition-colors"
                                     :class="{
                                         'bg-gray-50/60 border-gray-100': isWeekend(day),
                                         'bg-white border-gray-100': !isWeekend(day),
                                         'ring-1 ring-blue-300 border-blue-200': isToday(day)
                                     }">

                                    <span class="text-sm leading-none"
                                          :class="{
                                              'text-gray-300 font-normal': isWeekend(day),
                                              'text-gray-400 font-medium': !isWeekend(day) && !isToday(day),
                                              'text-blue-500 font-bold': isToday(day)
                                          }"
                                          x-text="day"></span>

                                    <span class="self-end text-[8px] font-bold uppercase tracking-widest text-blue-400"
                                          x-show="isToday(day)">
                                        hoy
                                    </span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Leyenda --}}
                <div class="mt-4 flex items-center gap-4 border-t border-gray-100 pt-3">
                    <div class="flex items-center gap-1.5">
                        <div class="h-3 w-3 rounded-sm border border-emerald-200 bg-emerald-100"></div>
                        <span class="text-[10px] text-gray-400">{{ __('labels.positive_day') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="h-3 w-3 rounded-sm border border-red-200 bg-red-100"></div>
                        <span class="text-[10px] text-gray-400">{{ __('labels.negative_day') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="h-1 w-8 rounded-full bg-gradient-to-r from-red-400 to-emerald-400"></div>
                        <span class="text-[10px] text-gray-400">{{ __('labels.bar_winrate_legend') }}</span>
                    </div>
                </div>
            </div>

            {{-- ── Columna derecha: Detalle del día ── --}}
            <div class="col-span-6 flex flex-col p-5">

                {{-- Estado vacío --}}
                <template x-if="!selectedDate">
                    <div class="flex flex-1 flex-col items-center justify-center text-center">
                        <svg class="mb-2 h-8 w-8 text-gray-200"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="1.5">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        <p class="text-sm text-gray-400">{{ __('labels.select_calendar_day') }}</p>
                        <p class="mt-1 text-xs text-gray-300">{{ __('labels.days_with_ops_colored') }}</p>
                    </div>
                </template>

                {{-- Detalle --}}
                <template x-if="selectedDate">
                    <div class="flex h-full flex-col">

                        {{-- Header del día --}}
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900"
                                   x-text="formatDate(selectedDate)"></p>
                                <div class="mt-0.5 flex items-center gap-3">
                                    <span class="text-xs text-gray-400">
                                        <span class="font-medium text-gray-600"
                                              x-text="selectedDayData?.total"></span> operaciones
                                    </span>
                                    <span class="text-xs font-medium"
                                          :class="selectedDayData?.pnl >= 0 ? 'text-emerald-600' : 'text-red-500'"
                                          x-text="(selectedDayData?.pnl > 0 ? '+' : '') + selectedDayData?.pnl + 'R'">
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        WR: <span class="font-medium text-gray-600"
                                              x-text="selectedDayData?.winrate + '%'"></span>
                                    </span>
                                </div>
                            </div>
                            <button class="flex h-6 w-6 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100"
                                    @click="selectedDate = null; selectedDayData = null">
                                <svg class="h-3.5 w-3.5"
                                     xmlns="http://www.w3.org/2000/svg"
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

                        {{-- Lista de trades --}}
                        <div class="flex-1 space-y-2 overflow-y-auto"
                             style="max-height: 280px">
                            <template x-if="selectedTrades.length === 0">
                                <p class="py-4 text-center text-xs text-gray-400">{{ __('labels.no_operations_registered') }}</p>
                            </template>

                            <template x-for="trade in selectedTrades"
                                      :key="trade.id">
                                <div class="mx-1 flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-colors hover:ring-2 hover:ring-blue-300"
                                     :class="trade.pnl_r > 0 ? 'bg-emerald-50 border-emerald-100' : (trade.pnl_r < 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100')"
                                     @click.stop="openTradeDetail({
             id:             trade.id,
             date:           trade.trade_date.split('-').reverse().join('/'),
             direction:      trade.direction,
             entry_price:    trade.entry_price,
             exit_price:     trade.exit_price,
             stop_loss:      trade.stop_loss,
             pnl_r:          trade.pnl_r,
             session:        trade.session,
             setup_rating:   trade.setup_rating,
             followed_rules: trade.followed_rules,
             confluences:    trade.confluences,
             notes:          trade.notes,
             screenshot:     trade.screenshot ?? null
         })">


                                    <div class="flex items-center gap-2.5">
                                        {{-- Dirección --}}
                                        <span class="rounded px-1.5 py-0.5 text-xs font-bold"
                                              :class="trade.direction === 'long' ?
                                                  'bg-emerald-100 text-emerald-700' :
                                                  'bg-red-100 text-red-600'"
                                              x-text="trade.direction === 'long' ? 'LONG' : 'SHORT'">
                                        </span>

                                        {{-- Precios --}}
                                        <div class="text-xs tabular-nums text-gray-500">
                                            <span x-text="parseFloat(trade.entry_price).toFixed(5)"></span>
                                            <span class="mx-1 text-gray-300">→</span>
                                            <span x-text="parseFloat(trade.exit_price).toFixed(5)"></span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        {{-- Sesión --}}
                                        <span class="text-xs capitalize text-gray-400"
                                              x-text="trade.session ?? '—'"></span>

                                        {{-- Rating --}}
                                        <span class="text-xs text-amber-400"
                                              x-text="trade.setup_rating ? '★'.repeat(trade.setup_rating) : ''"></span>

                                        {{-- Reglas --}}
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full text-xs"
                                              :class="trade.followed_rules ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-500'">
                                            <template x-if="trade.followed_rules">
                                                <svg class="h-2.5 w-2.5"
                                                     xmlns="http://www.w3.org/2000/svg"
                                                     fill="none"
                                                     viewBox="0 0 24 24"
                                                     stroke="currentColor"
                                                     stroke-width="3">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </template>
                                            <template x-if="!trade.followed_rules">
                                                <svg class="h-2.5 w-2.5"
                                                     xmlns="http://www.w3.org/2000/svg"
                                                     fill="none"
                                                     viewBox="0 0 24 24"
                                                     stroke="currentColor"
                                                     stroke-width="3">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </template>
                                        </span>

                                        {{-- PnL en R --}}
                                        <span class="w-14 text-right text-sm font-bold tabular-nums"
                                              :class="trade.pnl_r > 0 ? 'text-emerald-600' : (trade.pnl_r < 0 ? 'text-red-500' : 'text-gray-400')"
                                              x-text="(trade.pnl_r > 0 ? '+' : '') + parseFloat(trade.pnl_r).toFixed(2) + 'R'">
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </div>

    {{-- ═══ FILA 4: WR por día + WR por sesión ═══════════════════ --}}
    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">

        {{-- Win Rate por día --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="mb-4 text-sm font-semibold text-gray-900">{{ __('labels.winrate_by_day') }}</h3>
            <div class="space-y-2">
                @foreach ($m['daily_winrate'] as $day)
                    @if ($day['total'] > 0)
                        <div class="flex items-center gap-3">
                            <span class="w-8 text-xs font-medium text-gray-500">{{ $day['day'] }}</span>
                            <div class="h-5 flex-1 overflow-hidden rounded-full bg-gray-100">
                                <div class="{{ $day['wr'] >= 60 ? 'bg-emerald-500' : ($day['wr'] >= 40 ? 'bg-amber-400' : 'bg-red-400') }} flex h-full items-center justify-end rounded-full pr-2 transition-all duration-500"
                                     style="width: {{ max($day['wr'], 4) }}%">
                                    <span class="text-xs font-bold text-white">{{ $day['wr'] }}%</span>
                                </div>
                            </div>
                            <span class="w-12 text-right text-xs tabular-nums text-gray-400">{{ $day['total'] }} ops</span>
                        </div>
                    @endif
                @endforeach

                @if (collect($m['daily_winrate'])->sum('total') === 0)
                    <p class="py-4 text-center text-sm text-gray-400">{{ __('labels.no_data') }}</p>
                @endif
            </div>
        </div>

        {{-- Rendimiento por sesión --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="mb-4 text-sm font-semibold text-gray-900">{{ __('labels.performance_by_session') }}</h3>
            @if (!empty($m['pnl_by_session']['labels']))
                <div class="space-y-3">
                    @foreach ($m['pnl_by_session']['labels'] as $i => $label)
                        @php
                            $sessionWr = $m['pnl_by_session']['wr'][$i];
                            $sessionPnl = $m['pnl_by_session']['pnl'][$i];
                            $sessionN = $m['pnl_by_session']['counts'][$i];
                        @endphp
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="flex items-center gap-2">
                                <span class="{{ $sessionPnl >= 0 ? 'bg-emerald-500' : 'bg-red-400' }} h-2 w-2 rounded-full"></span>
                                <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                                <span class="text-xs text-gray-400">({{ $sessionN }})</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-xs text-gray-400">WR</p>
                                    <p class="{{ $sessionWr >= 50 ? 'text-emerald-600' : 'text-red-500' }} text-sm font-bold">{{ $sessionWr }}%</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400">PnL</p>
                                    <p class="{{ $sessionPnl >= 0 ? 'text-emerald-600' : 'text-red-500' }} text-sm font-bold tabular-nums">
                                        {{ $sessionPnl > 0 ? '+' : '' }}{{ $sessionPnl }}{{ $unit }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="py-8 text-center text-sm text-gray-400">
                    {{ __('labels.no_sessions_registered') }}<br>
                    <span class="text-xs">{{ __('labels.add_session_to_trades') }}</span>
                </p>
            @endif
        </div>

    </div>

    {{-- ═══ FILA 5: Distribución de R + Impacto Reglas ═══════════ --}}
    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">

        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="mb-4 text-sm font-semibold text-gray-900">{{ __('labels.r_distribution') }}</h3>
            <div id="chart-r-dist"
                 style="min-height:200px"></div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="mb-1 text-sm font-semibold text-gray-900">{{ __('labels.rules_impact_title') }}</h3>
            <p class="mb-4 text-xs text-gray-400">{{ __('labels.followed_rules_vs_not') }}</p>
            @php $ri = $m['rules_impact'] @endphp
            @if ($ri['followed']['count'] > 0 || $ri['not_followed']['count'] > 0)
                <div class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg border border-emerald-100 bg-emerald-50 p-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="text-sm font-medium text-gray-700">{{ __('labels.followed_rules') }}</span>
                            <span class="text-xs text-gray-400">({{ $ri['followed']['count'] }})</span>
                        </div>
                        <div class="flex items-center gap-4 text-right">
                            <div>
                                <p class="text-xs text-gray-400">WR</p>
                                <p class="text-sm font-bold text-emerald-600">{{ $ri['followed']['wr'] }}%</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Avg R</p>
                                <p class="{{ $ri['followed']['avg_r'] >= 0 ? 'text-emerald-600' : 'text-red-500' }} text-sm font-bold">{{ $ri['followed']['avg_r'] > 0 ? '+' : '' }}{{ $ri['followed']['avg_r'] }}R</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-red-100 bg-red-50 p-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-red-400"></span>
                            <span class="text-sm font-medium text-gray-700">{{ __('labels.not_followed_rules') }}</span>
                            <span class="text-xs text-gray-400">({{ $ri['not_followed']['count'] }})</span>
                        </div>
                        <div class="flex items-center gap-4 text-right">
                            <div>
                                <p class="text-xs text-gray-400">WR</p>
                                <p class="text-sm font-bold text-red-500">{{ $ri['not_followed']['wr'] }}%</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Avg R</p>
                                <p class="{{ $ri['not_followed']['avg_r'] >= 0 ? 'text-emerald-600' : 'text-red-500' }} text-sm font-bold">{{ $ri['not_followed']['avg_r'] > 0 ? '+' : '' }}{{ $ri['not_followed']['avg_r'] }}R</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <p class="py-6 text-center text-sm text-gray-400">{{ __('labels.no_enough_data') }}</p>
            @endif
        </div>

    </div>

    {{-- ═══ FILA: Rating vs Rendimiento + Análisis de Confluencias ══ --}}
    <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">

        {{-- Rating vs Rendimiento --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('labels.rating_vs_performance') }}</h3>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('labels.best_setups_perform_more') }}</p>
            </div>

            @php
                $ratingRows = collect($m['rating_impact'])->where('count', '>', 0);
                $maxAbsR = $ratingRows->max(fn($r) => abs($r['avg_r'])) ?: 1;
            @endphp

            @if ($ratingRows->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-gray-400">{{ __('labels.no_enough_data_short') }}</div>
            @else
                <div class="p-4">
                    <div class="space-y-2">
                        @foreach ($ratingRows->sortByDesc('rating') as $row)
                            @php
                                $pct = min(100, ($row['avg_r'] / $maxAbsR) * 100);
                                $isPositive = $row['avg_r'] >= 0;
                                [$badge, $badgeColor] = match (true) {
                                    $row['avg_r'] >= 1.5 => [__('labels.label_excellent'), 'bg-emerald-100 text-emerald-700'],
                                    $row['avg_r'] >= 0.5 => [__('labels.label_good'), 'bg-blue-100 text-blue-700'],
                                    $row['avg_r'] >= 0 => [__('labels.label_neutral'), 'bg-gray-100 text-gray-500'],
                                    $row['avg_r'] >= -0.5 => [__('labels.label_weak'), 'bg-amber-100 text-amber-700'],
                                    default => [__('labels.label_avoid'), 'bg-red-100 text-red-600'],
                                };
                            @endphp
                            <div class="{{ $isPositive ? 'border-gray-100 bg-gray-50' : 'border-red-50 bg-red-50/40' }} relative overflow-hidden rounded-lg border px-4 py-3">
                                <div class="{{ $isPositive ? 'bg-emerald-400/10' : 'bg-red-400/10' }} absolute inset-y-0 left-0 transition-all duration-500"
                                     style="width: {{ max(4, $pct) }}%"></div>
                                <div class="relative flex items-center gap-4">
                                    <div class="flex w-36 shrink-0 flex-col gap-1">
                                        <div class="flex items-center gap-0.5">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="{{ $i <= $row['rating'] ? 'text-amber-400' : 'text-gray-200' }} h-3.5 w-3.5"
                                                     fill="currentColor"
                                                     viewBox="0 0 20 20">
                                                    <path
                                                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endfor
                                        </div>
                                        <span class="{{ $badgeColor }} w-fit rounded-full px-1.5 py-0.5 text-[10px] font-semibold">{{ $badge }}</span>
                                    </div>
                                    <div class="flex flex-1 items-center justify-between gap-2">
                                        <div class="text-center">
                                            <p class="{{ $isPositive ? 'text-emerald-600' : 'text-red-500' }} text-base font-bold tabular-nums">
                                                {{ $row['avg_r'] >= 0 ? '+' : '' }}{{ $row['avg_r'] }}R
                                            </p>
                                            <p class="text-[10px] text-gray-400">avg R</p>
                                        </div>
                                        <div class="h-8 w-px bg-gray-200"></div>
                                        <div class="text-center">
                                            <p class="{{ $row['wr'] >= 50 ? 'text-emerald-600' : 'text-red-500' }} text-base font-bold tabular-nums">{{ $row['wr'] }}%</p>
                                            <p class="text-[10px] text-gray-400">winrate</p>
                                        </div>
                                        <div class="h-8 w-px bg-gray-200"></div>
                                        <div class="text-center">
                                            <p class="text-base font-bold tabular-nums text-gray-700">{{ $row['count'] }}</p>
                                            <p class="text-[10px] text-gray-400">trades</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @php
                        $best = $ratingRows->sortByDesc('avg_r')->first();
                        $worst = $ratingRows->sortBy('avg_r')->first();
                    @endphp
                    @if ($best && $worst && $best['rating'] !== $worst['rating'])
                        <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-2.5">
                            <p class="text-xs text-blue-700">
                                💡 Tus setups de <span class="font-semibold">{{ $best['rating'] }}★</span>
                                rinden <span class="font-semibold">{{ $best['avg_r'] >= 0 ? '+' : '' }}{{ $best['avg_r'] }}R</span>
                                vs <span class="font-semibold">{{ $worst['avg_r'] >= 0 ? '+' : '' }}{{ $worst['avg_r'] }}R</span>
                                de los de <span class="font-semibold">{{ $worst['rating'] }}★</span>.
                                @if ($best['rating'] > $worst['rating'])
                                    Tu criterio <span class="font-semibold">está calibrado</span>.
                                @else
                                    ⚠️ Revisa tu criterio — operas mejor en setups que valoras menos.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Análisis de Confluencias --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('labels.confluence_analysis_title') }}</h3>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('labels.technical_factors_correlation') }}</p>
            </div>

            @php $confluences = $m['confluence_analysis']; @endphp

            @if (empty($confluences))
                <div class="flex flex-col items-center justify-center px-5 py-10 text-center">
                    <p class="text-sm text-gray-400">{{ __('labels.no_confluences_registered') }}</p>
                    <p class="mt-1 text-xs text-gray-300">{{ __('labels.add_confluences_to_trades') }}</p>
                </div>
            @else
                @php $maxConfR = collect($confluences)->max(fn($c) => abs($c['avg_r'])) ?: 1; @endphp
                <div class="p-4">
                    <div class="space-y-2">
                        @foreach ($confluences as $c)
                            @php
                                $cPositive = $c['avg_r'] >= 0;
                                $cPct = min(100, (abs($c['avg_r']) / $maxConfR) * 100);
                            @endphp
                            <div class="{{ $cPositive ? 'border-gray-100 bg-gray-50' : 'border-red-50 bg-red-50/40' }} relative overflow-hidden rounded-lg border px-4 py-3">
                                <div class="{{ $cPositive ? 'bg-emerald-400/10' : 'bg-red-400/10' }} absolute inset-y-0 left-0 transition-all duration-500"
                                     style="width: {{ max(4, $cPct) }}%"></div>
                                <div class="relative flex items-center justify-between gap-4">

                                    {{-- Nombre --}}
                                    <span class="w-24 shrink-0 truncate text-sm font-semibold text-gray-700">
                                        {{ $c['name'] }}
                                    </span>

                                    {{-- Stats --}}
                                    <div class="flex flex-1 items-center justify-end gap-4">
                                        <div class="text-center">
                                            <p class="{{ $cPositive ? 'text-emerald-600' : 'text-red-500' }} text-sm font-bold tabular-nums">
                                                {{ $c['avg_r'] >= 0 ? '+' : '' }}{{ $c['avg_r'] }}R
                                            </p>
                                            <p class="text-[10px] text-gray-400">avg R</p>
                                        </div>
                                        <div class="h-8 w-px bg-gray-200"></div>
                                        <div class="text-center">
                                            <p class="{{ $c['wr'] >= 50 ? 'text-emerald-600' : 'text-red-500' }} text-sm font-bold tabular-nums">{{ $c['wr'] }}%</p>
                                            <p class="text-[10px] text-gray-400">WR</p>
                                        </div>
                                        <div class="h-8 w-px bg-gray-200"></div>
                                        <div class="text-center">
                                            <p class="text-sm font-bold tabular-nums text-gray-700">{{ $c['count'] }}</p>
                                            <p class="text-[10px] text-gray-400">trades</p>
                                        </div>
                                        <div class="h-8 w-px bg-gray-200"></div>
                                        <div class="text-center">
                                            <p class="{{ $c['total_r'] >= 0 ? 'text-emerald-600' : 'text-red-500' }} text-sm font-bold tabular-nums">
                                                {{ $c['total_r'] >= 0 ? '+' : '' }}{{ $c['total_r'] }}R
                                            </p>
                                            <p class="text-[10px] text-gray-400">total R</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Insight --}}
                    @if (count($confluences) >= 2)
                        @php
                            $topC = $confluences[0];
                            $bottomC = end($confluences);
                        @endphp
                        <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-2.5">
                            <p class="text-xs text-blue-700">
                                💡 <span class="font-semibold">{{ $topC['name'] }}</span> es tu confluencia más rentable
                                con <span class="font-semibold">{{ $topC['avg_r'] >= 0 ? '+' : '' }}{{ $topC['avg_r'] }}R</span> de media.
                                @if ($bottomC['avg_r'] < 0)
                                    Evita operar solo con <span class="font-semibold">{{ $bottomC['name'] }}</span>
                                    ({{ $bottomC['avg_r'] }}R avg).
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

    </div>
@endif
