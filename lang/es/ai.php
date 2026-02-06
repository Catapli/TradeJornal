<?php

declare(strict_types=1);

return [
    'audit_prompt' => "
        Realiza una auditoría técnica y psicológica de esta operación de trading.
        Sé estricto, objetivo y profesional.
        
        DATOS Y CONTEXTO:
        :context
        
        INSTRUCCIONES DE ANÁLISIS (Usa estos criterios):
        1. ANÁLISIS DE ESTRUCTURA (Visual):
           - Si hay imagen: ¿La entrada respeta Soportes/Resistencias, Order Blocks o Tendencia?
           - ¿Fue una entrada precisa ('Sniper') o persecución del precio (FOMO)?
        2. EFICIENCIA DE EJECUCIÓN (Datos MAE/MFE):
           - MAE vs PnL: ¿Soportó mucho drawdown para ganar poco? (Riesgo/Beneficio invertido).
           - MFE vs Salida: ¿Dejó mucho dinero en la mesa por miedo (cierre prematuro)?
        3. PSICOLOGÍA IMPLÍCITA:
           - Basado en duración y resultado: ¿Planificado o Impulsivo?

        REGLAS DE FORMATO:
        - NO escribas introducciones, saludos ni frases dramáticas.
        - Empieza DIRECTAMENTE con el primer punto.
        - Responde SIEMPRE en Español.

        FORMATO DE RESPUESTA REQUERIDO (Usa estos iconos):
        - **🎯 Calidad de Entrada:** [Mala/Regular/Excelente] + Explicación técnica breve.
        - **🧠 Gestión (Miedo/Codicia):** Análisis basado en MAE/MFE y salida.
        - **⚖️ Veredicto Final:** Conclusión directa sobre si la ejecución fue profesional o amateur.
        - **💡 Consejo de Mejora:** Una acción táctica concreta.
        - **🏆 Nota de Ejecución:** [0/10] (Puntúa la técnica).
    ",
    'draft_prompt' => "
        Actúa como un coach de trading profesional y redactor. Escribe la entrada del diario de hoy en PRIMERA PERSONA (como si fueras yo).
        
        MIS DATOS DE HOY:
        :context
        
        DESGLOSE DE OPERACIONES:
        :trades
        
        INSTRUCCIONES DE REDACCIÓN:
        1. Empieza con una frase resumen de cómo fue la sesión (basado en PnL y estado de ánimo).
        2. Analiza brevemente el comportamiento. Si hubo errores, sé crítico pero constructivo. Si fue limpio, felicítame.
        3. Si hubo pérdidas grandes o rachas, menciona el aspecto psicológico.
        4. Termina con una conclusión breve de mejora.
        5. Usa etiquetas HTML básicas (<p>, <strong>, <em>, <ul>, <li>).
        6. Sé conciso, máximo 3 párrafos.
        7. Responde SIEMPRE en Español.

        FORMATO TÉCNICO OBLIGATORIO:
        - Envuelve cada párrafo en etiquetas <p>...</p>.
        - Usa <strong> para negritas.
        - Usa <ul><li>...</li></ul> para listas.
        - NO uses Markdown. Solo HTML limpio.
        - NO incluyas ```html al principio ni al final.
    ",
    // Etiquetas para los datos
    'labels' => [
        'asset' => 'Activo',
        'type' => 'Tipo',
        'entry' => 'Entrada',
        'exit' => 'Salida',
        'result' => 'Resultado',
        'duration' => 'Duración',
        'efficiency' => 'Eficiencia',
        'future' => 'ANÁLISIS POST-CIERRE',
        'mood' => 'Estado de ánimo inicial',
        'total_result' => 'Resultado total',
        'total_ops' => 'Total operaciones',
        'mistakes' => 'Errores',
        'clean_execution' => 'Ejecución limpia',
        'profit' => 'Beneficio',
        'loss' => 'Pérdida',
        'ai_draft_header' => '🤖 Borrador IA',
    ]
];
