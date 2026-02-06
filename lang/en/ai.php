<?php

declare(strict_types=1);

return [
    'audit_prompt' => "
        Perform a technical and psychological audit of this trading operation.
        Be strict, objective, and professional.
        
        DATA AND CONTEXT:
        :context
        
        ANALYSIS INSTRUCTIONS (Use these criteria):
        1. STRUCTURE ANALYSIS (Visual):
           - If image provided: Did the entry respect Support/Resistance, Order Blocks, or Trend?
           - Was it a precision entry ('Sniper') or price chasing (FOMO)?
        2. EXECUTION EFFICIENCY (MAE/MFE Data):
           - MAE vs PnL: Did it withstand too much drawdown for little gain? (Inverted Risk/Reward).
           - MFE vs Exit: Was money left on the table due to fear (premature close)?
        3. IMPLICIT PSYCHOLOGY:
           - Based on duration and result: Planned or Impulsive?

        FORMAT RULES:
        - DO NOT write introductions, greetings, or dramatic phrases.
        - Start DIRECTLY with the first point.
        - ALWAYS respond in English.

        REQUIRED RESPONSE FORMAT (Use these icons):
        - **🎯 Entry Quality:** [Bad/Average/Excellent] + Brief technical explanation.
        - **🧠 Management (Fear/Greed):** Analysis based on MAE/MFE and exit.
        - **⚖️ Final Verdict:** Direct conclusion on whether execution was professional or amateur.
        - **💡 Improvement Tip:** A concrete tactical action for the next similar trade.
        - **🏆 Execution Score:** [0/10] (Rate the technique).
    ",
    'labels' => [
        'asset' => 'Asset',
        'type' => 'Type',
        'entry' => 'Entry',
        'exit' => 'Exit',
        'result' => 'Result',
        'duration' => 'Duration',
        'efficiency' => 'Efficiency',
    ]
];
