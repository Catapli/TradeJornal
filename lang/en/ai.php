<?php

declare(strict_types=1);

return [
    'audit_prompt' => "
        Perform a technical and psychological audit of this trading operation.
        Be strict, objective and professional.
        
        DATA & CONTEXT:
        :context
        
        ANALYSIS INSTRUCTIONS (Use these criteria):
        1. STRUCTURE ANALYSIS (Visual):
           - If there is an image: Does the entry respect Support/Resistance, Order Blocks or Trend?
           - Was it a precise entry ('Sniper') or price chasing (FOMO)?
        2. EXECUTION EFFICIENCY (MAE/MFE Data):
           - MAE vs PnL: Did it hold too much drawdown to gain little? (Inverted Risk/Reward).
           - MFE vs Exit: Did it leave a lot of money on the table out of fear (premature close)?
        3. IMPLIED PSYCHOLOGY:
           - Based on duration and result: Planned or Impulsive?


        FORMAT RULES:
        - Do NOT write introductions, greetings or dramatic phrases.
        - Start DIRECTLY with the first point.
        - ALWAYS respond in Spanish.


        REQUIRED RESPONSE FORMAT (Use these icons):
        - **🎯 Entry Quality:** [Poor/Fair/Excellent] + Brief technical explanation.
        - **🧠 Management (Fear/Greed):** Analysis based on MAE/MFE and exit.
        - **⚖️ Final Verdict:** Direct conclusion on whether the execution was professional or amateur.
        - **💡 Improvement Tip:** One concrete tactical action.
        - **🏆 Execution Score:** [0/10] (Rate the technique).
    ",

    'session_prompt' => '
Perform a risk and behaviour audit of today\'s complete trading session.
Be strict, objective and professional.

SESSION DATA (Chronological):
:trades_text

ANALYSIS INSTRUCTIONS (Look for these patterns):
1. EMOTIONAL CONTROL (Tilt): Are there consecutive fast trades after a loss (Revenge Trading)?
2. RISK MANAGEMENT: Does lot size increase after a loss (Martingale)? Does it cut gains quickly and let losses run?
3. DISCIPLINE: Is there overtrading (many mediocre trades) or quality selection?

FORMAT RULES:
- Do NOT write introductions, greetings or dramatic phrases.
- Start DIRECTLY with the first point of the format.
- ALWAYS respond in Spanish.

REQUIRED RESPONSE FORMAT (Use these icons):
- **📊 Summary:** One sentence defining the trader\'s mental and technical state today.
- **🚩 Detected Alerts:** List of serious errors (Tilt, Overtrading, etc.). If it was a clean day, state "None".
- **💡 Tip for Tomorrow:** One concrete corrective action.
- **🏆 Day Score:** [0/10] (Based on discipline, not just money earned).
',

    'draft_prompt' => "
        Act as a professional trading coach and writer. Write today's journal entry in FIRST PERSON (as if you were me).
        
        MY DATA FOR TODAY:
        :context
        
        TRADE BREAKDOWN:
        :trades
        
        WRITING INSTRUCTIONS:
        1. Start with a summary sentence of how the session went (based on PnL and mood).
        2. Briefly analyse the behaviour. If there were errors, be critical but constructive. If it was clean, congratulate me.
        3. If there were large losses or streaks, mention the psychological aspect.
        4. End with a brief improvement conclusion.
        5. Use basic HTML tags (<p>, <strong>, <em>, <ul>, <li>).
        6. Be concise, maximum 3 paragraphs.
        7. ALWAYS respond in Spanish.


        MANDATORY TECHNICAL FORMAT:
        - Wrap each paragraph in <p>...</p> tags.
        - Use <strong> for bold text.
        - Use <ul><li>...</li></ul> for lists.
        - Do NOT use Markdown. Clean HTML only.
        - Do NOT include \`\`\`html at the beginning or end.
    ",
    'daily_tip' => "
    Act as an expert Psycho-Trading Coach. Analyse these trades looking for destructive patterns.

    DATA:
    :datos

    PRIORITY INSTRUCTIONS (Follow this strict order):
    1. 🚨 FIRST look for OVERTRADING/TILT: If you see multiple trades (more than 3-4) on the same day or session with losses, IGNORE the direction (Long/Short) and attack the quantity. The problem is the volume, not the setup.
    2. 🕒 SECOND look for TIMING: If losses always occur at the same time.
    3. 📉 THIRD look for DIRECTION: Only if the behaviour is disciplined (few trades), check whether it fails on Longs/Shorts.

    RESPONSE RULES:
    - Give me ONE SINGLE imperative and blunt sentence.
    - Maximum 20 words.
    - Start with an emoji.
    
    Correct examples:
    '🔥 You are on a destructive streak: shut down the computer after 2 losses or you will blow the account.' (Prioritise behaviour)
    '🛑 Your obsession with trading the New York open is costing you; wait 30 minutes before entering.' (Prioritise timing)
",


    // Labels for data fields
    'labels' => [
        'asset' => 'Asset',
        'type' => 'Type',
        'entry' => 'Entry',
        'exit' => 'Exit',
        'result' => 'Result',
        'duration' => 'Duration',
        'efficiency' => 'Efficiency',
        'future' => 'POST-CLOSE ANALYSIS',
        'mood' => 'Initial mood',
        'total_result' => 'Total result',
        'total_ops' => 'Total trades',
        'mistakes' => 'Errors',
        'clean_execution' => 'Clean execution',
        'profit' => 'Profit',
        'loss' => 'Loss',
        'ai_draft_header' => '🤖 AI Draft',
    ]
];
