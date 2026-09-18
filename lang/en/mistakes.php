<?php

/**
 * Global mistake catalogue (mistakes.slug). User-created mistakes do not go
 * through here: they are shown with their literal name.
 */

return [

    // ── SEVERE ──────────────────────────────────────────────────────────────
    'revenge_trading' => [
        'name' => 'Revenge trading',
        'description' => 'You re-entered shortly after a loss to win it back, not because your setup appeared.',
    ],
    'no_stop_loss' => [
        'name' => 'No stop loss',
        'description' => 'You opened the trade without a defined stop, leaving the maximum loss uncapped.',
    ],
    'moved_stop_loss' => [
        'name' => 'Moved the stop loss',
        'description' => 'You pushed the stop away to avoid being taken out, widening the risk you accepted on entry.',
    ],
    'averaging_down' => [
        'name' => 'Averaging down',
        'description' => 'You added size to a losing position to lower the average price instead of accepting the mistake.',
    ],
    'excessive_risk' => [
        'name' => 'Excessive risk',
        'description' => 'You risked a larger share of the account than your risk management plan allows.',
    ],

    // ── MEDIUM ──────────────────────────────────────────────────────────────
    'fomo' => [
        'name' => 'FOMO',
        'description' => 'You entered late chasing a move already underway, out of fear of missing out.',
    ],
    'overtrading' => [
        'name' => 'Overtrading',
        'description' => 'You took more trades than your daily plan allows, lowering the quality of each entry.',
    ],
    'counter_trend' => [
        'name' => 'Counter trend',
        'description' => 'You traded against the dominant direction of the timeframe you use as reference.',
    ],
    'round_trip' => [
        'name' => 'Round trip',
        'description' => 'The trade was clearly in profit and you let it come all the way back into a loss.',
    ],
    'held_loser' => [
        'name' => 'Held the loser',
        'description' => 'You kept a deeply losing position hoping it would come back instead of closing per plan.',
    ],
    'no_setup' => [
        'name' => 'Entry without setup',
        'description' => 'Not every condition of your strategy was met: the entry was discretionary.',
    ],
    'news_trading' => [
        'name' => 'Trading the news',
        'description' => 'You held or opened a position through a high-impact release without it being part of the plan.',
    ],

    // ── MINOR / TECHNICAL ───────────────────────────────────────────────────
    'early_exit' => [
        'name' => 'Early exit',
        'description' => 'You closed before your target and captured only a small part of the favourable move.',
    ],
    'late_entry' => [
        'name' => 'Late entry',
        'description' => 'You executed late relative to the signal, worsening the average price and the risk/reward.',
    ],
    'wrong_size' => [
        'name' => 'Wrong position size',
        'description' => 'Position size did not match the stop distance nor the risk you had planned.',
    ],

];
