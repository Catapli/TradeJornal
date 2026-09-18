<?php

declare(strict_types=1);

/**
 * Textos de la landing pública y de la página de precios pública.
 *
 * Regla: aquí solo se promete lo que el producto hace hoy. Si una funcionalidad
 * está en el ROADMAP pero no implementada, no aparece en este fichero.
 */
return [

    // ─────────────────────────────────────────────────────────────
    // Navegación
    // ─────────────────────────────────────────────────────────────
    'nav' => [
        'modules' => 'Módulos',
        'propfirm' => 'Prop firms',
        'pricing' => 'Precios',
        'demo' => 'Ver la demo',
        'login' => 'Entrar',
        'register' => 'Empezar gratis',
        'menu' => 'Abrir menú',
    ],

    // ─────────────────────────────────────────────────────────────
    // Héroe
    // ─────────────────────────────────────────────────────────────
    'hero' => [
        'badge' => 'Diario de trading para traders de prop firm',
        'title' => 'Pasa el challenge por método, no por suerte.',
        'subtitle' => 'TradeForge registra tus operaciones, vigila el drawdown de tu programa y te dice exactamente qué error te está costando el dinero. Con auditoría por IA de cada operación.',
        'cta' => 'Crear cuenta gratis',
        'cta_demo' => 'Entrar en la demo',
        'trust' => 'Sin tarjeta · Sincronización MT4/MT5 · Español e inglés',
    ],

    // ─────────────────────────────────────────────────────────────
    // Problema
    // ─────────────────────────────────────────────────────────────
    'problem' => [
        'eyebrow' => 'El problema',
        'title' => 'No fallas por análisis. Fallas por repetición.',
        'lead' => 'La mayoría de cuentas no se queman por una mala lectura del mercado, sino por el mismo puñado de errores repetidos cien veces sin que nadie los cuente.',
        'items' => [
            [
                'title' => 'No sabes cuánto te cuesta cada error',
                'text' => 'Mover el stop, promediar a la baja, entrar por FOMO. Los reconoces al hacerlos, pero nunca has visto la factura sumada al final del mes.',
            ],
            [
                'title' => 'El objetivo del programa vive en una hoja aparte',
                'text' => 'Drawdown diario, drawdown total, objetivo de beneficio, días mínimos. Cuatro reglas que deciden si cobras, calculadas a mano en una hoja de cálculo.',
            ],
            [
                'title' => 'Tu diario se queda en buenas intenciones',
                'text' => 'Escribes tres días seguidos, lo dejas la primera semana mala, y cuando quieres revisar el año no hay nada que revisar.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // Módulos
    // ─────────────────────────────────────────────────────────────
    'modules' => [
        'eyebrow' => 'Qué hay dentro',
        'title' => 'Seis módulos que trabajan sobre los mismos datos',
        'lead' => 'Una operación entra una vez y aparece en todos: en el calendario, en el diario del día, en las estadísticas de su estrategia y en el informe de errores.',
        'items' => [
            'dashboard' => [
                'title' => 'Panel y calendario de P&L',
                'text' => 'Curva de balance, calendario mensual con el resultado de cada día, mapa de calor por franja horaria y comparativa entre periodos. Todo filtrable por cuenta y por fechas.',
            ],
            'journal' => [
                'title' => 'Diario diario',
                'text' => 'Ritual pre-mercado con estado de ánimo y objetivos del día, tus reglas maestras en un checklist y una puntuación de disciplina que sale de los errores que te has marcado.',
            ],
            'session' => [
                'title' => 'Sesión en vivo',
                'text' => 'Abres la jornada declarando cuenta, estrategia y estado de ánimo. Vas marcando reglas y anotando cómo te sientes mientras operas, no seis horas después.',
            ],
            'lab' => [
                'title' => 'Laboratorio',
                'text' => 'Escenarios sobre tu histórico real: sólo largos, sin tu peor operación, con un tope de operaciones al día, con stop y objetivo fijos. La curva se recalcula delante de ti.',
            ],
            'playbook' => [
                'title' => 'Playbook',
                'text' => 'Cada estrategia con sus reglas, sus confluencias y sus estadísticas propias. Descubre cuál te da de comer y cuál sólo te entretiene.',
            ],
            'backtesting' => [
                'title' => 'Backtesting',
                'text' => 'Registra operaciones de backtest aparte de las reales, con su propia analítica y auditoría por IA antes de arriesgar un euro.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // Prop firms
    // ─────────────────────────────────────────────────────────────
    'propfirm' => [
        'eyebrow' => 'Hecho para challenges',
        'title' => 'Las reglas de tu programa, dentro del producto',
        'lead' => 'TradeForge no trata tu challenge como una cuenta más. Los objetivos y los límites del programa están modelados de verdad: eliges firma, tamaño y fase, y el panel vigila las cuatro reglas que deciden si pasas.',
        'rules' => [
            'daily_dd' => ['title' => 'Drawdown diario',  'text' => 'Cuánto te queda hoy antes de violar el límite.'],
            'max_dd' => ['title' => 'Drawdown total',   'text' => 'Distancia real al suelo de la cuenta, no al balance inicial.'],
            'target' => ['title' => 'Objetivo de beneficio', 'text' => 'Lo que falta para superar la fase, en dinero y en porcentaje.'],
            'min_days' => ['title' => 'Días mínimos',     'text' => 'Días operados que cuentan de verdad, según tu programa.'],
        ],
        'note' => 'Catálogo de firmas y programas mantenido desde la propia aplicación.',
    ],

    // ─────────────────────────────────────────────────────────────
    // IA
    // ─────────────────────────────────────────────────────────────
    'ai' => [
        'eyebrow' => 'Auditor con IA',
        'title' => 'Una segunda opinión que no te da la razón',
        'lead' => 'No es un chat. Es un auditor con criterio fijo que revisa la calidad de la entrada, la gestión del miedo y la codicia a partir de tus MAE y MFE, y pone nota. Disponible por operación, por día, por sesión y por estrategia.',
        'sample' => [
            'label' => 'Ejemplo de salida',
            'entry' => 'Calidad de entrada: regular. Largo abierto al 78 % del rango de las últimas 50 velas, con poco recorrido hasta el techo.',
            'mgmt' => 'Gestión: soportaste un MAE de −41 puntos para cerrar en +12. El MFE llegó a +58: cerraste en el 20 % del movimiento disponible.',
            'verdict' => 'Veredicto: ejecución amateur. Entrada perseguida y salida por miedo.',
            'score' => 'Nota de ejecución: 4/10',
        ],
        'note' => 'Créditos diarios según plan. El análisis queda guardado con la operación.',
    ],

    // ─────────────────────────────────────────────────────────────
    // Errores
    // ─────────────────────────────────────────────────────────────
    'mistakes' => [
        'eyebrow' => 'Disciplina medible',
        'title' => 'Quince errores tipificados, con peso',
        'lead' => 'Marcas lo que ha pasado en cada operación y el sistema hace la cuenta. Los errores graves pesan el triple que los técnicos, así que tu puntuación de disciplina refleja lo que de verdad te hace daño.',
        'grave' => 'Graves',
        'medium' => 'Medios',
        'light' => 'Técnicos',
    ],

    // ─────────────────────────────────────────────────────────────
    // Demo
    // ─────────────────────────────────────────────────────────────
    'demo' => [
        'eyebrow' => 'Pruébalo antes de registrarte',
        'title' => 'Entra en una cuenta con seis meses de historial',
        'lead' => 'Un challenge en curso, una cuenta fondeada y una quemada, con operaciones reales de ejemplo, diario escrito y errores marcados. Se navega entero, sin registro y sin poder romper nada.',
        'cta' => 'Entrar en la demo',
        'note' => 'Modo lectura: en la demo no se guarda ningún cambio.',
        'banner' => 'Estás viendo la demo de TradeForge con datos de ejemplo. No se guarda ningún cambio.',
        'banner_cta' => 'Crear mi cuenta gratis',
        'banner_exit' => 'Salir de la demo',
        'blocked' => 'En la demo no se pueden guardar cambios. Crea tu cuenta gratis para usarlo de verdad.',
    ],

    // ─────────────────────────────────────────────────────────────
    // Precios
    // ─────────────────────────────────────────────────────────────
    'pricing' => [
        'eyebrow' => 'Precios',
        'title' => 'Empieza gratis. Pasa a PRO cuando el diario sea un hábito.',
        'lead' => 'Sin permanencia. Puedes cancelar cuando quieras y tus datos siguen siendo tuyos.',
        'monthly' => 'Mensual',
        'yearly' => 'Anual',
        'per_month' => '/mes',
        'per_year' => '/año',
        'save' => 'Dos meses gratis',
        'current' => 'Tu plan actual',
        'free_cta' => 'Empezar gratis',
        'pro_cta' => 'Pasar a PRO',
        'login_cta' => 'Entrar para suscribirte',
        'popular' => 'Recomendado',
        'trial_badge' => ':days días gratis al registrarte, sin tarjeta',
        'trial_note' => 'Al crear la cuenta empiezas con :days días de PRO. No pedimos tarjeta y, cuando terminan, pasas a Free sin perder nada de lo registrado.',
        'trialing' => 'Estás de prueba · :days días',
        'unavailable' => 'La suscripción no está disponible ahora mismo. Inténtalo en unos minutos.',

        'free' => [
            'name' => 'Free',
            'price' => '0 €',
            'claim' => 'Para empezar a registrar y ver si el hábito cuaja.',
        ],
        'pro' => [
            'name' => 'PRO',
            'claim' => 'Todo el producto, con la sincronización automática.',
        ],

        'compare' => 'Comparativa',
        'features' => [
            ['label' => 'Cuentas de trading',            'free' => 'Hasta 3',        'pro' => 'Ilimitadas'],
            ['label' => 'Registro manual de operaciones', 'free' => true,             'pro' => true],
            ['label' => 'Panel, calendario y curva',      'free' => true,             'pro' => true],
            ['label' => 'Objetivos de prop firm',         'free' => true,             'pro' => true],
            ['label' => 'Errores y disciplina',           'free' => true,             'pro' => true],
            ['label' => 'MAE / MFE y eficiencia',         'free' => true,             'pro' => true],
            ['label' => 'Sincronización automática MT4/MT5', 'free' => false,         'pro' => true],
            ['label' => 'Estrategias en las operaciones', 'free' => false,            'pro' => true],
            ['label' => 'Diario diario',                  'free' => false,            'pro' => true],
            ['label' => 'Sesión en vivo e historial',     'free' => false,            'pro' => true],
            ['label' => 'Laboratorio de escenarios',      'free' => false,            'pro' => true],
            ['label' => 'Playbook de estrategias',        'free' => false,            'pro' => true],
            ['label' => 'Backtesting',                    'free' => false,            'pro' => true],
            ['label' => 'Análisis con IA al día',         'free' => '3',              'pro' => '15'],
            ['label' => 'Soporte',                        'free' => 'Por email',      'pro' => 'Prioritario'],
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // FAQ
    // ─────────────────────────────────────────────────────────────
    'faq' => [
        'eyebrow' => 'Dudas razonables',
        'title' => 'Preguntas frecuentes',
        'items' => [
            [
                'q' => '¿Cómo entran mis operaciones?',
                'a' => 'De dos formas. A mano, desde la propia aplicación, disponible en el plan gratuito. O automáticamente: con el plan PRO instalas un pequeño ejecutable junto a tu terminal MetaTrader y las operaciones se sincronizan solas, incluidas las velas del gráfico de cada entrada.',
            ],
            [
                'q' => '¿Necesito una cuenta de prop firm?',
                'a' => 'No. TradeForge funciona igual con una cuenta personal o una demo. Lo que aporta de más, si operas un challenge, es que las reglas del programa están modeladas dentro y no tienes que vigilarlas por tu cuenta.',
            ],
            [
                'q' => '¿Qué plataformas soportáis?',
                'a' => 'La sincronización automática es hoy para MetaTrader 4 y 5. Desde cualquier otra plataforma puedes registrar las operaciones manualmente. Estamos trabajando en la importación por fichero y en más orígenes de datos.',
            ],
            [
                'q' => '¿Le dais mis datos a alguien?',
                'a' => 'No. Tus operaciones y tus notas son tuyas. Las credenciales de la plataforma se guardan cifradas y sólo las usa tu propio terminal para sincronizar.',
            ],
            [
                'q' => '¿Qué pasa si cancelo PRO?',
                'a' => 'No pierdes nada de lo registrado. La cuenta vuelve al plan gratuito y los módulos PRO dejan de estar disponibles, pero tus operaciones, notas y estadísticas siguen ahí esperándote.',
            ],
            [
                'q' => '¿Está en español?',
                'a' => 'Sí, la aplicación entera está en español e inglés, incluido el auditor con IA, y se cambia de idioma en cualquier momento.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // Cierre
    // ─────────────────────────────────────────────────────────────
    'cta' => [
        'title' => 'El próximo challenge empieza con datos, no con promesas.',
        'lead' => 'Crea la cuenta gratis y registra tu primera operación en dos minutos.',
        'primary' => 'Crear cuenta gratis',
        'secondary' => 'Ver la demo antes',
    ],

    'footer' => [
        'tagline' => 'Diario de trading para traders de prop firm.',
        'product' => 'Producto',
        'legal' => 'Legal',
        'terms' => 'Términos de servicio',
        'privacy' => 'Política de privacidad',
        'rights' => 'Todos los derechos reservados.',
    ],

    // ─────────────────────────────────────────────────────────────
    // Muro PRO (pantallas bloqueadas dentro de la app)
    // ─────────────────────────────────────────────────────────────
    'gate' => [
        'badge' => 'Incluido en PRO',
        'title' => ':module con PRO',
        'lead' => 'Esto es :module funcionando con datos de ejemplo. Con PRO trabaja sobre tus operaciones reales.',
        'cta' => 'Ver planes',
        'demo' => 'Datos de ejemplo',
        'demo_cta' => 'Verlo en la demo',
        'tag' => 'PRO',
        'locked' => 'Disponible en PRO',
    ],
];
