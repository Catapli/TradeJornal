<?php

declare(strict_types=1);

return [

    'title' => 'Mentor',
    'lead' => 'What keeps repeating over your last :months months, and one single thing to change this month.',

    // ── Accumulated profile ──────────────────────────────────────────────────
    'profile_title' => 'Your profile',
    'coverage' => ':reviewed of :trades trades reviewed (:coverage%)',
    'coverage_hint' => 'The profile can only see what you have tagged. The less you review, the less it knows.',

    'not_enough_title' => 'Not enough yet to talk about patterns',
    'not_enough_text' => 'You have :marks mistake tags and :min are needed. :missing to go.',
    'not_enough_why' => 'With four tags, "your recurring mistake" is a coincidence with a name. And this is where the goal you commit to for a whole month comes from.',
    'not_enough_cta' => 'Go to the review queue',

    'mistake_count' => ':count times',
    'mistake_cost' => 'has cost you :amount',
    'this_month' => 'This month: :count',
    'trend_down' => 'Improving',
    'trend_up' => 'Getting worse',
    'trend_flat' => 'Unchanged',
    'trend_hint' => 'Compared per trade, not by raw count: a month with half the trades has fewer tags without you having improved.',

    // ── Goal of the month ────────────────────────────────────────────────────
    'goal_title' => 'This month goal',
    'goal_lead' => 'One single thing. Five changes at once are none.',

    'goal' => [
        'statement' => 'Go from :baseline down to :target trades with ":name".',
        'propose_intro' => 'Last month you made this mistake :baseline times across :sample trades.',
        'set' => 'Set this goal',
        'confirm_title' => 'Are you committing?',
        'confirm_text' => 'Once set it cannot be changed or deleted until the month ends. That is what makes it worth something.',
        'confirm_yes' => 'Yes, this is my goal',
        'confirm_no' => 'Not now',
        'saved' => 'Goal of the month set. See you on the 1st.',
        'none_title' => 'No goal to propose this month',
        'none_text' => 'It takes a previous month with at least :min trades and a mistake repeated in it. Without that, any figure would be made up.',
    ],

    'progress_so_far' => 'You are at :count of :target',
    'progress_days' => ':days days left',
    'progress_last_day' => 'Last day of the month',
    'progress_ok' => 'On track',
    'progress_blown' => 'Goal broken: you are at :count and the limit was :target',
    'progress_blown_hint' => 'Said now, not on the 30th. Finding out at month end about something that broke on the 4th fixes nothing.',

    // ── History ──────────────────────────────────────────────────────────────
    'history_title' => 'Previous months',
    'history_empty' => 'You have not closed any month yet.',
    'history_achieved' => 'Met',
    'history_missed' => 'Missed',
    'history_result' => ':result of :target',
    'history_sample' => 'over :sample trades',
];
