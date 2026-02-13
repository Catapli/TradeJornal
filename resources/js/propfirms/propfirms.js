document.addEventListener("alpine:init", () => {
    Alpine.data("propManager", function (initialTree) {
        return {
            // --- DATA STORE ---
            firms: initialTree,
            view: "firms",
            selectedFirmId: null,
            selectedProgramId: null,

            // Flag interno para evitar listeners duplicados
            _listenersRegistered: false,

            // --- ESTADO VISUAL ---
            modals: {
                firm: false,
                program: false,
                level: false,
            },

            // --- ESTADO ALERTAS (NUEVO) ---
            showAlert: false,
            typeAlert: "success",
            bodyAlert: "",

            // --- FORMULARIOS (Entangled) ---
            firmForm: this.$wire.entangle("firmForm"),
            programForm: this.$wire.entangle("programForm"),
            levelForm: this.$wire.entangle("levelForm"),
            objectivesForm: this.$wire.entangle("objectivesForm"),

            // --- COMPUTED ---
            get activeFirm() {
                return this.firms.find((f) => f.id === this.selectedFirmId);
            },
            get activeProgram() {
                if (!this.activeFirm) return null;
                return this.activeFirm.programs.find(
                    (p) => p.id === this.selectedProgramId,
                );
            },
            get currentPrograms() {
                return this.activeFirm ? this.activeFirm.programs : [];
            },
            get currentLevels() {
                return this.activeProgram ? this.activeProgram.levels : [];
            },

            // --- INIT ---
            init() {
                // EVITAR LISTENERS DUPLICADOS
                if (this._listenersRegistered) return;
                this._listenersRegistered = true;

                console.log("🚀 PropManager Ready");

                // 1. Refrescar datos (Blindado)
                this.$wire.on("refresh-tree", (data) => {
                    const payload = Array.isArray(data) ? data[0] : data;

                    if (payload && payload.tree) {
                        // Usar structuredClone es más moderno y eficiente que JSON parse/stringify
                        this.firms = structuredClone(payload.tree);
                        console.log("✅ Tree actualizado");
                    }
                });

                // 2. Notificaciones
                this.$wire.on("notify", (data) => {
                    const payload = Array.isArray(data) ? data[0] : data;
                    this.triggerAlert(payload.message, payload.type);
                    this.closeAllModals();

                    if (payload.newProgramId) {
                        this.autoNavigateToNewProgram(payload.newProgramId);
                    }
                });
            },

            // --- ALERTAS (NUEVO) ---
            triggerAlert(message, type = "success") {
                this.bodyAlert = message;
                this.typeAlert = type;
                this.showAlert = true;

                // Auto-ocultar
                setTimeout(() => {
                    this.showAlert = false;
                }, 4000);
            },

            // --- NAVEGACIÓN AUTOMÁTICA TRAS CREAR PROGRAMA (NUEVO) ---
            autoNavigateToNewProgram(programId) {
                this.$nextTick(() => {
                    this.selectedProgramId = programId;
                    this.view = "levels";
                    window.scrollTo({ top: 0, behavior: "smooth" });
                });
            },

            // --- NAVEGACIÓN ---
            selectFirm(id) {
                this.selectedFirmId = id;
                this.view = "programs";
                window.scrollTo({ top: 0, behavior: "smooth" });
            },

            selectProgram(id) {
                this.selectedProgramId = id;
                this.view = "levels";
                window.scrollTo({ top: 0, behavior: "smooth" });
            },

            // --- MODALES ---
            openFirmModal(firm = null) {
                if (firm) {
                    this.firmForm.id = firm.id;
                    this.firmForm.name = firm.name;
                    this.firmForm.website = firm.website;
                    this.firmForm.server = firm.server;
                } else {
                    this.firmForm.id = null;
                    this.firmForm.name = "";
                    this.firmForm.website = "";
                    this.firmForm.server = "";
                }
                this.modals.firm = true;
            },

            openProgramModal() {
                this.programForm.id = null;
                this.programForm.firm_id = this.selectedFirmId;
                this.programForm.name = "";
                this.programForm.step_count = 1;
                this.modals.program = true;
            },

            openLevelModal(level = null) {
                if (level) {
                    this.levelForm.id = level.id;
                    this.levelForm.program_id = level.program_id;
                    this.levelForm.name = level.name;
                    this.levelForm.size = level.size;
                    this.levelForm.fee = level.fee;
                    this.levelForm.currency = level.currency;

                    let objectivesMap = {};
                    level.objectives.forEach((obj) => {
                        // 1. Parsear el JSON de metadatos (si existe)
                        const metadata = obj.rules_metadata
                            ? JSON.parse(obj.rules_metadata)
                            : {};
                        const restrictions = metadata.restrictions || {};

                        objectivesMap[obj.phase_number] = {
                            name: obj.name,
                            profit_target_percent: obj.profit_target_percent,
                            max_daily_loss_percent: obj.max_daily_loss_percent,
                            max_total_loss_percent: obj.max_total_loss_percent,
                            min_trading_days: obj.min_trading_days,
                            loss_type: obj.loss_type,

                            // --- REGLAS: Mapeo JSON -> Campos Planos ---
                            min_trade_duration:
                                restrictions.min_trade_duration_seconds || null,

                            // Checkbox News: Si existe la key, es true
                            news_trading_enabled:
                                !!restrictions.no_news_trading,

                            // Detalles News
                            news_minutes_before:
                                restrictions.no_news_trading?.minutes_before ||
                                2,
                            news_minutes_after:
                                restrictions.no_news_trading?.minutes_after ||
                                2,

                            // Weekend Holding (Default true si no existe)
                            weekend_holding:
                                restrictions.weekend_holding !== undefined
                                    ? restrictions.weekend_holding
                                    : true,
                        };
                    });
                    this.objectivesForm = objectivesMap;
                } else {
                    // ... lógica existente de crear nuevo ...
                    this.levelForm.id = null;
                    this.levelForm.program_id = this.selectedProgramId;
                    this.levelForm.name = "";
                    this.levelForm.size = 10000;
                    this.levelForm.fee = 0;
                    this.levelForm.currency = "USD";
                    this.generateObjectivesTemplate();
                }
                this.modals.level = true;
            },

            generateObjectivesTemplate() {
                const steps = parseInt(this.activeProgram.step_count);
                let templates = {};

                templates[0] = this.getObjectiveTemplate(0, "Live Account");

                if (steps > 0) {
                    for (let i = 1; i <= steps; i++) {
                        templates[i] = this.getObjectiveTemplate(
                            i,
                            `Phase ${i}`,
                        );
                    }
                }

                this.objectivesForm = templates;
            },

            getObjectiveTemplate(phase, name) {
                return {
                    name: name,
                    profit_target_percent: phase === 0 ? null : 10,
                    max_daily_loss_percent: 5,
                    max_total_loss_percent: 10,
                    min_trading_days: 0,
                    loss_type: "balance_based",
                    min_trade_duration: null,
                    news_trading_enabled: false,
                    news_minutes_before: 2,
                    news_minutes_after: 2,
                    weekend_holding: true,
                };
            },

            closeAllModals() {
                this.modals.firm = false;
                this.modals.program = false;
                this.modals.level = false;
            },
        };
    });
});
