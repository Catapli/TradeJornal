document.addEventListener("alpine:init", () => {
    Alpine.data(
        "sessionPage",
        (serverAccounts, serverStrategies, restoredData) => ({
            // ==========================================
            // 🎯 STATE
            // ==========================================
            step: 1,
            timer: "00:00:00",
            startTimeTimestamp: null,
            mobileTab: "checklist",
            width: window.innerWidth,

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

            // Additional State
            manualTradeCount: 0,
            ghostMode: false,
            events: [],
            postSessionNotes: "",

            // ✅ NUEVO: Estado del polling inteligente
            isSyncing: false,
            lastSyncTime: null,
            syncErrors: 0,
            isTabVisible: true,

            // ==========================================
            // 🎬 LIFECYCLE
            // ==========================================
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
                    this.events = restoredData.events || [];
                }

                // ✅ NUEVO: Listener de visibilidad de página
                this.setupVisibilityListener();

                // ✅ NUEVO: Listener de resize para responsive
                window.addEventListener("resize", () => {
                    this.width = window.innerWidth;
                });
            },

            // ==========================================
            // 🧮 COMPUTEDS
            // ==========================================
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
                if (this.isOvertrading) return false;

                const limitBreached = this.isLimitBreached;
                const tradesFull = this.isMaxTradesReached;
                const timeOk = this.isTimeValid;

                return strategyOk && !limitBreached && !tradesFull && timeOk;
            },

            get tradeButtonText() {
                if (this.isOvertrading) return "BLOQUEADO: OVERTRADING";
                if (this.isLimitBreached) return "BLOQUEADO: MAX LOSS";
                if (this.isMaxTradesReached) return "STOP: MUNICIÓN AGOTADA";
                if (!this.isTimeValid) return "FUERA DE HORARIO";
                if (!this.allRulesChecked) return "CHECKLIST PENDIENTE";
                return "SETUP APROBADO";
            },

            get isOvertrading() {
                const max = this.currentAccount.limits?.max_trades;
                if (!max) return false;
                return this.metrics.count > max;
            },

            // ✅ NUEVO: Intervalo adaptativo de polling
            get pollingInterval() {
                // Si hay límite alcanzado o máximo de trades, reducir frecuencia
                if (this.isLimitBreached || this.isMaxTradesReached) {
                    return 15000; // 15 segundos
                }
                // Frecuencia normal
                return 5000; // 5 segundos
            },

            // ==========================================
            // 🚀 ACTIONS
            // ==========================================
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

            toggleManualTrade(index) {
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

            // ✅ OPTIMIZADO: Polling inteligente con intervalo adaptativo
            startPolling() {
                if (this.pollInterval) {
                    clearTimeout(this.pollInterval);
                }

                // Primera actualización inmediata
                this.fetchUpdates();

                // Polling con intervalo adaptativo
                const poll = () => {
                    this.pollInterval = setTimeout(async () => {
                        await this.fetchUpdates();
                        // Reiniciar con nuevo intervalo (puede cambiar si se alcanza límite)
                        poll();
                    }, this.pollingInterval);
                };

                poll();
            },

            // ✅ OPTIMIZADO: Fetch con manejo de errores y visibilidad
            async fetchUpdates() {
                // No sincronizar si tab no está visible
                if (!this.isTabVisible) {
                    return;
                }

                // Evitar requests concurrentes
                if (this.isSyncing) {
                    return;
                }

                this.isSyncing = true;

                try {
                    const data = await this.$wire.fetchUpdates();

                    if (data) {
                        this.trades = data.trades;
                        this.metrics = data.metrics;
                        this.events = data.events || [];

                        // ✅ IMPORTANTE: Si sync trae más trades, resetear manual
                        if (this.metrics.count >= this.manualTradeCount) {
                            this.manualTradeCount = 0; // Reset porque sync prevalece
                        }

                        // ✅ Actualizar timestamp de última sync
                        this.lastSyncTime = new Date();
                        this.syncErrors = 0; // Reset contador de errores
                    } else {
                        // Si no hay data, puede ser que la sesión no existe
                        this.syncErrors++;

                        // Después de 3 errores consecutivos, detener polling
                        if (this.syncErrors >= 3) {
                            console.warn(
                                "Sesión no encontrada. Deteniendo polling.",
                            );
                            this.stopPolling();
                        }
                    }
                } catch (error) {
                    console.error("Error en fetchUpdates:", error);
                    this.syncErrors++;

                    // Detener polling si hay muchos errores
                    if (this.syncErrors >= 5) {
                        console.error(
                            "Demasiados errores. Deteniendo polling.",
                        );
                        this.stopPolling();
                    }
                } finally {
                    this.isSyncing = false;
                }
            },

            // ✅ NUEVO: Detener polling manualmente
            stopPolling() {
                if (this.pollInterval) {
                    clearTimeout(this.pollInterval);
                    this.pollInterval = null;
                }
            },

            // ✅ NUEVO: Listener de visibilidad de página (Page Visibility API)
            setupVisibilityListener() {
                document.addEventListener("visibilitychange", () => {
                    this.isTabVisible = !document.hidden;

                    if (this.isTabVisible && this.step === 2) {
                        // Tab vuelve a estar visible, forzar sync inmediata
                        console.log("Tab visible, sincronizando...");
                        this.fetchUpdates();
                    }
                });
            },

            // ✅ OPTIMISTIC UI: Nota aparece instantáneamente
            async submitNote() {
                if (!this.newNoteText.trim()) return;

                // OPTIMISTIC UPDATE: Añadir nota al array ANTES de la respuesta del servidor
                const optimisticNote = {
                    id: "temp-" + Date.now(),
                    note: this.newNoteText,
                    mood: this.newNoteMood,
                    time: new Date().toLocaleTimeString([], {
                        hour: "2-digit",
                        minute: "2-digit",
                    }),
                };

                this.sessionNotes.unshift(optimisticNote);

                // LIMPIAR INPUTS INMEDIATAMENTE (UX instantánea)
                const noteText = this.newNoteText;
                const noteMood = this.newNoteMood;
                this.newNoteText = "";
                this.newNoteMood = "neutral";

                // ✅ Scroll automático al top (donde aparece la nota)
                this.$nextTick(() => {
                    const container = this.$refs.notesContainer;
                    if (container) {
                        container.scrollTo({ top: 0, behavior: "smooth" });
                    }
                });

                // SYNC CON SERVIDOR (background)
                try {
                    await this.$wire.addNote(noteText, noteMood);
                    // La nota temporal será reemplazada en el próximo fetchUpdates()
                } catch (error) {
                    // ROLLBACK: Si falla, eliminar nota temporal y restaurar inputs
                    this.sessionNotes = this.sessionNotes.filter(
                        (n) => n.id !== optimisticNote.id,
                    );
                    this.newNoteText = noteText;
                    this.newNoteMood = noteMood;
                    console.error("Error al guardar nota:", error);
                }
            },

            async setTradeMood(tradeId, mood) {
                const t = this.trades.find((t) => t.id === tradeId);
                if (t) t.mood = mood;
                await this.$wire.updateTradeMood(tradeId, mood);
            },

            async finishSession(endMood) {
                clearInterval(this.timerInterval);
                this.stopPolling(); // ✅ Usar método optimizado
                const url = await this.$wire.closeSession(
                    this.metrics,
                    endMood,
                    this.postSessionNotes,
                );
                if (url) window.location.href = url;
            },
        }),
    );
});
