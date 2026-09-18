<?php

declare(strict_types=1);

/**
 * Public landing page and public pricing page copy.
 *
 * Rule: only claim what the product does today. Anything that lives in the
 * ROADMAP but is not implemented does not belong in this file.
 */
return [

    // ─────────────────────────────────────────────────────────────
    // Navigation
    // ─────────────────────────────────────────────────────────────
    'nav' => [
        'modules' => 'Modules',
        'propfirm' => 'Prop firms',
        'pricing' => 'Pricing',
        'demo' => 'See the demo',
        'login' => 'Log in',
        'register' => 'Start free',
        'menu' => 'Open menu',
    ],

    // ─────────────────────────────────────────────────────────────
    // Hero
    // ─────────────────────────────────────────────────────────────
    'hero' => [
        'badge' => 'Trading journal for prop firm traders',
        'title' => 'Pass the challenge on method, not on luck.',
        'subtitle' => 'TradeForge logs your trades, watches your program drawdown and tells you exactly which mistake is costing you money. With an AI audit of every trade.',
        'cta' => 'Create free account',
        'cta_demo' => 'Enter the demo',
        'trust' => 'No card required · MT4/MT5 sync · English and Spanish',
    ],

    // ─────────────────────────────────────────────────────────────
    // Problem
    // ─────────────────────────────────────────────────────────────
    'problem' => [
        'eyebrow' => 'The problem',
        'title' => "You don't fail at analysis. You fail at repetition.",
        'lead' => 'Most accounts do not blow up on a bad read of the market. They blow up on the same handful of mistakes repeated a hundred times with nobody counting.',
        'items' => [
            [
                'title' => "You don't know what each mistake costs",
                'text' => 'Moving the stop, averaging down, chasing an entry. You recognise them as you do them, but you have never seen the bill added up at the end of the month.',
            ],
            [
                'title' => 'Your program rules live in a separate spreadsheet',
                'text' => 'Daily drawdown, max drawdown, profit target, minimum days. Four rules that decide whether you get paid, tracked by hand somewhere else.',
            ],
            [
                'title' => 'Your journal stays a good intention',
                'text' => 'You write for three days, drop it on the first bad week, and when you want to review the year there is nothing to review.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // Modules
    // ─────────────────────────────────────────────────────────────
    'modules' => [
        'eyebrow' => "What's inside",
        'title' => 'Six modules working on the same data',
        'lead' => 'A trade goes in once and shows up everywhere: on the calendar, in that day\'s journal, in its strategy stats and in the mistakes report.',
        'items' => [
            'dashboard' => [
                'title' => 'Dashboard and P&L calendar',
                'text' => 'Balance curve, monthly calendar with every day\'s result, heatmap by time of day and period comparison. All filterable by account and date range.',
            ],
            'journal' => [
                'title' => 'Daily journal',
                'text' => 'Pre-market routine with mood and goals for the day, your master rules as a checklist, and a discipline score built from the mistakes you tag.',
            ],
            'session' => [
                'title' => 'Live session',
                'text' => 'Open the day by declaring account, strategy and mood. Tick rules off and note how you feel while you trade, not six hours later.',
            ],
            'lab' => [
                'title' => 'Lab',
                'text' => 'Scenarios over your real history: longs only, without your worst trade, with a daily trade cap, with a fixed stop and target. The curve recalculates in front of you.',
            ],
            'playbook' => [
                'title' => 'Playbook',
                'text' => 'Every strategy with its rules, its confluences and its own stats. Find out which one feeds you and which one just entertains you.',
            ],
            'backtesting' => [
                'title' => 'Backtesting',
                'text' => 'Log backtest trades separately from real ones, with their own analytics and AI audit, before you risk a euro.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // Prop firms
    // ─────────────────────────────────────────────────────────────
    'propfirm' => [
        'eyebrow' => 'Built for challenges',
        'title' => 'Your program rules, inside the product',
        'lead' => 'TradeForge does not treat your challenge as just another account. Program objectives and limits are properly modelled: pick firm, size and phase, and the dashboard watches the four rules that decide whether you pass.',
        'rules' => [
            'daily_dd' => ['title' => 'Daily drawdown',  'text' => 'How much room you have left today before you breach.'],
            'max_dd' => ['title' => 'Max drawdown',    'text' => 'Real distance to the account floor, not to the starting balance.'],
            'target' => ['title' => 'Profit target',   'text' => 'What is left to clear the phase, in money and in percent.'],
            'min_days' => ['title' => 'Minimum days',    'text' => 'Trading days that actually count, per your program.'],
        ],
        'note' => 'Firm and program catalogue maintained from inside the application.',
    ],

    // ─────────────────────────────────────────────────────────────
    // AI
    // ─────────────────────────────────────────────────────────────
    'ai' => [
        'eyebrow' => 'AI auditor',
        'title' => 'A second opinion that will not tell you what you want to hear',
        'lead' => 'It is not a chat. It is an auditor with fixed criteria that reviews entry quality and the fear-and-greed of your management from your MAE and MFE, and grades it. Available per trade, per day, per session and per strategy.',
        'sample' => [
            'label' => 'Sample output',
            'entry' => 'Entry quality: fair. Long opened at 78% of the range of the last 50 candles, with little room left to the top.',
            'mgmt' => 'Management: you took a −41 point MAE to close at +12. MFE reached +58: you closed at 20% of the available move.',
            'verdict' => 'Verdict: amateur execution. Chased entry, fear-driven exit.',
            'score' => 'Execution score: 4/10',
        ],
        'note' => 'Daily credits depend on your plan. The analysis is stored with the trade.',
    ],

    // ─────────────────────────────────────────────────────────────
    // Mistakes
    // ─────────────────────────────────────────────────────────────
    'mistakes' => [
        'eyebrow' => 'Measurable discipline',
        'title' => 'Fifteen typed mistakes, weighted',
        'lead' => 'You tag what happened on each trade and the system does the maths. Serious mistakes weigh three times more than technical ones, so your discipline score reflects what actually hurts you.',
        'grave' => 'Serious',
        'medium' => 'Medium',
        'light' => 'Technical',
    ],

    // ─────────────────────────────────────────────────────────────
    // Demo
    // ─────────────────────────────────────────────────────────────
    'demo' => [
        'eyebrow' => 'Try it before signing up',
        'title' => 'Walk into an account with six months of history',
        'lead' => 'A challenge in progress, a funded account and a blown one, with realistic sample trades, a written journal and tagged mistakes. Fully navigable, no sign-up, nothing you can break.',
        'cta' => 'Enter the demo',
        'note' => 'Read-only: nothing you do in the demo is saved.',
        'banner' => 'You are viewing the TradeForge demo with sample data. Nothing is saved.',
        'banner_cta' => 'Create my free account',
        'banner_exit' => 'Leave the demo',
        'blocked' => 'Changes cannot be saved in the demo. Create your free account to use it for real.',
    ],

    // ─────────────────────────────────────────────────────────────
    // Pricing
    // ─────────────────────────────────────────────────────────────
    'pricing' => [
        'eyebrow' => 'Pricing',
        'title' => 'Start free. Go PRO once journalling is a habit.',
        'lead' => 'No lock-in. Cancel whenever you want and your data stays yours.',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'per_month' => '/month',
        'per_year' => '/year',
        'save' => 'Two months free',
        'current' => 'Your current plan',
        'free_cta' => 'Start free',
        'pro_cta' => 'Go PRO',
        'login_cta' => 'Log in to subscribe',
        'popular' => 'Recommended',
        'trial_badge' => ':days days free on sign-up, no card',
        'trial_note' => 'Creating your account starts :days days of PRO. We ask for no card, and when it ends you drop to Free without losing anything you logged.',
        'trialing' => 'Trial running · :days days',
        'unavailable' => 'Subscription is unavailable right now. Please try again in a few minutes.',

        'free' => [
            'name' => 'Free',
            'price' => '€0',
            'claim' => 'To start logging and see whether the habit sticks.',
        ],
        'pro' => [
            'name' => 'PRO',
            'claim' => 'The whole product, with automatic sync.',
        ],

        'compare' => 'Comparison',
        'features' => [
            ['label' => 'Trading accounts',              'free' => 'Up to 3',        'pro' => 'Unlimited'],
            ['label' => 'Manual trade logging',          'free' => true,             'pro' => true],
            ['label' => 'Dashboard, calendar and curve', 'free' => true,             'pro' => true],
            ['label' => 'Prop firm objectives',          'free' => true,             'pro' => true],
            ['label' => 'Mistakes and discipline',       'free' => true,             'pro' => true],
            ['label' => 'MAE / MFE and efficiency',      'free' => true,             'pro' => true],
            ['label' => 'Automatic MT4/MT5 sync',        'free' => false,            'pro' => true],
            ['label' => 'Strategies on trades',          'free' => false,            'pro' => true],
            ['label' => 'Daily journal',                 'free' => false,            'pro' => true],
            ['label' => 'Live session and history',      'free' => false,            'pro' => true],
            ['label' => 'Scenario lab',                  'free' => false,            'pro' => true],
            ['label' => 'Strategy playbook',             'free' => false,            'pro' => true],
            ['label' => 'Backtesting',                   'free' => false,            'pro' => true],
            ['label' => 'AI analyses per day',           'free' => '3',              'pro' => '15'],
            ['label' => 'Support',                       'free' => 'Email',          'pro' => 'Priority'],
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // FAQ
    // ─────────────────────────────────────────────────────────────
    'faq' => [
        'eyebrow' => 'Fair questions',
        'title' => 'Frequently asked',
        'items' => [
            [
                'q' => 'How do my trades get in?',
                'a' => 'Two ways. By hand, from the application itself, available on the free plan. Or automatically: on PRO you install a small executable next to your MetaTrader terminal and trades sync themselves, including the chart candles around each entry.',
            ],
            [
                'q' => 'Do I need a prop firm account?',
                'a' => 'No. TradeForge works just as well with a personal or demo account. What it adds, if you are trading a challenge, is that the program rules are modelled inside so you do not have to track them yourself.',
            ],
            [
                'q' => 'Which platforms do you support?',
                'a' => 'Automatic sync currently covers MetaTrader 4 and 5. From any other platform you can log trades manually. File import and more data sources are in the works.',
            ],
            [
                'q' => 'Do you share my data?',
                'a' => 'No. Your trades and your notes are yours. Platform credentials are stored encrypted and only your own terminal uses them to sync.',
            ],
            [
                'q' => 'What happens if I cancel PRO?',
                'a' => 'You lose nothing you have logged. The account returns to the free plan and the PRO modules become unavailable, but your trades, notes and stats are still there waiting.',
            ],
            [
                'q' => 'Is it available in English?',
                'a' => 'Yes, the whole application is in English and Spanish, AI auditor included, and you can switch language at any time.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // Closing
    // ─────────────────────────────────────────────────────────────
    'cta' => [
        'title' => 'The next challenge starts with data, not promises.',
        'lead' => 'Create your free account and log your first trade in two minutes.',
        'primary' => 'Create free account',
        'secondary' => 'See the demo first',
    ],

    'footer' => [
        'tagline' => 'Trading journal for prop firm traders.',
        'product' => 'Product',
        'legal' => 'Legal',
        'terms' => 'Terms of service',
        'privacy' => 'Privacy policy',
        'rights' => 'All rights reserved.',
    ],

    // ─────────────────────────────────────────────────────────────
    // PRO gate (locked screens inside the app)
    // ─────────────────────────────────────────────────────────────
    'gate' => [
        'badge' => 'Included in PRO',
        'title' => ':module with PRO',
        'lead' => 'This is :module running on sample data. With PRO it works on your real trades.',
        'cta' => 'See plans',
        'demo' => 'Sample data',
        'demo_cta' => 'See it in the demo',
        'tag' => 'PRO',
        'locked' => 'Available on PRO',
    ],
];
