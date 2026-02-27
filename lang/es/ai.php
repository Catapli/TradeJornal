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

    'session_prompt' => '
Realiza una auditoría de riesgo y comportamiento de la sesión de trading completa de hoy.
Sé estricto, objetivo y profesional.

DATOS DE LA SESIÓN (Cronológicos):
:trades_text

INSTRUCCIONES DE ANÁLISIS (Busca estos patrones):
1. CONTROL EMOCIONAL (Tilt): ¿Hay operaciones consecutivas rápidas tras una pérdida (Revenge Trading)?
2. GESTIÓN DE RIESGO: ¿Aumenta el lotaje tras perder (Martingala)? ¿Corta las ganancias rápido y deja correr las pérdidas?
3. DISCIPLINA: ¿Hay sobreoperativa (muchas operaciones mediocres) o selección de calidad?

REGLAS DE FORMATO:
- NO escribas introducciones, saludos ni frases dramáticas.
- Empieza DIRECTAMENTE con el primer punto del formato.
- Responde SIEMPRE en español.

FORMATO DE RESPUESTA REQUERIDO (Usa estos iconos):
- **📊 Resumen:** Una frase que defina el estado mental y técnico del trader hoy.
- **🚩 Alertas Detectadas:** Lista de errores graves (Tilt, Sobreoperativa, etc.). Si fue un día limpio, indica "Ninguna".
- **💡 Consejo para Mañana:** Una acción correctiva concreta.
- **🏆 Nota del Día:** [0/10] (Basado en la disciplina, no solo en el dinero ganado).
',

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
    'daily_tip' => "
                Actúa como un Psico-Trading Coach experto. Analiza estos trades buscando patrones destructivos.
            
            DATOS:
            :datos

            INSTRUCCIONES DE PRIORIDAD (Sigue este orden estricto):
            1. 🚨 PRIMERO busca SOBREOPERATIVA/TILT: Si ves múltiples operaciones (más de 3-4) en el mismo día o sesión con pérdidas, IGNORA la dirección (Long/Short) y ataca la cantidad. El problema es el volumen, no el setup.
            2. 🕒 SEGUNDO busca HORARIO: Si pierde siempre a la misma hora.
            3. 📉 TERCERO busca DIRECCIÓN: Solo si la conducta es disciplinada (pocos trades), mira si falla en Longs/Shorts.

            REGLAS DE RESPUESTA:
            - Dame UNA SOLA frase imperativa y dura.
            - Máximo 20 palabras.
            - Empieza con emoji.
            
            Ejemplos correctos:
            '🔥 Estás en racha destructiva: apaga el ordenador tras 2 pérdidas o quemarás la cuenta.' (Prioriza conducta)
            '🛑 Tu obsesión por operar la apertura de Nueva York te está costando cara; espera 30 minutos.' (Prioriza horario)
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
