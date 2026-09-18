{{--
    Resumen semanal (R1).

    HTML de correo: tablas y estilos en línea, porque Outlook y Gmail siguen sin
    entender flexbox ni hojas de estilo externas. El bloque <style> solo añade el
    modo oscuro de los clientes que lo soportan (Apple Mail, iOS); ninguno de sus
    colores es imprescindible para leer el mensaje.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <meta name="supported-color-schemes" content="light dark">
        <title>{{ __('weekly.mail.subject', ['week' => $weekLabel]) }}</title>
        <style>
            @media (prefers-color-scheme: dark) {
                .tf-body { background-color: #0b1120 !important; }
                .tf-card { background-color: #111827 !important; border-color: #1f2937 !important; }
                .tf-text { color: #e5e7eb !important; }
                .tf-muted { color: #9ca3af !important; }
                .tf-rule { border-color: #1f2937 !important; }
            }
        </style>
    </head>

    <body class="tf-body" style="margin:0; padding:0; background-color:#f3f4f6; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">

        @php
            // El correo sale en dos idiomas: los separadores de miles y decimales
            // tienen que seguir al idioma en que se está escribiendo, no al del
            // servidor. Un «1.234,56» en el correo en inglés se lee como otra cifra.
            $money = fn (float $value): string => app()->getLocale() === 'es'
                ? number_format($value, 2, ',', '.')
                : number_format($value, 2, '.', ',');
        @endphp

        {{-- Preencabezado: lo que se lee en la lista antes de abrir el correo. --}}
        <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
            {{ __('weekly.mail.preheader', ['trades' => $summary['trades']]) }}
        </div>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 12px;" class="tf-body">
            <tr>
                <td align="center">

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="tf-card"
                           style="max-width:600px; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden;">

                        {{-- CABECERA --}}
                        <tr>
                            <td style="background-color:#4f46e5; padding:24px 28px;">
                                <p style="margin:0; font-size:13px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#c7d2fe;">
                                    {{ config('app.name', 'TradeForge') }}
                                </p>
                                <h1 style="margin:6px 0 0; font-size:22px; line-height:1.3; color:#ffffff;">
                                    {{ __('weekly.mail.title') }}
                                </h1>
                                <p style="margin:4px 0 0; font-size:14px; color:#e0e7ff;">{{ $weekLabel }}</p>
                            </td>
                        </tr>

                        {{-- RESULTADO --}}
                        <tr>
                            <td style="padding:28px 28px 8px;">
                                <p class="tf-muted" style="margin:0 0 4px; font-size:13px; color:#6b7280;">
                                    {{ __('weekly.mail.hello', ['name' => $user->name]) }}
                                </p>

                                <p style="margin:0; font-size:34px; font-weight:800; color:{{ $summary['pnl'] >= 0 ? '#059669' : '#dc2626' }};">
                                    {{ $summary['pnl'] >= 0 ? '+' : '' }}{{ $money($summary['pnl']) }} $
                                </p>

                                @php
                                    $diff = $summary['pnl'] - $summary['previous']['pnl'];
                                @endphp
                                <p class="tf-muted" style="margin:4px 0 0; font-size:13px; color:#6b7280;">
                                    {{ __('weekly.mail.vs_previous', [
                                        'sign' => $diff >= 0 ? '+' : '',
                                        'amount' => $money($diff),
                                    ]) }}
                                </p>
                            </td>
                        </tr>

                        {{-- KPIs --}}
                        <tr>
                            <td style="padding:20px 28px 4px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        @php
                                            $kpis = [
                                                ['label' => __('weekly.kpi.trades'), 'value' => $summary['trades']],
                                                ['label' => __('weekly.kpi.win_rate'), 'value' => number_format($summary['win_rate'], 1) . '%'],
                                                ['label' => __('weekly.kpi.journal_days'), 'value' => $summary['journal_days']],
                                            ];
                                        @endphp

                                        @foreach ($kpis as $kpi)
                                            <td width="33%" align="center" style="padding:12px 6px; border:1px solid #e5e7eb; border-radius:12px;" class="tf-rule">
                                                <p class="tf-text" style="margin:0; font-size:20px; font-weight:800; color:#111827;">{{ $kpi['value'] }}</p>
                                                <p class="tf-muted" style="margin:2px 0 0; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#6b7280;">{{ $kpi['label'] }}</p>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        {{-- MEJOR Y PEOR --}}
                        @if ($summary['best_trade'] && $summary['worst_trade'])
                            <tr>
                                <td style="padding:20px 28px 0;">
                                    <h2 class="tf-text" style="margin:0 0 10px; font-size:15px; color:#111827;">{{ __('weekly.mail.extremes') }}</h2>

                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:10px 12px; background-color:#ecfdf5; border-radius:10px;">
                                                <span style="font-size:13px; color:#065f46;">
                                                    {{ __('weekly.mail.best', [
                                                        'symbol' => $summary['best_trade']['symbol'],
                                                        'amount' => $money($summary['best_trade']['pnl']),
                                                    ]) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr><td style="height:8px; line-height:8px;">&nbsp;</td></tr>
                                        <tr>
                                            <td style="padding:10px 12px; background-color:#fef2f2; border-radius:10px;">
                                                <span style="font-size:13px; color:#991b1b;">
                                                    {{ __('weekly.mail.worst', [
                                                        'symbol' => $summary['worst_trade']['symbol'],
                                                        'amount' => $money($summary['worst_trade']['pnl']),
                                                    ]) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif

                        {{-- ERRORES REPETIDOS --}}
                        @if (count($summary['mistakes']) > 0)
                            <tr>
                                <td style="padding:24px 28px 0;">
                                    <h2 class="tf-text" style="margin:0 0 10px; font-size:15px; color:#111827;">{{ __('weekly.mail.mistakes') }}</h2>

                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        @foreach ($summary['mistakes'] as $mistake)
                                            <tr>
                                                <td class="tf-rule" style="padding:10px 0; border-bottom:1px solid #f3f4f6;">
                                                    <span class="tf-text" style="font-size:14px; font-weight:600; color:#111827;">{{ $mistake['name'] }}</span>
                                                    <span class="tf-muted" style="font-size:13px; color:#6b7280;">
                                                        · {{ trans_choice('weekly.mail.times', $mistake['times'], ['times' => $mistake['times']]) }}
                                                    </span>
                                                    <span style="float:right; font-size:13px; font-weight:700; color:{{ $mistake['pnl'] >= 0 ? '#059669' : '#dc2626' }};">
                                                        {{ $mistake['pnl'] >= 0 ? '+' : '' }}{{ $money($mistake['pnl']) }} $
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        @endif

                        {{-- REGLAS ROTAS --}}
                        @if (count($summary['broken_rules']) > 0)
                            <tr>
                                <td style="padding:24px 28px 0;">
                                    <h2 class="tf-text" style="margin:0 0 10px; font-size:15px; color:#111827;">{{ __('weekly.mail.broken_rules') }}</h2>

                                    @foreach ($summary['broken_rules'] as $rule)
                                        <p class="tf-muted" style="margin:0 0 6px; font-size:13px; color:#6b7280;">
                                            &bull; {{ $rule['text'] }}
                                            <span style="color:#b45309;">({{ trans_choice('weekly.mail.days', $rule['times'], ['days' => $rule['times']]) }})</span>
                                        </p>
                                    @endforeach
                                </td>
                            </tr>
                        @endif

                        {{-- MEJOR FRANJA --}}
                        @if ($summary['best_hour'])
                            <tr>
                                <td style="padding:24px 28px 0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:14px 16px; background-color:#eef2ff; border-radius:12px;">
                                                <span style="font-size:13px; color:#3730a3;">
                                                    {{ __('weekly.mail.best_hour', [
                                                        'hour' => sprintf('%02d:00', $summary['best_hour']['hour']),
                                                        'amount' => $money($summary['best_hour']['pnl']),
                                                        'trades' => $summary['best_hour']['trades'],
                                                    ]) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif

                        {{-- LLAMADA A LA REVISIÓN --}}
                        <tr>
                            <td align="center" style="padding:28px;">
                                <a href="{{ $reviewUrl }}"
                                   style="display:inline-block; padding:14px 28px; background-color:#4f46e5; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none; border-radius:12px;">
                                    {{ __('weekly.mail.cta') }}
                                </a>
                                <p class="tf-muted" style="margin:12px 0 0; font-size:12px; color:#6b7280;">{{ __('weekly.mail.cta_hint') }}</p>
                            </td>
                        </tr>

                        {{-- PIE --}}
                        <tr>
                            <td class="tf-rule" style="padding:18px 28px 24px; border-top:1px solid #e5e7eb;">
                                <p class="tf-muted" style="margin:0; font-size:11px; line-height:1.6; color:#9ca3af;">
                                    {{ __('weekly.mail.footer') }}<br>
                                    <a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">{{ __('weekly.mail.unsubscribe') }}</a>
                                </p>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>
    </body>

</html>
