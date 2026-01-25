document.addEventListener("alpine:init", () => {
    // COMPONENTE 1: Lógica del Dashboard (Gráficos, Alertas, UI General)
    Alpine.data("dashboardLogic", () => ({
        showLoadingGrafic: false,
        timeframe: "all",
        showAlert: false,
        bodyAlert: "",
        typeAlert: "error",
        showModal: false, // Control del modal
        labelTitleModal: "",
        typeButton: "", // Tipo de Boton para los modals
        tableHistory: null,
        showDeleteModal: false, // Variable para mostrar el modal
        accountToDeleteId: null, // ID temporal

        init() {
            const self = this; // Capturamos el scope de Alpine
            // Inicializamos gráfico
            this.initChart();

            // Listeners de eventos globales
            window.addEventListener("timeframe-updated", (e) => {
                this.timeframe = e.detail.timeframe;
                this.showLoadingGrafic = true;
                this.initChart();
            });

            window.addEventListener("show-alert", (e) => {
                const data = e.detail[0] || e.detail; // Ajuste por si viene en array o no
                this.showModal = false; // Cerramos modal si está abierto
                this.triggerAlert(data.message, data.type);
            });

            // LISTENER PARA RECARGAR LA TABLA CUANDO CAMBIA LA CUENTA
            window.addEventListener("account-change", (e) => {
                // ... tu lógica de timeframe ...
                this.timeframe = e.detail.timeframe;
                console.log("Prueba");

                // 👇 RECARGAR DATATABLE
                if (this.tableHistory) {
                    // reload(null, false) recarga los datos manteniendo la paginación actual (opcional)
                    this.tableHistory.ajax.reload();
                }
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

                this.showModal = false; // Cerramos modal si está abierto

                this.triggerAlert(
                    this.$s("account_updated_success"),
                    "success",
                );
            });

            // LISTENER PARA RECARGAR LA TABLA CUANDO CAMBIA LA CUENTA
            window.addEventListener("account-created", (e) => {
                // ... tu lógica de timeframe ...
                this.timeframe = e.detail.timeframe;

                // 👇 RECARGAR DATATABLE
                if (this.tableHistory) {
                    // reload(null, false) recarga los datos manteniendo la paginación actual (opcional)
                    this.tableHistory.ajax.reload();
                }

                this.showModal = false; // Cerramos modal si está abierto

                this.triggerAlert(
                    this.$s("account_created_success"),
                    "success",
                );
            });

            if (!$.fn.DataTable.isDataTable("#table_history")) {
                self.tableHistory = $("#table_history").DataTable({
                    ajax: {
                        url: "/trades/data",
                        data: function (d) {
                            let id = self.$wire.selectedAccountId;
                            d.id = id;
                        },
                    },
                    // Ordenamos por fecha de entrada descendente por defecto
                    order: [[1, "desc"]],
                    lengthChange: false,
                    searching: false,

                    columnDefs: [
                        {
                            targets: "_all", // Aplica a todas las columnas
                            className: "dt-left", // Usa 'dt-left' si no usas Bootstrap
                        },
                    ],

                    // 👇 AQUÍ ESTÁ EL CAMBIO IMPORTANTE
                    columns: [
                        // COLUMNA 1: ID Orden / Activo / Tipo
                        // COLUMNA 1: ID Orden / Activo / Tipo
                        {
                            data: "ticket",
                            name: "ticket",
                            title: "Orden / Activo",
                            render: function (data, type, row) {
                                // Lógica de colores (igual que antes)
                                let isBuy =
                                    row.direction === "buy" ||
                                    row.type === "BUY" ||
                                    row.direction === "long";
                                let badgeClass = isBuy
                                    ? "bg-[#00800061] text-[#0eb90e] border-emerald-700"
                                    : "bg-[#7f101061] text-[#eb0b0b] border-red-700";
                                let label = isBuy ? "Buy" : "Sell";

                                return `
            <!-- Contenedor Flex Fila: Alinea Badge (Izq) y Textos (Der) -->
            <div class="flex items-center gap-3">
                
                <!-- 1. BADGE IZQUIERDA (Centrado verticalmente gracias a items-center del padre) -->
                <div class="flex-shrink-0">
                    <span class="text-sm px-2 py-1 rounded border ${badgeClass} font-bold uppercase tracking-wider">
                        ${label}
                    </span>
                </div>

                <!-- 2. TEXTOS DERECHA (Apilados verticalmente) -->
                <div class="flex flex-col items-start gap-0.5">
                    
                    <!-- Ticket ID + Copiar -->
                    <div class="font-bold text-white text-sm flex items-center gap-2">
                        ${data}
                    </div>

                    <!-- Símbolo (Debajo del ID) -->
                    <span class="text-xs font-medium text-gray-400">
                        ${row.symbol}
                    </span>
                </div>
            </div>
        `;
                            },
                        },

                        // COLUMNA 2: Tiempo (Abrir / Cerrar)
                        {
                            data: "entry_time",
                            name: "entry_time",
                            title: "Tiempo",
                            render: function (data, type, row) {
                                return `
                    <div class="flex flex-col text-sm gap-1">
                        <div class="flex justify-start gap-2">
                            <span class="text-gray-300 ">Abrir:</span>
                            <span class="text-white font-mono ">${data}</span>
                        </div>
                        <div class="flex justify-start gap-2">
                            <span class="text-gray-300 ">Cerrar:</span>
                            <span class="text-white font-mono ">${row.exit_time}</span>
                        </div>
                    </div>
                `;
                            },
                        },

                        // COLUMNA 3: Lotes
                        {
                            data: "size",
                            name: "size",
                            title: "Lotes",
                            class: "text-left font-bold text-white", // Centrado y negrita
                            render: function (data) {
                                return data; // Simple
                            },
                        },

                        // COLUMNA 4: Precio (Abrir / Cerrar)
                        {
                            data: "entry_price",
                            name: "entry_price",
                            title: "Precio",
                            render: function (data, type, row) {
                                return `
                    <div class="flex flex-col text-sm gap-1">
                        <div class="flex gap-2">
                            <span class="text-gray-300 w-10 ">Abrir:</span>
                            <span class="text-white font-mono font-bold ">${data}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-gray-300 w-10 ">Cerrar:</span>
                            <span class="text-white font-mono font-bold ">${row.exit_price}</span>
                        </div>
                    </div>
                `;
                            },
                        },

                        // COLUMNA 5: Beneficios (PnL)
                        {
                            data: "pnl",
                            name: "pnl",
                            title: "Beneficio",
                            class: "text-right", // Alineado a la derecha
                            render: function (data, type, row) {
                                let val = parseFloat(data);

                                // Determinar color
                                let colorClass =
                                    val >= 0
                                        ? "text-emerald-400"
                                        : "text-red-400";

                                // Formatear a 2 decimales (ej: "10.50" o "-5.20")
                                let formatted = val.toFixed(2);

                                // Si es mayor que 0, le pegamos el "+" delante.
                                // Si es negativo, ya trae el "-" incluido en 'formatted'.
                                // Si es 0, se queda como "0.00"
                                let textWithSign =
                                    val > 0 ? "+" + formatted : formatted;

                                return `
            <span class="font-bold text-base font-mono ${colorClass}">
                ${textWithSign}
            </span>
        `;
                            },
                        },
                    ],
                    // Estilos generales de la tabla para modo oscuro
                    createdRow: function (row, data, dataIndex) {
                        // Añadir clases a la fila (borde inferior, fondo oscuro, hover)
                        $(row).addClass(
                            "border-b border-gray-700 hover:bg-gray-800 transition-colors",
                        );
                    },
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

        // 1. ABRIR MODAL
        confirmDeleteAccount(id) {
            this.accountToDeleteId = id;
            this.showDeleteModal = true;
        },

        // 2. EJECUTAR BORRADO (Llamado desde el botón rojo del modal)
        executeDelete() {
            console.log("Prueba");
            if (this.accountToDeleteId) {
                this.$wire.deleteAccount(this.accountToDeleteId);
                this.showDeleteModal = false;
                this.accountToDeleteId = null;
            }
        },

        setTimeframe(value) {
            this.showLoadingGrafic = true;
            this.$wire.setTimeframe(value);
        },

        showOpenModalCreate() {
            this.showModal = true;
            this.labelTitleModal = this.$t("create_account");
        },

        showOpenModalEdit() {
            this.showModal = true;
            this.labelTitleModal = this.$t("edit_account");
        },

        // initChart() {
        //     const canvas = this.$refs.canvas;
        //     if (!canvas) return;

        //     this.showLoadingGrafic = false;

        //     // Usamos nextTick para asegurar que el DOM está listo
        //     this.$nextTick(() => {
        //         if (window.balanceChart) window.balanceChart.destroy();

        //         const ctx = canvas.getContext("2d");
        //         // Accedemos a los datos de Livewire de forma segura
        //         const labels = this.$wire.balanceChartData?.labels || [];
        //         const datasets = this.$wire.balanceChartData?.datasets || [];

        //         if (!labels.length) return;

        //         window.balanceChart = new Chart(ctx, {
        //             type: "line",
        //             data: { labels, datasets },
        //             options: {
        //                 responsive: true,
        //                 maintainAspectRatio: false,
        //                 interaction: { intersect: false, mode: "index" },
        //                 // Tus opciones de chart...
        //             },
        //         });
        //     });
        // },
        initChart() {
            const chartEl = this.$refs.chart;
            if (!chartEl) return;

            this.$nextTick(() => {
                // Limpiar instancia previa para evitar duplicados
                if (window.balanceChart) {
                    window.balanceChart.destroy();
                }

                const categories =
                    this.$wire.balanceChartData?.categories || [];
                const seriesData = this.$wire.balanceChartData?.series || [];

                // Si no hay datos, salimos (o podrías mostrar un div de "Sin datos")
                if (!categories.length) return;

                const options = {
                    series: seriesData,
                    chart: {
                        type: "area",
                        height: 350,
                        fontFamily: "Inter, sans-serif",
                        toolbar: { show: false },
                        animations: { enabled: true },
                    },
                    dataLabels: { enabled: false },

                    // --- ESTILOS DE LÍNEA ---
                    stroke: {
                        curve: "smooth",
                        width: [2, 3, 2], // MFE fino, Balance grueso, MAE fino
                        dashArray: [5, 0, 5], // MFE y MAE punteados, Balance sólido
                    },

                    // --- COLORES SEMÁNTICOS ---
                    // Azul (Cielo/Potencial), Verde (Realidad), Rojo (Riesgo/Suelo)
                    colors: ["#3B82F6", "#10B981", "#EF4444"],

                    // --- RELLENO ---
                    // Solo rellenamos el Balance Real para darle peso visual y no ensuciar
                    fill: {
                        type: ["solid", "gradient", "solid"],
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.05,
                            stops: [0, 100],
                        },
                        opacity: [0, 0.3, 0], // Transparente, Semitransparente, Transparente
                    },

                    xaxis: {
                        categories: categories,
                        tooltip: { enabled: false },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            style: { colors: "#9ca3af", fontSize: "12px" },
                        },
                    },

                    yaxis: {
                        labels: {
                            style: { colors: "#9ca3af", fontSize: "12px" },
                            formatter: (val) => val.toFixed(2) + " €",
                        },
                    },

                    grid: {
                        borderColor: "#f3f4f6",
                        strokeDashArray: 4,
                        yaxis: { lines: { show: true } },
                    },

                    // --- TOOLTIP COMPARTIDO ---
                    tooltip: {
                        theme: "light",
                        shared: true, // Muestra los 3 valores a la vez al pasar el ratón
                        intersect: false,
                        y: {
                            formatter: function (val) {
                                return val.toFixed(2) + " €";
                            },
                        },
                    },

                    // --- LEYENDA ARRIBA A LA DERECHA ---
                    legend: {
                        position: "top",
                        horizontalAlign: "right",
                        offsetY: -20,
                    },
                };

                window.balanceChart = new ApexCharts(chartEl, options);
                window.balanceChart.render();
                this.showLoadingGrafic = false;
            });
        },
    }));

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
        mode: "create", // 'create' o 'edit'
        editingAccountId: null,
        isLoading: false, // <--- LA CLAVE PARA QUE NO SE ROMPA LA CASCADA

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
                if (this.isLoading) return; // <--- STOP SI CARGAMOS DATOS

                this.selectedProgramId = "";
                this.selectedSize = "";
                this.selectedLevelId = "";

                // Lógica del servidor
                const firm = this.allFirms.find((f) => f.id == value);
                this.selectedServer = firm ? firm.server : "";

                this.syncToLivewire();
            });

            this.$watch("selectedProgramId", () => {
                if (this.isLoading) return; // <--- STOP
                this.selectedSize = "";
                this.selectedLevelId = "";
                this.syncToLivewire();
            });

            this.$watch("selectedSize", () => {
                if (this.isLoading) return; // <--- STOP
                this.selectedLevelId = "";
                this.syncToLivewire();
            });

            this.$watch("selectedLevelId", () => {
                if (this.isLoading) return; // <--- STOP
                this.syncToLivewire();
            });

            this.$watch("syncronize", () => {
                if (this.isLoading) return; // <--- STOP
                this.syncToLivewire();
            });

            this.$watch("platformBroker", () => {
                if (this.isLoading) return; // <--- STOP
                this.syncToLivewire();
            });

            this.$watch("loginPlatform", () => {
                if (this.isLoading) return; // <--- STOP
                this.syncToLivewire();
            });

            this.$watch("passwordPlatform", () => {
                if (this.isLoading) return; // <--- STOP
                this.syncToLivewire();
            });

            // ESCUCHAMOS EL EVENTO DE EDICIÓN
            window.addEventListener("open-modal-edit", (event) => {
                let data = event.detail[0].data; // Livewire envía array
                this.loadEditData(data);
            });

            // ESCUCHAMOS APERTURA DE CREACIÓN (Para limpiar)
            window.addEventListener("open-modal-create", () => {
                this.resetForm();
                this.mode = "create";
                this.labelTitleModal = this.$t("create_account"); // O tu traducción
                this.showOpenModalCreate(); // Tu función que pone showModal = true
            });

            // ❌ ELIMINADO: this.syncToLivewire()
            // No sincronizamos al inicio para evitar errores de $wire undefined
        },

        // FUNCIÓN PARA CARGAR DATOS DE EDICIÓN
        loadEditData(data) {
            // 1. Bloqueamos los watchers para evitar que limpien los campos al asignar
            this.isLoading = true;

            // 2. Modo Edición
            this.mode = "edit";
            this.editingAccountId = data.accountId;
            this.nameAccount = data.name;

            // 3. ASIGNACIÓN EN CASCADA (Forzando tipos)

            // Empresa
            this.selectedFirmId = data.firmId;
            this.selectedServer = data.server;

            // Programa
            this.selectedProgramId = data.programId;

            // Tamaño (CRÍTICO: Forzamos a Float para coincidir con getSizes que usa parseFloat)
            // Si no hacemos esto, '100000' (string) != 100000 (number) y getCurrencies falla.
            this.selectedSize = parseFloat(data.size);

            // Divisa / Nivel Final
            this.selectedLevelId = data.levelId;

            // Datos Sync
            this.syncronize = Boolean(data.sync);
            this.platformBroker = data.platform;
            this.loginPlatform = data.login;
            this.passwordPlatform = ""; // Dejar vacío por seguridad

            // 4. Sincronizar y Abrir
            this.syncToLivewire();

            this.$nextTick(() => {
                this.isLoading = false; // Reactivamos watchers
                this.showOpenModalEdit(); // Abrimos el modal
            });
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

                if (this.mode === "create") {
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
            }
            this.syncToLivewire();

            // AL FINAL, DECIDIMOS QUÉ LLAMAR
            if (this.mode === "edit") {
                // Llamamos a update pasando el ID
                this.$wire.call("updateAccount", this.editingAccountId);
            } else {
                this.$wire.call("insertAccount");
            }
        },

        resetForm() {
            this.isLoading = true;
            this.mode = "create"; // <--- Importante resetear el modo
            this.editingAccountId = null;

            this.selectedFirmId = "";
            this.selectedProgramId = "";
            this.selectedSize = "";
            this.selectedLevelId = "";
            this.nameAccount = "";
            this.syncronize = false;
            this.loginPlatform = "";
            this.passwordPlatform = "";

            this.isLoading = false;
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
