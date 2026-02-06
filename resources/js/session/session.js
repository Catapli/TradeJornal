document.addEventListener("alpine:init", () => {
    Alpine.data(
        "sessionPage",
        (serverAccounts, serverStrategies, restoredData) => ({
            // === STATE ===
            step: 1,
            timer: "00:00:00",
            startTimeTimestamp: null,
            mobileTab: "checklist",
            width: window.innerWidth, // INICIALIZACIÓN IMPORTANTE

            // Data Sources
            accounts: serverAccounts,
            strategies: serverStrategies,

            // Setup Inputs
            selectedAccountId: serverAccounts.length
                ? serverAccounts[0].id
                : null,
            selectedStrategyId: serverStrategies.length
                ? serverStrategies[0].id
                : null,
            startMood: "calm",

            // Active Session Data
            activeRules: [],
            trades: [],
            sessionNotes: [],
            metrics: { count: 0, pnl: 0, pnl_percent: 0, winrate: 0 },

            // Journal Inputs
            newNoteText: "",
            newNoteMood: "neutral",

            // Intervals
            timerInterval: null,
            pollInterval: null,

            manualTradeCount: 0,
            ghostMode: false,

            events: [],

            // === LIFECYCLE ===
            init() {
                if (restoredData) {
                    this.selectedAccountId = restoredData.accountId;
                    this.selectedStrategyId = restoredData.strategyId;
                    this.trades = restoredData.trades;
                    this.metrics = restoredData.metrics;
                    this.sessionNotes = restoredData.notes;
                    this.startTimeTimestamp = restoredData.startTime * 1000;
                    this.loadRules(restoredData.checklistState);
                    this.startTimer();
                    this.startPolling();
                    this.step = 2;
                    // Si pasaste los eventos en el mount, restáuralos aquí:
                    this.events = restoredData.events || [];
                }
            },

            // === COMPUTEDS ===
            get currentAccount() {
                return (
                    this.accounts.find((a) => a.id == this.selectedAccountId) ||
                    {}
                );
            },

            get currentStrategy() {
                return (
                    this.strategies.find(
                        (s) => s.id == this.selectedStrategyId,
                    ) || {}
                );
            },

            get allRulesChecked() {
                return (
                    this.activeRules.length > 0 &&
                    this.activeRules.every((r) => r.checked)
                );
            },

            get statusColor() {
                const pnl = this.metrics.pnl_percent;
                const limits = this.currentAccount.limits || {};

                if (limits.max_loss_pct && pnl <= -limits.max_loss_pct) {
                    return {
                        text: "text-rose-600",
                        bg: "bg-rose-100 text-rose-700 border border-rose-200",
                    };
                }
                if (limits.target_pct && pnl >= limits.target_pct) {
                    return {
                        text: "text-emerald-600",
                        bg: "bg-emerald-100 text-emerald-700 border border-emerald-200",
                    };
                }
                return pnl >= 0
                    ? {
                          text: "text-emerald-600",
                          bg: "bg-emerald-50 text-emerald-700",
                      }
                    : { text: "text-rose-500", bg: "bg-rose-50 text-rose-700" };
            },

            get isLimitBreached() {
                const limits = this.currentAccount.limits || {};
                return (
                    limits.max_loss_pct &&
                    this.metrics.pnl_percent <= -limits.max_loss_pct
                );
            },

            get isMaxTradesReached() {
                const max = this.currentAccount.limits?.max_trades;
                if (!max) return false;
                return this.metrics.count >= max;
            },

            get isTimeValid() {
                // Protección: Si no hay límites, siempre es válido (o false, según prefieras)
                if (!this.currentAccount.limits) return true;

                const start = this.currentAccount.limits.start_time;
                const end = this.currentAccount.limits.end_time;

                if (!start || !end) return true;

                const now = new Date();
                const currentMinutes = now.getHours() * 60 + now.getMinutes();

                const [startH, startM] = start.split(":").map(Number);
                const [endH, endM] = end.split(":").map(Number);

                const startMinutes = startH * 60 + startM;
                const endMinutes = endH * 60 + endM;

                return (
                    currentMinutes >= startMinutes &&
                    currentMinutes < endMinutes
                );
            },
            get canTakeTrade() {
                const strategyOk =
                    this.activeRules.length > 0 &&
                    this.activeRules.every((r) => r.checked);

                // Si hay Overtrading, return FALSE inmediatamente
                if (this.isOvertrading) return false;

                const limitBreached = this.isLimitBreached;
                const tradesFull = this.isMaxTradesReached; // Esto bloquea si llegas al límite exacto
                const timeOk = this.isTimeValid;

                return strategyOk && !limitBreached && !tradesFull && timeOk;
            },

            get tradeButtonText() {
                // EL ORDEN IMPORTA: Prioridad 1 es Overtrading (Regla violada)
                if (this.isOvertrading) return "BLOQUEADO: OVERTRADING";
                if (this.isLimitBreached) return "BLOQUEADO: MAX LOSS";

                // Prioridad 2: Advertencias (Ya no quedan balas)
                if (this.isMaxTradesReached) return "STOP: MUNICIÓN AGOTADA";

                if (!this.isTimeValid) return "FUERA DE HORARIO";
                if (!this.allRulesChecked) return "CHECKLIST PENDIENTE";

                return "SETUP APROBADO";
            },

            // Nueva propiedad computada específica
            get isOvertrading() {
                const max = this.currentAccount.limits?.max_trades;
                if (!max) return false;
                // Si llevas MÁS trades que el máximo, es Overtrading
                return this.metrics.count > max;
            },

            // === ACTIONS ===
            async initSession() {
                if (!this.selectedAccountId || !this.selectedStrategyId) return;

                const response = await this.$wire.startSession(
                    this.selectedAccountId,
                    this.selectedStrategyId,
                    this.startMood,
                    "",
                );

                if (response && response.id) {
                    this.startTimeTimestamp = Date.now();
                    this.loadRules([]);
                    this.startTimer();
                    this.startPolling();
                    this.step = 2;
                }
            },

            // En la sección ACTIONS:
            toggleManualTrade(index) {
                // Si haces click en la bala 3 y tenías 2, sube a 3.
                // Si haces click en la 3 y tenías 3, baja a 2 (deshacer).
                if (index === this.manualTradeCount) {
                    this.manualTradeCount = index - 1;
                } else {
                    this.manualTradeCount = index;
                }
            },

            loadRules(savedState = []) {
                const strat = this.currentStrategy;
                if (strat && strat.rules) {
                    this.activeRules = strat.rules.map((ruleText) => ({
                        text: ruleText,
                        checked: savedState.includes(ruleText),
                    }));
                } else {
                    this.activeRules = [];
                }
            },

            syncChecklist() {
                const checkedData = this.activeRules
                    .filter((r) => r.checked)
                    .map((r) => r.text);
                this.$wire.syncChecklist(checkedData);
            },

            startTimer() {
                if (this.timerInterval) clearInterval(this.timerInterval);
                this.timerInterval = setInterval(() => {
                    const now = Date.now();
                    const diff = Math.floor(
                        (now - this.startTimeTimestamp) / 1000,
                    );
                    const h = Math.floor(diff / 3600)
                        .toString()
                        .padStart(2, "0");
                    const m = Math.floor((diff % 3600) / 60)
                        .toString()
                        .padStart(2, "0");
                    const s = (diff % 60).toString().padStart(2, "0");
                    this.timer = `${h}:${m}:${s}`;
                }, 1000);
            },

            startPolling() {
                if (this.pollInterval) clearInterval(this.pollInterval);
                this.pollInterval = setInterval(async () => {
                    const data = await this.$wire.fetchUpdates();
                    if (data) {
                        this.trades = data.trades;
                        this.metrics = data.metrics;

                        // Actualizar lista de eventos
                        this.events = data.events || [];
                    }
                }, 5000);
            },

            async submitNote() {
                if (!this.newNoteText.trim()) return;

                this.sessionNotes.unshift({
                    id: "temp-" + Date.now(),
                    note: this.newNoteText,
                    mood: this.newNoteMood,
                    time: new Date().toLocaleTimeString([], {
                        hour: "2-digit",
                        minute: "2-digit",
                    }),
                });

                await this.$wire.addNote(this.newNoteText, this.newNoteMood);

                this.newNoteText = "";
                this.newNoteMood = "neutral";
            },

            async setTradeMood(tradeId, mood) {
                const t = this.trades.find((t) => t.id === tradeId);
                if (t) t.mood = mood;
                await this.$wire.updateTradeMood(tradeId, mood);
            },

            async finishSession(endMood) {
                clearInterval(this.timerInterval);
                clearInterval(this.pollInterval);
                const url = await this.$wire.closeSession(
                    this.metrics,
                    endMood,
                );
                if (url) window.location.href = url;
            },
        }),
    );
});
