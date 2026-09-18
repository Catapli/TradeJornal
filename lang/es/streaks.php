<?php

declare(strict_types=1);

return [

    'title' => 'Rachas y disciplina',
    'subtitle' => 'Lo que sostiene una cuenta no es una operación buena, es no fallar dos días seguidos.',
    'write_today' => 'Escribir el diario de hoy',

    'journal' => [
        'label' => 'Diario escrito',
        'unit' => '{0} días|{1} día|[2,*] días',
        'tooltip' => 'Días laborables seguidos con algo escrito en el diario. El fin de semana no cuenta ni la rompe.',
    ],

    'clean' => [
        'label' => 'Sin errores graves',
        'unit' => '{0} días|{1} día|[2,*] días',
        'tooltip' => 'Días operados seguidos sin ninguna operación marcada con un error grave. Los días sin operar no suman ni restan.',
    ],

    'plan' => [
        'label' => 'Plan cumplido',
        'unit' => '{0} semanas|{1} semana|[2,*] semanas',
        'tooltip' => 'Semanas cerradas en las que marcaste todos los objetivos del día y no hubo ningún error grave. La semana en curso no cuenta hasta que termina.',
    ],

    'empty' => [
        'label' => 'Empieza tu racha',
        'tooltip' => 'Escribe el diario de hoy y empieza a contar.',
    ],

    'legend' => [
        'journal' => 'Con diario',
        'traded' => 'Operado sin diario',
        'severe' => 'Con error grave',
        'weekend' => 'Fin de semana',
        'empty' => 'Sin actividad',
    ],

];
