document.addEventListener("alpine:init", () => {
    // COMPONENTE 1: Lógica del Dashboard (Gráficos, Alertas, UI General)
    Alpine.data("dashboardLogic", () => ({
        showLoadingGrafic: false,
        timeframe: "all",
        showAlert: false,
        bodyAlert: "",
        typeAlert: "error",
        showModal: false, // Control del modal
        labelTitleModal: "Crear Cuenta",
        typeButton: "", // Tipo de Boton para los modals
        tableHistory: null,

        init() {
            const self = this; // Capturamos el scope de Alpine
            // Inicializamos gráfico
            this.initChart();

            // Listeners de eventos globales
            window.addEventListener("timeframe-updated", (e) => {
                this.timeframe = e.detail.timeframe;
                this.initChart();
            });

            window.addEventListener("show-alert", (e) => {
                const data = e.detail[0] || e.detail; // Ajuste por si viene en array o no
                this.showModal = false; // Cerramos modal si está abierto
                this.triggerAlert(data.message, data.type);
            });

            // LISTENER PARA RECARGAR LA TABLA CUANDO CAMBIA LA CUENTA
            window.addEventListener("account-updated", (e) => {
                // ... tu lógica de timeframe ...
                this.timeframe = e.detail.timeframe;
                console.log("Prueba");

                // 👇 RECARGAR DATATABLE
                if (this.tableHistory) {
                    // reload(null, false) recarga los datos manteniendo la paginación actual (opcional)
                    this.tableHistory.ajax.reload();
                }
            });

            if (!$.fn.DataTable.isDataTable("#table_history")) {
                self.tableHistory = $("#table_history")
                    .on("init.dt", () => {
                        // Sincroniza la altura del contenedor con Alpine
                        this.height =
                            document.getElementById("container_table")
                                .offsetHeight + "px";

                        document.getElementById(
                            "container_table",
                        ).style.minHeight = this.height;
                    })
                    .DataTable({
                        ajax: {
                            url: "/users/data",
                            data: function (d) {
                                // Agregar parámetros adicionales para el filtro
                                let id = self.$wire.selectedAccountId;

                                console.log(
                                    "Cargando tabla para Account ID:",
                                    id,
                                );
                                d.id = id;
                            },
                        },
                        lengthMenu: [5, 10, 20, 25, 50],
                        pageLength: 5,
                        columns: [
                            { data: "id" },
                            { data: "name" },
                            { data: "town.town" },
                            { data: "active" },
                        ],
                        pagingType: "numbers",
                        language: {
                            url: "/datatable/es-ES.json",
                        },

                        createdRow: function (row, data, dataIndex) {
                            $(row).on("click", function () {
                                self.$wire.id_user = data.id;
                                self.$wire.call("findUserById");
                                self.registerSelected = true;
                            });
                        },
                        columnDefs: [
                            { width: "5%", targets: 0 },
                            {
                                targets: 3, // Índice de la columna a formatear
                                render: function (data, type, row) {
                                    if (data) {
                                        return '<i class="fa-solid fa-circle-check text-green-600"></i>';
                                    } else {
                                        return '<i class="fa-solid fa-circle-xmark text-red-600"></i>';
                                    }
                                },
                            },
                        ],
                    });
            }
        },

        triggerAlert(message, type = "error") {
            this.bodyAlert = message;
            this.typeAlert = type;
            this.showAlert = true;
            // Opcional: auto-ocultar a los 3 seg
            setTimeout(() => (this.showAlert = false), 4000);
        },

        setTimeframe(value) {
            this.showLoadingGrafic = true;
            this.$wire.setTimeframe(value);
        },

        showOpenModalCreate() {
            this.showModal = true;
        },

        initChart() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            this.showLoadingGrafic = false;

            // Usamos nextTick para asegurar que el DOM está listo
            this.$nextTick(() => {
                if (window.balanceChart) window.balanceChart.destroy();

                const ctx = canvas.getContext("2d");
                // Accedemos a los datos de Livewire de forma segura
                const labels = this.$wire.balanceChartData?.labels || [];
                const datasets = this.$wire.balanceChartData?.datasets || [];

                if (!labels.length) return;

                window.balanceChart = new Chart(ctx, {
                    type: "line",
                    data: { labels, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: "index" },
                        // Tus opciones de chart...
                    },
                });
            });
        },
    }));

    // COMPONENTE 2: Lógica del Selector (Formulario Prop Firms)
    // COMPONENTE 2: Lógica del Selector
    Alpine.data("accountSelector", (data) => ({
        // 1. Blindaje de datos iniciales
        allFirms: Array.isArray(data) ? data : [],

        nameAccount: "",
        selectedFirmId: "",
        selectedProgramId: "",
        selectedSize: "",
        selectedLevelId: "",
        selectedServer: "",
        syncronize: false,
        platformBroker: "",
        loginPlatform: "",
        passwordPlatform: "",

        // 2. Funciones seguras en lugar de Getters complejos
        getPrograms() {
            if (!this.selectedFirmId) return [];
            const firm = this.allFirms.find((f) => f.id == this.selectedFirmId);
            return firm ? firm.programs : [];
        },

        getSizes() {
            if (!this.selectedProgramId) return [];
            const programs = this.getPrograms(); // Llamamos a la función interna
            const program = programs.find(
                (p) => p.id == this.selectedProgramId,
            );

            if (!program || !program.levels) return [];

            // Extraer tamaños únicos y ordenarlos numéricamente
            const sizes = program.levels.map((l) => parseFloat(l.size));
            return [...new Set(sizes)].sort((a, b) => a - b);
        },

        getCurrencies() {
            if (!this.selectedSize || !this.selectedProgramId) return [];
            const programs = this.getPrograms();
            const program = programs.find(
                (p) => p.id == this.selectedProgramId,
            );

            if (!program || !program.levels) return [];

            return program.levels.filter(
                (l) => parseFloat(l.size) == this.selectedSize,
            );
        },

        init() {
            console.log(
                "✅ Alpine Selector Iniciado. Empresas:",
                this.allFirms,
            );

            // 3. Watchers (Solo sincronizamos cuando el usuario CAMBIA algo)
            this.$watch("selectedFirmId", (value) => {
                this.selectedProgramId = "";
                this.selectedSize = "";
                this.selectedLevelId = "";

                // 2. LÓGICA PARA BUSCAR EL SERVIDOR
                // Buscamos la empresa seleccionada en el array de datos
                const firm = this.allFirms.find((f) => f.id == value);

                // Si existe, asignamos su server, si no, vacío
                this.selectedServer = firm ? firm.server : "";
                this.syncToLivewire();
            });

            this.$watch("selectedProgramId", () => {
                this.selectedSize = "";
                this.selectedLevelId = "";
                this.syncToLivewire();
            });

            this.$watch("selectedSize", () => {
                this.selectedLevelId = "";
                this.syncToLivewire();
            });

            this.$watch("selectedLevelId", () => {
                this.syncToLivewire();
            });

            this.$watch("syncronize", () => {
                this.syncToLivewire();
            });

            this.$watch("platformBroker", () => {
                this.syncToLivewire();
            });

            this.$watch("loginPlatform", () => {
                this.syncToLivewire();
            });

            this.$watch("passwordPlatform", () => {
                this.syncToLivewire();
            });

            // ❌ ELIMINADO: this.syncToLivewire()
            // No sincronizamos al inicio para evitar errores de $wire undefined
        },

        syncToLivewire() {
            // 4. Protección contra fallos de $wire
            if (typeof this.$wire === "undefined") {
                console.warn("Livewire no está listo todavía.");
                return;
            }

            // Enviamos datos
            this.$wire.form.selectedPropFirmID = this.selectedFirmId;
            this.$wire.form.selectedProgramID = this.selectedProgramId;
            this.$wire.form.size = this.selectedSize;
            this.$wire.form.programLevelID = this.selectedLevelId;
            this.$wire.form.server = this.selectedServer;
            this.$wire.form.sync = this.syncronize;
            this.$wire.form.platformBroker = this.platformBroker;
            this.$wire.form.loginPlatform = this.loginPlatform;
            this.$wire.form.passwordPlatform = this.passwordPlatform;
            this.$wire.form.name = this.nameAccount;
        },

        triggerAlert(message, type = "error") {
            this.bodyAlert = message;
            this.typeAlert = type;
            this.showAlert = true;
            // Opcional: auto-ocultar a los 3 seg
            setTimeout(() => (this.showAlert = false), 4000);
        },

        checkForm() {
            console.log(this.$wire.form.selectedPropFirmID);

            if (this.nameAccount === null || this.nameAccount.trim() === "") {
                this.triggerAlert(this.$e("enter_account_name"), "error");
                this.inputRed(this.$refs.nameAccount);
                return false;
            }

            if (
                this.$wire.form.selectedPropFirmID === null ||
                this.$wire.form.selectedPropFirmID === ""
            ) {
                this.triggerAlert(this.$e("select_prop_firm"), "error");
                this.inputRed(this.$refs.propFirm);
                return false;
            }

            if (
                this.$wire.form.selectedProgramID === null ||
                this.$wire.form.selectedProgramID === ""
            ) {
                this.triggerAlert(this.$e("select_type_account"), "error");
                this.inputRed(this.$refs.program);
                return false;
            }

            if (this.$wire.form.size === null || this.$wire.form.size === "") {
                this.triggerAlert(this.$e("select_account_size"), "error");
                this.inputRed(this.$refs.size);
                return false;
            }

            if (
                this.$wire.form.programLevelID === null ||
                this.$wire.form.programLevelID === ""
            ) {
                this.triggerAlert(this.$e("select_currency_account"), "error");
                this.inputRed(this.$refs.currency);
                return false;
            }

            if (this.syncronize) {
                console.log("Sincronizando cuenta con el servidor...");
                if (
                    this.$wire.form.platformBroker === null ||
                    this.$wire.form.platformBroker === ""
                ) {
                    this.triggerAlert(
                        this.$e("enter_platform_broker"),
                        "error",
                    );
                    this.inputRed(this.$refs.platformBroker);
                    return false;
                }

                if (
                    this.$wire.form.loginPlatform === null ||
                    this.$wire.form.loginPlatform.trim() === ""
                ) {
                    this.triggerAlert(this.$e("enter_login_platform"), "error");
                    this.inputRed(this.$refs.loginPlatform);
                    return false;
                }

                if (
                    this.$wire.form.passwordPlatform === null ||
                    this.$wire.form.passwordPlatform.trim() === ""
                ) {
                    this.triggerAlert(
                        this.$e("enter_password_platform"),
                        "error",
                    );
                    this.inputRed(this.$refs.passwordPlatform);
                    return false;
                }
            }

            this.$wire.call("insertAccount");
        },

        // ? Colorear Borde Rojo Input
        inputRed(input_id) {
            input_id.style.borderColor = "red";
            this.$nextTick(() => {
                setTimeout(() => {
                    input_id.style.borderColor = "";
                }, 4000);
            });
        },
    }));
});
