<?php

declare(strict_types=1);

return [

    // ─────────────────────────────────────────────────────────────
    // Correo del domingo (R1)
    // ─────────────────────────────────────────────────────────────
    'mail' => [
        'subject' => 'Tu semana en TradeForge · :week',
        'preheader' => 'Resumen de tus :trades operaciones de la semana.',
        'title' => 'Resumen de la semana',
        'hello' => 'Hola, :name. Esto es lo que dejó la semana:',
        'vs_previous' => ':sign:amount $ respecto a la semana anterior.',
        'extremes' => 'Lo mejor y lo peor',
        'best' => 'Mejor operación: :symbol, :amount $.',
        'worst' => 'Peor operación: :symbol, :amount $.',
        'mistakes' => 'Errores que se repitieron',
        'times' => '{1} una vez|[2,*] :times veces',
        'days' => '{1} 1 día|[2,*] :days días',
        'broken_rules' => 'Reglas que te saltaste',
        'best_hour' => 'Tu mejor franja fue a las :hour: :amount $ en :trades operaciones.',
        'cta' => 'Hacer la revisión semanal',
        'cta_hint' => 'Seis operaciones, tres preguntas cada una. Menos de diez minutos.',
        'footer' => 'Recibes este correo porque tienes activado el resumen semanal en TradeForge.',
        'unsubscribe' => 'Dejar de recibirlo',
    ],

    'kpi' => [
        'result' => 'Resultado',
        'trades' => 'Operaciones',
        'win_rate' => 'Acierto',
        'journal_days' => 'Días de diario',
        'discipline' => 'Disciplina',
    ],

    // ─────────────────────────────────────────────────────────────
    // Revisión semanal guiada (R2)
    // ─────────────────────────────────────────────────────────────
    'review' => [
        'title' => 'Revisión semanal',
        'lead' => 'Tus tres mejores y tres peores operaciones de la semana, con las mismas preguntas cada vez.',
        'previous' => 'Semana anterior',
        'next' => 'Semana siguiente',
        'selection' => 'Elegidas por resultado: 3 mejores y 3 peores.',
        'progress' => 'Contestadas :done de :total.',
        'done_on' => 'Revisión cerrada el :date.',
        'open_trade' => 'Ver operación',
        'q_plan' => '¿Estaba en tu plan?',
        'plan_yes' => 'Sí',
        'plan_partly' => 'A medias',
        'plan_no' => 'No',
        'q_trigger' => '¿Qué te hizo entrar?',
        'q_trigger_hint' => 'El setup, la noticia, el impulso…',
        'q_change' => '¿Qué harías distinto?',
        'q_change_hint' => 'Una sola cosa, concreta.',
        'takeaway' => 'La conclusión de la semana',
        'takeaway_hint' => 'Una frase que puedas leer el lunes por la mañana.',
        'complete' => 'Cerrar la revisión',
        'save_draft' => 'Guardar borrador',
        'saved' => 'Borrador guardado.',
        'completed' => 'Revisión cerrada. Nos vemos la semana que viene.',
        'incomplete' => 'Contesta la primera pregunta de cada operación antes de cerrarla.',
        'empty_title' => 'Esa semana no tiene operaciones',
        'empty_text' => 'Sin operaciones cerradas no hay nada que revisar. Cambia de semana con las flechas.',
        'history' => 'Semanas anteriores',
        'col_week' => 'Semana',
        'open' => 'Ver',
    ],

    // ─────────────────────────────────────────────────────────────
    // Preferencias (perfil)
    // ─────────────────────────────────────────────────────────────
    'settings' => [
        'title' => 'Resumen semanal por correo',
        'lead' => 'Un correo el domingo con lo que dejó la semana y un enlace a la revisión guiada.',
        'toggle' => 'Quiero recibir el resumen semanal',
        'toggle_hint' => 'Sale los domingos a las :hour de tu hora local. Puedes darte de baja cuando quieras, también desde el propio correo.',
        'timezone' => 'Zona horaria',
        'locale' => 'Idioma',
        'locales' => [
            'es' => 'Español',
            'en' => 'Inglés',
        ],
        'save' => 'Guardar preferencias',
        'saved' => 'Preferencias guardadas.',
    ],

    'unsubscribe' => [
        'title' => 'Ya no recibirás el resumen semanal',
        'lead' => 'Hemos dado de baja a :email del correo del domingo. Puedes volver a activarlo cuando quieras desde tu perfil.',
        'back' => 'Ir al panel',
        'resubscribe' => 'Cambiar mis preferencias',
    ],

];
