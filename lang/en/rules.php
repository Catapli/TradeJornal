<?php

return [

    // ── Hallazgos del Laboratorio ────────────────────────────────────────────
    'findings' => [
        'title' => 'What your data is telling you',
        'lead' => 'Every line comes from your own trades and carries the figure and the sample. Turn it into a rule and you will see it before you trade.',
        'empty' => 'Nothing is clear enough yet to become a rule. At least :min trades are needed in the period you are looking at.',
        'sample' => 'across :count trades',
        'adopt' => 'Turn into a rule',
        'adopted' => 'Already one of your rules',
        'cost_label' => 'Costs you :amount',
    ],

    'finding' => [
        'mistake_cost' => ':name has cost you :amount across :count trades.',
        'mistake_cost_rule' => 'Before entering, check I am not committing: :name',

        'time_of_day' => 'Trading in the :slot slot you have lost :amount across :count trades.',
        'time_of_day_rule' => 'Only trade between :from and :to',
        'time_of_day_rule_soft' => 'Do not trade during :slot',

        'trades_per_day' => 'From your trade number :position onwards the day turns against you: :amount lost across :count trades.',
        'trades_per_day_rule' => 'At most :limit trades per day',

        'weekday' => 'On :weekday you have lost :amount across :count trades.',
        'weekday_rule' => 'Do not trade on :weekday',
    ],

    'weekdays' => [
        0 => 'Sundays',
        1 => 'Mondays',
        2 => 'Tuesdays',
        3 => 'Wednesdays',
        4 => 'Thursdays',
        5 => 'Fridays',
        6 => 'Saturdays',
    ],

    'slots' => [
        'early' => 'the early hours (00-07)',
        'morning' => 'the morning (08-12)',
        'afternoon' => 'the afternoon (13-17)',
        'evening' => 'the evening (18-23)',
    ],

    // ── Al adoptar ───────────────────────────────────────────────────────────
    'adopt' => [
        'title' => 'Turn into a rule',
        'scope' => 'What does it apply to?',
        'scope_account' => 'Only :name',
        'scope_one' => 'One specific account',
        'scope_all' => 'All my accounts',
        'scope_hint' => 'Rules for a single account are also written into its plan, so the live session traffic light watches them on its own.',
        'text_label' => 'The rule, in your own words',
        'confirm' => 'Adopt',
        'cancel' => 'Cancel',
        'saved' => 'Rule adopted. You will see it in your next session checklist.',
        'enforced' => 'Rule adopted and added to the account plan: the traffic light will watch it.',
        'no_account' => 'Pick an account or tick that it applies to all of them.',
    ],

    // ── Mis reglas ───────────────────────────────────────────────────────────
    'mine' => [
        'title' => 'My rules',
        'lead' => 'They came from a finding. If the data changes, they can be switched off without deleting them.',
        'empty' => 'You have not turned any finding into a rule yet.',
        'global' => 'All accounts',
        'origin' => 'Came from: :summary (:sample).',
        'origin_sample' => '{1} :count trade|[2,*] :count trades',
        'enforced' => 'Watched by the traffic light',
        'checklist_only' => 'Checklist only',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'delete' => 'Delete',
        'deleted' => 'Rule deleted.',
        'toggled_on' => 'Rule activated.',
        'toggled_off' => 'Rule deactivated.',
    ],

    // ── Sesión en vivo ───────────────────────────────────────────────────────
    'session' => [
        'my_rules' => 'My rules',
        'broken' => 'You are breaking one of your own rules',
        'broken_max_trades' => 'You are at :current trades and your cap is :limit.',
        'broken_time_window' => 'It is :now and your window is :from to :to.',
        'broken_weekday' => 'Today is :weekday, and you decided not to trade that day.',
    ],
];
