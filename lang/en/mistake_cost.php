<?php

return [

    // ── Titular (portada y Laboratorio) ──────────────────────────────────────
    'title' => 'What your mistakes have cost you',
    'headline_cost' => 'Your mistakes have cost you :amount',
    'headline_gain' => 'Your flagged trades have made you :amount',
    'headline_neutral' => 'Your flagged mistakes have not cost you anything yet',
    'without_them' => 'Without them you would be at :amount instead of :real.',
    'no_marked' => 'You have not flagged any mistake in this period yet.',
    'no_trades' => 'No trades in this period.',

    // ── Cobertura ────────────────────────────────────────────────────────────
    'coverage' => 'Based on :reviewed of :total reviewed trades (:percent %).',
    'coverage_short' => ':reviewed of :total reviewed',
    'coverage_warning' => 'The more you review, the closer this number gets to the truth.',

    // ── Desglose ─────────────────────────────────────────────────────────────
    'by_mistake' => 'By mistake',
    'by_slot' => 'By time of day',
    'cost_column' => 'Cost',
    'count_column' => 'Trades',
    'trades_count' => '{1} :count trade|[2,*] :count trades',
    'avg_column' => 'Average',
    'overlap_note' => 'A trade can carry several mistakes, so these figures do not add up to the total.',
    'gain_label' => 'in your favour',

    'slots' => [
        'early' => 'Early hours (00-07)',
        'morning' => 'Morning (08-12)',
        'afternoon' => 'Afternoon (13-17)',
        'evening' => 'Evening (18-23)',
    ],

    // ── Escenario del Laboratorio ────────────────────────────────────────────
    'scenario_title' => 'Remove trades with these mistakes',
    'scenario_hint' => 'Tick one or more to see your curve without those trades.',
    'scenario_none' => 'Flag mistakes on your trades to be able to use this scenario.',

    // ── Cola de repaso ───────────────────────────────────────────────────────
    'review' => [
        'title' => 'Losers left to review',
        'lead' => 'Flag what happened in each one. What you do not review does not count towards the number above.',
        'empty' => 'No losing trades left to review. Well done.',
        'pending' => '{1} You have :count losing trade left to review|[2,*] You have :count losing trades left to review',
        'open' => 'Review now',
        'open_trade' => 'Open trade',
        'clean' => 'No mistakes',
        'clean_hint' => 'Takes it out of the queue without making anything up: you looked and it was clean.',
        'save' => 'Save and next',
        'saved' => 'Trade reviewed.',
        'marked_clean' => 'Marked as reviewed, no mistakes.',
        'no_mistakes_yet' => 'You have no mistakes defined yet. Create them from a trade detail.',
        'showing' => 'Showing :count of :total.',
        'load_more' => 'Show more',
    ],
];
