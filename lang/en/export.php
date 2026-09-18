<?php

declare(strict_types=1);

return [

    // ── CSV ────────────────────────────────────────────────────────────────
    'csv' => [
        'slug' => 'trades',
        'button' => 'Export CSV',
        'hint' => 'Downloads exactly the trades you are looking at, filters included.',
        'empty' => 'There are no trades to export with these filters.',
    ],

    'demo_notice' => 'TradeForge demo data: these are not real trades.',

    'columns' => [
        'ticket' => 'Ticket',
        'account' => 'Account',
        'asset' => 'Asset',
        'direction' => 'Direction',
        'entry_time' => 'Opened',
        'exit_time' => 'Closed',
        'entry_price' => 'Entry price',
        'exit_price' => 'Exit price',
        'size' => 'Size',
        'pnl' => 'P&L',
        'pnl_percentage' => 'P&L %',
        'duration' => 'Duration (min)',
        'strategy' => 'Strategy',
        'mistakes' => 'Mistakes',
        'mood' => 'Mood',
        'notes' => 'Notes',
    ],

    'direction' => [
        'long' => 'Long',
        'short' => 'Short',
    ],

    // ── Monthly PDF report ─────────────────────────────────────────────────
    'pdf' => [
        'slug' => 'report',
        'button' => 'Download report (PDF)',
        'card_title' => 'Monthly report',
        'card_lead' => 'A branded PDF for the month you pick: metrics, equity curve, calendar, what your mistakes cost and how well you kept your rules.',
        'account_label' => 'Account',
        'month_label' => 'Month',
        'all_accounts' => 'all accounts',
        'all_accounts_option' => 'All accounts',
        'empty' => 'No closed trades in :month. Pick another month.',
        'generating' => 'Generating report…',

        'title' => 'Monthly report',
        'generated_at' => 'Generated on :date',
        'for' => 'Prepared for :name',
        'demo_stamp' => 'DEMO',
        'demo_footer' => 'Report generated from the TradeForge demo, with sample data.',
        'footer' => 'TradeForge · trading journal for prop firms',

        'account' => [
            'phase' => 'Phase',
            'status' => 'Status',
            'balance' => 'Balance',
        ],

        'metrics' => [
            'title' => 'The month in numbers',
            'pnl' => 'Monthly P&L',
            'trades' => 'Trades',
            'win_rate' => 'Win rate',
            'profit_factor' => 'Profit factor',
            'expectancy' => 'Expectancy per trade',
            'max_drawdown' => 'Max drawdown',
            'avg_win' => 'Average win',
            'avg_loss' => 'Average loss',
            'best_trade' => 'Best trade',
            'worst_trade' => 'Worst trade',
            'win_days' => 'Green days',
            'loss_days' => 'Red days',
            'infinite' => 'no losses',
        ],

        'previous' => [
            'title' => 'Against :month',
            'none' => 'No trades last month: nothing to compare against.',
            'pnl' => 'P&L',
            'win_rate' => 'Win rate',
            'trades' => 'Trades',
        ],

        'equity' => [
            'title' => 'Equity curve for the month',
            'lead' => 'Cumulative day by day, by close date.',
            'empty' => 'No closed trades: there is no curve to draw.',
        ],

        'calendar' => [
            'title' => 'Month calendar',
            'lead' => 'P&L by close date. Blank days are days you did not trade.',
            'weekdays' => ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
        ],

        'mistakes' => [
            'title' => 'What your mistakes cost',
            'cost' => 'Cost this month',
            'without' => 'Without those trades you would have',
            'real' => 'What you actually have',
            'coverage' => 'Calculated over :reviewed of :total reviewed trades (:coverage %).',
            'pending' => ':count losing trades still unreviewed.',
            'column_mistake' => 'Mistake',
            'column_count' => 'Trades',
            'column_cost' => 'Cost',
            'column_avg' => 'Average cost',
            'overlap' => 'A trade with two mistakes counts in full for both, so the breakdown does not add up to the total.',
            'empty' => 'No trades tagged this month.',
        ],

        'rules' => [
            'title' => 'Your rules',
            'lead' => 'Active rules adopted from the Lab.',
            'column_rule' => 'Rule',
            'column_breaches' => 'Times you broke it',
            'column_rate' => 'Compliance',
            'unit_days' => ':breaches of :scope days',
            'unit_trades' => ':breaches of :scope trades',
            'manual' => 'You check this one',
            'empty' => 'You have not adopted any rule yet.',
            'global' => 'all accounts',
        ],
    ],
];
