{{--
    Informe mensual en PDF (Fase 5 · P10).

    Escrito para dompdf, que es PHP puro: aquí no hay Tailwind, ni flex, ni grid.
    Todo lo que parece una rejilla es una tabla y todo el CSS va en este fichero.
    Tampoco hay tema oscuro: esto se imprime.

    La plantilla no calcula nada de negocio. Lo que pinta viene entero de
    BuildMonthlyReport, que a su vez compone las acciones que ya alimentan el
    dashboard y el Laboratorio. Si un número de aquí no cuadra con la pantalla,
    el error está en la acción, no en la vista.
--}}
@php
    $m = $report['metrics'];
    $kpis = $m['extraKpis'] ?? [];
    $divisa = $report['currency'];

    // El símbolo se pone solo si la divisa es única; con varias cuentas mezcladas
    // se rotula el número pelado antes que etiquetarlo con una divisa falsa.
    $money = function ($valor) use ($divisa): string {
        $n = number_format((float) $valor, 2, ',', '.');

        return $divisa ? $n . ' ' . $divisa : $n;
    };

    $signed = function ($valor) use ($money): string {
        return ((float) $valor > 0 ? '+' : '') . $money($valor);
    };

    $tone = fn ($valor): string => (float) $valor > 0 ? '#15803D' : ((float) $valor < 0 ? '#B91C1C' : '#4B5563');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('export.pdf.title') }} · {{ $report['month']['label'] }}</title>
    <style>
        @page { margin: 96px 40px 64px 40px; }

        /* DejaVu es la única fuente que dompdf trae con acentos y símbolo de euro. */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            color: #111827;
            margin: 0;
        }

        header, footer { position: fixed; left: 0; right: 0; }
        header { top: -72px; height: 56px; }
        footer { bottom: -44px; height: 32px; }

        .logo { height: 26px; }

        .head-right {
            text-align: right;
            font-size: 9px;
            color: #6B7280;
        }

        .stamp {
            display: inline-block;
            border: 2px solid #B91C1C;
            color: #B91C1C;
            font-weight: bold;
            font-size: 11px;
            letter-spacing: 2px;
            padding: 2px 8px;
        }

        .rule { border-top: 1px solid #E5E7EB; }

        h1 { font-size: 21px; margin: 0 0 2px 0; }
        h2 {
            font-size: 12px;
            margin: 18px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #E5E7EB;
        }
        p { margin: 0 0 4px 0; }

        .muted { color: #6B7280; }
        .small { font-size: 8.5px; }
        .right { text-align: right; }
        .center { text-align: center; }

        table { width: 100%; border-collapse: collapse; }

        /* Tarjetas de KPI: una celda por métrica, con su marco. */
        .kpi td {
            width: 25%;
            border: 1px solid #E5E7EB;
            padding: 7px 9px;
            vertical-align: top;
        }
        .kpi .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #6B7280;
        }
        .kpi .value { font-size: 14px; font-weight: bold; padding-top: 2px; }

        .data th {
            background: #F3F4F6;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4B5563;
            text-align: left;
            padding: 5px 7px;
            border-bottom: 1px solid #E5E7EB;
        }
        .data td {
            padding: 5px 7px;
            border-bottom: 1px solid #F3F4F6;
        }

        .cal th {
            font-size: 8px;
            color: #6B7280;
            padding: 3px 0;
            text-align: center;
        }
        .cal td {
            width: 14.28%;
            height: 40px;
            border: 1px solid #E5E7EB;
            padding: 3px 4px;
            vertical-align: top;
        }
        .cal .day { font-size: 8px; color: #9CA3AF; }
        .cal .pnl { font-size: 9.5px; font-weight: bold; padding-top: 4px; }
        .cal .empty { background: #FAFAFA; border-color: #F3F4F6; }

        .chip {
            display: inline-block;
            background: #EEF2FF;
            color: #4338CA;
            font-size: 8px;
            padding: 1px 5px;
            border-radius: 6px;
        }

        .note {
            background: #F9FAFB;
            border-left: 3px solid #E5E7EB;
            padding: 5px 8px;
            color: #4B5563;
        }
    </style>
</head>
<body>

    <header>
        <table>
            <tr>
                <td>
                    @if ($logo)
                        <img class="logo" src="{{ $logo }}" alt="TradeForge">
                    @else
                        <strong>TradeForge</strong>
                    @endif
                </td>
                <td class="head-right">
                    {{ __('export.pdf.title') }} · {{ $report['month']['label'] }}
                    @if ($isDemo)
                        <br><span class="stamp">{{ __('export.pdf.demo_stamp') }}</span>
                    @endif
                </td>
            </tr>
        </table>
        <div class="rule" style="margin-top: 6px;"></div>
    </header>

    <footer>
        <div class="rule" style="margin-bottom: 5px;"></div>
        <table class="small muted">
            <tr>
                <td>{{ $isDemo ? __('export.pdf.demo_footer') : __('export.pdf.footer') }}</td>
                <td class="right">
                    {{ __('export.pdf.generated_at', ['date' => $report['generated_at']->format('d/m/Y H:i')]) }}
                </td>
            </tr>
        </table>
    </footer>

    {{-- ── Portada ────────────────────────────────────────────────────── --}}
    <h1>{{ $report['month']['label'] }}</h1>
    <p class="muted">{{ __('export.pdf.for', ['name' => $report['user']['name']]) }}</p>

    @if ($report['account'])
        <table class="kpi" style="margin-top: 12px;">
            <tr>
                <td>
                    <div class="label">{{ __('export.pdf.account_label') }}</div>
                    <div class="value" style="font-size: 12px;">{{ $report['account']['name'] }}</div>
                </td>
                <td>
                    <div class="label">{{ __('export.pdf.account.phase') }}</div>
                    <div class="value" style="font-size: 12px;">{{ $report['account']['phase'] }}</div>
                </td>
                <td>
                    <div class="label">{{ __('export.pdf.account.status') }}</div>
                    <div class="value" style="font-size: 12px;">{{ $report['account']['status'] }}</div>
                </td>
                <td>
                    <div class="label">{{ __('export.pdf.account.balance') }}</div>
                    <div class="value" style="font-size: 12px;">{{ $money($report['account']['balance']) }}</div>
                </td>
            </tr>
        </table>
    @else
        <p class="muted small">{{ __('export.pdf.all_accounts_option') }}</p>
    @endif

    {{-- ── El mes en cifras ───────────────────────────────────────────── --}}
    <h2>{{ __('export.pdf.metrics.title') }}</h2>

    <table class="kpi">
        <tr>
            <td>
                <div class="label">{{ __('export.pdf.metrics.pnl') }}</div>
                <div class="value" style="color: {{ $tone($m['pnlTotal']) }};">{{ $signed($m['pnlTotal']) }}</div>
            </td>
            <td>
                <div class="label">{{ __('export.pdf.metrics.trades') }}</div>
                <div class="value">{{ $report['trades_count'] }}</div>
            </td>
            <td>
                <div class="label">{{ __('export.pdf.metrics.win_rate') }}</div>
                <div class="value">{{ number_format((float) $m['winRateChartData']['rate'], 1, ',', '.') }} %</div>
            </td>
            <td>
                <div class="label">{{ __('export.pdf.metrics.profit_factor') }}</div>
                <div class="value">
                    {{-- null significa infinito: un mes sin una sola pérdida. --}}
                    {{ $kpis['profit_factor'] === null
                        ? __('export.pdf.metrics.infinite')
                        : number_format((float) $kpis['profit_factor'], 2, ',', '.') }}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('export.pdf.metrics.expectancy') }}</div>
                <div class="value" style="color: {{ $tone($kpis['expectancy'] ?? 0) }};">{{ $signed($kpis['expectancy'] ?? 0) }}</div>
            </td>
            <td>
                <div class="label">{{ __('export.pdf.metrics.max_drawdown') }}</div>
                <div class="value" style="color: #B91C1C;">{{ $money($kpis['max_drawdown'] ?? 0) }}</div>
            </td>
            <td>
                <div class="label">{{ __('export.pdf.metrics.avg_win') }} / {{ __('export.pdf.metrics.avg_loss') }}</div>
                <div class="value" style="font-size: 11px;">
                    {{ $money($m['avgPnLChartData']['avg_win']) }} / {{ $money($m['avgPnLChartData']['avg_loss']) }}
                </div>
            </td>
            <td>
                <div class="label">{{ __('export.pdf.metrics.win_days') }} / {{ __('export.pdf.metrics.loss_days') }}</div>
                <div class="value">
                    {{ $m['dailyWinLossData']['count_wins'] }} / {{ $m['dailyWinLossData']['count_losses'] }}
                </div>
            </td>
        </tr>
        <tr>
            <td style="width: 50%;">
                <div class="label">{{ __('export.pdf.metrics.best_trade') }}</div>
                <div class="value" style="color: #15803D;">
                    {{ $kpis['best_trade'] === null ? '—' : $signed($kpis['best_trade']) }}
                </div>
            </td>
            <td style="width: 50%;" colspan="3">
                <div class="label">{{ __('export.pdf.metrics.worst_trade') }}</div>
                <div class="value" style="color: #B91C1C;">
                    {{ $kpis['worst_trade'] === null ? '—' : $signed($kpis['worst_trade']) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Contra el mes anterior ─────────────────────────────────────── --}}
    @if ($report['previous'])
        @php $prev = $report['previous']; @endphp
        <h2>{{ __('export.pdf.previous.title', ['month' => $prev['label']]) }}</h2>

        <table class="data">
            <tr>
                <th>&nbsp;</th>
                <th class="right">{{ $report['month']['label'] }}</th>
                <th class="right">{{ $prev['label'] }}</th>
                <th class="right">&Delta;</th>
            </tr>
            <tr>
                <td>{{ __('export.pdf.previous.pnl') }}</td>
                <td class="right">{{ $signed($m['pnlTotal']) }}</td>
                <td class="right">{{ $signed($prev['pnl']) }}</td>
                <td class="right" style="color: {{ $tone((float) $m['pnlTotal'] - $prev['pnl']) }};">
                    {{ $signed((float) $m['pnlTotal'] - $prev['pnl']) }}
                </td>
            </tr>
            <tr>
                <td>{{ __('export.pdf.previous.win_rate') }}</td>
                <td class="right">{{ number_format((float) $m['winRateChartData']['rate'], 1, ',', '.') }} %</td>
                <td class="right">{{ number_format($prev['win_rate'], 1, ',', '.') }} %</td>
                <td class="right" style="color: {{ $tone((float) $m['winRateChartData']['rate'] - $prev['win_rate']) }};">
                    {{ ((float) $m['winRateChartData']['rate'] - $prev['win_rate'] > 0 ? '+' : '')
                        . number_format((float) $m['winRateChartData']['rate'] - $prev['win_rate'], 1, ',', '.') }} %
                </td>
            </tr>
            <tr>
                <td>{{ __('export.pdf.previous.trades') }}</td>
                <td class="right">{{ $report['trades_count'] }}</td>
                <td class="right">{{ $prev['trades'] }}</td>
                <td class="right">{{ ($report['trades_count'] - $prev['trades'] > 0 ? '+' : '') . ($report['trades_count'] - $prev['trades']) }}</td>
            </tr>
        </table>
    @else
        {{-- Callarse la sección dejaría al lector preguntándose si falta algo. --}}
        <p class="note small" style="margin-top: 10px;">{{ __('export.pdf.previous.none') }}</p>
    @endif

    {{-- ── Curva de capital ───────────────────────────────────────────── --}}
    <h2>{{ __('export.pdf.equity.title') }}</h2>
    <p class="small muted">{{ __('export.pdf.equity.lead') }}</p>

    @php
        $serie = array_values(array_map('floatval', $report['equity']['data'] ?? []));
        $ancho = 515;
        $alto = 130;
        $margen = 6;

        $puntos = '';
        $ceroY = null;

        // Con un solo punto (el cero inicial) no hay curva: la línea necesita dos.
        if (count($serie) > 1) {
            $min = min($serie);
            $max = max($serie);
            $rango = ($max - $min) ?: 1;
            $util = $alto - $margen * 2;
            $n = count($serie) - 1;

            $coords = [];
            foreach ($serie as $i => $v) {
                $x = round(($i / $n) * $ancho, 1);
                $y = round($alto - $margen - (($v - $min) / $rango) * $util, 1);
                $coords[] = $x . ',' . $y;
            }
            $puntos = implode(' ', $coords);

            // La línea del cero solo se dibuja si el mes la cruza.
            if ($min < 0 && $max > 0) {
                $ceroY = round($alto - $margen - ((0 - $min) / $rango) * $util, 1);
            }

            $color = end($serie) >= 0 ? '#15803D' : '#B91C1C';
            $relleno = $puntos . ' ' . $ancho . ',' . ($alto - $margen) . ' 0,' . ($alto - $margen);
        }
    @endphp

    @if ($puntos === '')
        <p class="note small">{{ __('export.pdf.equity.empty') }}</p>
    @else
        <svg width="{{ $ancho }}" height="{{ $alto }}" viewBox="0 0 {{ $ancho }} {{ $alto }}">
            <rect x="0" y="0" width="{{ $ancho }}" height="{{ $alto }}" fill="#FAFAFA" stroke="#E5E7EB" stroke-width="1"/>
            @if ($ceroY !== null)
                <line x1="0" y1="{{ $ceroY }}" x2="{{ $ancho }}" y2="{{ $ceroY }}" stroke="#D1D5DB" stroke-width="1" stroke-dasharray="3,3"/>
            @endif
            <polygon points="{{ $relleno }}" fill="{{ $color }}" fill-opacity="0.12"/>
            <polyline points="{{ $puntos }}" fill="none" stroke="{{ $color }}" stroke-width="1.6"/>
        </svg>
    @endif

    {{-- ── Calendario ─────────────────────────────────────────────────── --}}
    <h2>{{ __('export.pdf.calendar.title') }}</h2>
    <p class="small muted">{{ __('export.pdf.calendar.lead') }}</p>

    <table class="cal">
        <tr>
            @foreach (__('export.pdf.calendar.weekdays') as $inicial)
                <th>{{ $inicial }}</th>
            @endforeach
        </tr>
        @foreach ($report['calendar'] as $semana)
            <tr>
                @foreach ($semana as $dia)
                    @if ($dia === null)
                        <td class="empty">&nbsp;</td>
                    @else
                        <td>
                            <div class="day">{{ $dia['day'] }}</div>
                            @if ($dia['trades'] > 0)
                                <div class="pnl" style="color: {{ $tone($dia['pnl']) }};">{{ $signed($dia['pnl']) }}</div>
                            @endif
                        </td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </table>

    {{-- ── Coste de los errores ───────────────────────────────────────── --}}
    @php $coste = $report['mistake_cost']; @endphp
    <h2>{{ __('export.pdf.mistakes.title') }}</h2>

    <table class="kpi">
        <tr>
            <td style="width: 33.3%;">
                <div class="label">{{ __('export.pdf.mistakes.cost') }}</div>
                <div class="value" style="color: {{ $coste['cost'] > 0 ? '#B91C1C' : '#15803D' }};">{{ $money($coste['cost']) }}</div>
            </td>
            <td style="width: 33.3%;">
                <div class="label">{{ __('export.pdf.mistakes.real') }}</div>
                <div class="value" style="color: {{ $tone($coste['real_pnl']) }};">{{ $signed($coste['real_pnl']) }}</div>
            </td>
            <td style="width: 33.4%;">
                <div class="label">{{ __('export.pdf.mistakes.without') }}</div>
                <div class="value" style="color: {{ $tone($coste['pnl_without']) }};">{{ $signed($coste['pnl_without']) }}</div>
            </td>
        </tr>
    </table>

    {{-- La cobertura nunca sale del informe sin el coste al lado, ni al revés. --}}
    <p class="note small" style="margin-top: 6px;">
        {{ __('export.pdf.mistakes.coverage', [
            'reviewed' => $coste['reviewed'],
            'total' => $coste['trades_total'],
            'coverage' => number_format((float) $coste['coverage'], 1, ',', '.'),
        ]) }}
        @if ($coste['pending_review'] > 0)
            {{ __('export.pdf.mistakes.pending', ['count' => $coste['pending_review']]) }}
        @endif
    </p>

    @if ($coste['by_mistake'] === [])
        <p class="small muted" style="margin-top: 6px;">{{ __('export.pdf.mistakes.empty') }}</p>
    @else
        <table class="data" style="margin-top: 8px;">
            <tr>
                <th>{{ __('export.pdf.mistakes.column_mistake') }}</th>
                <th class="right">{{ __('export.pdf.mistakes.column_count') }}</th>
                <th class="right">{{ __('export.pdf.mistakes.column_cost') }}</th>
                <th class="right">{{ __('export.pdf.mistakes.column_avg') }}</th>
            </tr>
            @foreach ($coste['by_mistake'] as $fila)
                <tr>
                    <td>{{ $fila['name'] }}</td>
                    <td class="right">{{ $fila['count'] }}</td>
                    <td class="right" style="color: {{ $fila['cost'] > 0 ? '#B91C1C' : '#15803D' }};">{{ $money($fila['cost']) }}</td>
                    <td class="right">{{ $money($fila['avg_cost']) }}</td>
                </tr>
            @endforeach
        </table>
        <p class="small muted" style="margin-top: 4px;">{{ __('export.pdf.mistakes.overlap') }}</p>
    @endif

    {{-- ── Reglas ─────────────────────────────────────────────────────── --}}
    <h2>{{ __('export.pdf.rules.title') }}</h2>
    <p class="small muted">{{ __('export.pdf.rules.lead') }}</p>

    @if ($report['rules'] === [])
        <p class="small muted">{{ __('export.pdf.rules.empty') }}</p>
    @else
        <table class="data">
            <tr>
                <th>{{ __('export.pdf.rules.column_rule') }}</th>
                <th class="right">{{ __('export.pdf.rules.column_breaches') }}</th>
                <th class="right">{{ __('export.pdf.rules.column_rate') }}</th>
            </tr>
            @foreach ($report['rules'] as $regla)
                <tr>
                    <td>
                        {{ $regla['text'] }}
                        @if ($regla['is_global'])
                            <span class="chip">{{ __('export.pdf.rules.global') }}</span>
                        @endif
                    </td>
                    <td class="right">
                        {{-- Sin unidad no hay nada que contar: es una regla que se
                             cumple a mano y el informe lo dice en vez de inventar un 100 %. --}}
                        @if ($regla['unit'] === null)
                            <span class="muted">{{ __('export.pdf.rules.manual') }}</span>
                        @else
                            {{ __('export.pdf.rules.unit_' . $regla['unit'], [
                                'breaches' => $regla['breaches'],
                                'scope' => $regla['scope'],
                            ]) }}
                        @endif
                    </td>
                    <td class="right">
                        @if ($regla['rate'] === null)
                            <span class="muted">—</span>
                        @else
                            <strong style="color: {{ $regla['rate'] >= 90 ? '#15803D' : ($regla['rate'] >= 70 ? '#B45309' : '#B91C1C') }};">
                                {{ number_format($regla['rate'], 1, ',', '.') }} %
                            </strong>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

</body>
</html>
