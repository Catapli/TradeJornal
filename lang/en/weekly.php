<?php

declare(strict_types=1);

return [

    // ─────────────────────────────────────────────────────────────
    // Sunday email (R1)
    // ─────────────────────────────────────────────────────────────
    'mail' => [
        'subject' => 'Your week on TradeForge · :week',
        'preheader' => 'A summary of your :trades trades this week.',
        'title' => 'Your week in review',
        'hello' => 'Hi :name. Here is what the week left behind:',
        'vs_previous' => ':sign:amount $ compared with last week.',
        'extremes' => 'Best and worst',
        'best' => 'Best trade: :symbol, :amount $.',
        'worst' => 'Worst trade: :symbol, :amount $.',
        'mistakes' => 'Mistakes you repeated',
        'times' => '{1} once|[2,*] :times times',
        'days' => '{1} 1 day|[2,*] :days days',
        'broken_rules' => 'Rules you skipped',
        'best_hour' => 'Your best time slot was :hour: :amount $ across :trades trades.',
        'cta' => 'Start the weekly review',
        'cta_hint' => 'Six trades, three questions each. Under ten minutes.',
        'footer' => 'You are getting this email because the weekly summary is switched on in TradeForge.',
        'unsubscribe' => 'Stop receiving it',
    ],

    'kpi' => [
        'result' => 'Result',
        'trades' => 'Trades',
        'win_rate' => 'Win rate',
        'journal_days' => 'Journal days',
        'discipline' => 'Discipline',
    ],

    // ─────────────────────────────────────────────────────────────
    // Guided weekly review (R2)
    // ─────────────────────────────────────────────────────────────
    'review' => [
        'title' => 'Weekly review',
        'lead' => 'Your three best and three worst trades of the week, with the same questions every time.',
        'previous' => 'Previous week',
        'next' => 'Next week',
        'selection' => 'Picked by result: 3 best and 3 worst.',
        'progress' => ':done of :total answered.',
        'done_on' => 'Review closed on :date.',
        'open_trade' => 'View trade',
        'q_plan' => 'Was it in your plan?',
        'plan_yes' => 'Yes',
        'plan_partly' => 'Partly',
        'plan_no' => 'No',
        'q_trigger' => 'What made you enter?',
        'q_trigger_hint' => 'The setup, the news, the impulse…',
        'q_change' => 'What would you do differently?',
        'q_change_hint' => 'One thing, and be specific.',
        'takeaway' => 'This week in one line',
        'takeaway_hint' => 'Something you can read again on Monday morning.',
        'complete' => 'Close the review',
        'save_draft' => 'Save draft',
        'saved' => 'Draft saved.',
        'completed' => 'Review closed. See you next week.',
        'incomplete' => 'Answer the first question of every trade before closing it.',
        'empty_title' => 'No trades that week',
        'empty_text' => 'With no closed trades there is nothing to review. Use the arrows to change week.',
        'history' => 'Previous weeks',
        'col_week' => 'Week',
        'open' => 'Open',
    ],

    // ─────────────────────────────────────────────────────────────
    // Preferences (profile)
    // ─────────────────────────────────────────────────────────────
    'settings' => [
        'title' => 'Weekly summary by email',
        'lead' => 'One email on Sunday with what the week left behind, and a link to the guided review.',
        'toggle' => 'Send me the weekly summary',
        'toggle_hint' => 'It goes out on Sundays at :hour in your local time. You can unsubscribe whenever you want, straight from the email too.',
        'timezone' => 'Time zone',
        'locale' => 'Language',
        'locales' => [
            'es' => 'Spanish',
            'en' => 'English',
        ],
        'save' => 'Save preferences',
        'saved' => 'Preferences saved.',
    ],

    'unsubscribe' => [
        'title' => 'You will not get the weekly summary any more',
        'lead' => 'We have unsubscribed :email from the Sunday email. You can switch it back on from your profile whenever you like.',
        'back' => 'Go to the dashboard',
        'resubscribe' => 'Change my preferences',
    ],

];
