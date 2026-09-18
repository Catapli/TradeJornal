<?php

declare(strict_types=1);

return [

    'title' => 'Streaks and discipline',
    'subtitle' => 'What keeps an account alive is not one good trade, it is not slipping twice in a row.',
    'write_today' => "Write today's journal",

    'journal' => [
        'label' => 'Journal written',
        'unit' => '{0} days|{1} day|[2,*] days',
        'tooltip' => 'Consecutive weekdays with something written in the journal. Weekends neither count nor break it.',
    ],

    'clean' => [
        'label' => 'No serious mistakes',
        'unit' => '{0} days|{1} day|[2,*] days',
        'tooltip' => 'Consecutive trading days with no trade tagged with a serious mistake. Days without trading neither add nor subtract.',
    ],

    'plan' => [
        'label' => 'Plan followed',
        'unit' => '{0} weeks|{1} week|[2,*] weeks',
        'tooltip' => 'Finished weeks where you ticked every daily objective and made no serious mistakes. The current week does not count until it ends.',
    ],

    'empty' => [
        'label' => 'Start your streak',
        'tooltip' => "Write today's journal and start counting.",
    ],

    'legend' => [
        'journal' => 'Journal written',
        'traded' => 'Traded, no journal',
        'severe' => 'Serious mistake',
        'weekend' => 'Weekend',
        'empty' => 'No activity',
    ],

];
