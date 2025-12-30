document.addEventListener("alpine:init", () => {
    Alpine.data("rols", () => ({
        height: "auto",
        registerSelected: false,

        columnStates: {
            acceder: false,
            escribir: false,
            eliminar: false,
            descargar: false,
        },

        //Modal Alerta
        showAlert: false, // Mostrar alerta
        alertTitle: "", // Titulo para la alerta
        typeAlert: "error", // Tipo de Alerta
        typeButton: "",
        bodyAlert: "", // Mensaje para la alerta
        table_rols: null,
        isInitialized: false,
        showLoading: false,
        init() {
            if (this.isInitialized) return; // Coomprobar que esta inicializado
            this.isInitialized = true;
            const self = this;

            if (!$.fn.DataTable.isDataTable("#table_rols")) {
                self.table_rols = $("#table_rols")
                    .on("init.dt", () => this.resizeContainers())
                    .on("draw.dt", () => this.resizeContainers())
                    .DataTable({
                        ajax: {
                            url: "/rols/data",
                        },
                        lengthMenu: [5, 10, 20, 25, 50],
                        pageLength: 5,
                        columns: [
                            { data: "id" },
                            { data: "name" },
                            { data: "label" },
                        ],
                        pagingType: "numbers",
                        language: {
                            url: "/datatable/es-ES.json",
                        },
                        createdRow: function (row, data, dataIndex) {
                            $(row).on("click", function () {
                                // self.clean();
                                self.$wire.call("findByID", data.id);
                                self.registerSelected = true;
                            });
                        },
                    });
            }

            window.addEventListener("show-alert", (event) => {
                let e = event.detail[0];
                if (e.type == "success") {
                    this.showAlertSucces(e.title, e.message, e.type, e.event);
                } else {
                    this.showAlertFunc(e.title, this.$e(e.message), "error");
                }
                this.typeButton = "";
            });
        },

        showAlertSucces(alertTitle, bodyAlert, typeAlert, event) {
            if (event == "delete") {
                this.typeButton = "";
            }
            this.showAlertFunc(alertTitle, this.$s(bodyAlert), typeAlert);
            this.reloadTable();
            this.clean();
        },

        toggleColumnCheckboxes(columnClass) {
            const headerCheckbox = event.target;
            const isChecked = headerCheckbox.checked;
            document.querySelectorAll(`.${columnClass}`).forEach((cb) => {
                cb.checked = isChecked;
                // Opcional: dispara 'change' si necesitas que otras partes reaccionen
                cb.dispatchEvent(new Event("change", { bubbles: true }));
            });
        },

        toggleRowCheckboxes(rowCheckbox) {
            const isChecked = rowCheckbox.checked;
            const row = rowCheckbox.closest("tr");
            // Seleccionar SOLO los checkboxes de permisos (los 4)
            const permCheckboxes = row.querySelectorAll(
                ".permission-col-acceder, .permission-col-escribir, .permission-col-eliminar, .permission-col-descargar",
            );
            permCheckboxes.forEach((cb) => (cb.checked = isChecked));
        },

        toggleAllCheckboxes(masterCheckbox) {
            const isChecked = masterCheckbox.checked;
            // Seleccionar TODOS los checkboxes de permisos en el tbody
            const allPermCheckboxes = document.querySelectorAll(
                ".permission-col-acceder, .permission-col-escribir, .permission-col-eliminar, .permission-col-descargar",
            );
            allPermCheckboxes.forEach((cb) => (cb.checked = isChecked));
        },

        reloadTable() {
            this.showLoading = true;
            $("#table_rols").DataTable().ajax.reload();
        },

        resizeContainers() {
            $("#table_rols").DataTable().columns.adjust();

            let htable =
                document.getElementById("container_table").offsetHeight + "px";
            let hdata =
                document.getElementById("container_data").offsetHeight + "px";

            if (htable > hdata) {
                this.height = htable;
            } else {
                this.height = hdata;
            }

            // ? Colocamos el height del contenedor de la tabla en auto
            document.getElementById("container_table").style.minHeight =
                this.height;

            document.getElementById("container_data").style.minHeight =
                this.height;

            this.showLoading = false;
        },

        showAlertFunc(alertTitle, bodyAlert, typeAlert) {
            this.alertTitle = this.$t(alertTitle);
            this.bodyAlert = bodyAlert;
            this.typeAlert = typeAlert;
            this.showAlert = true;
        },

        inputRed(input_id) {
            input_id.style.borderColor = "red";
            this.$nextTick(() => {
                setTimeout(() => {
                    input_id.style.borderColor = "";
                }, 4000);
            });
        },

        insert() {
            //? Comprobacion de nombre
            if (this.$wire.name == null || this.$wire.name.trim() == "") {
                this.showAlertFunc(
                    "insert_role",
                    this.$e("empty_name_rol"),
                    "error",
                );
                this.inputRed(this.$refs.input_name);
                return;
            }

            // ? Comprobacion de apodo
            if (this.$wire.label == null || this.$wire.label.trim() == "") {
                this.showAlertFunc(
                    "insert_role",
                    this.$e("empty_nickname_rol"),
                    "error",
                );
                this.inputRed(this.$refs.input_label);
                return;
            }

            if (this.$wire.permissions.length == 0) {
                this.showAlertFunc(
                    "insert_role",
                    this.$e("empty_permissions"),
                    "error",
                );
                return;
            }

            this.$wire.call("insertRol");
        },

        deleteRol() {
            this.typeButton = "deleteRol";
            this.showAlertFunc(
                "delete_role",
                this.$e("sure_delete_role") + this.$wire.name + "?",
                "warn",
            );
        },

        update() {
            //? Comprobacion de nombre
            if (this.$wire.name == null || this.$wire.name.trim() == "") {
                this.showAlertFunc(
                    "update_role",
                    this.$e("empty_name_rol"),
                    "error",
                );
                this.inputRed(this.$refs.input_name);
                return;
            }

            // ? Comprobacion de apodo
            if (this.$wire.label == null || this.$wire.label.trim() == "") {
                this.showAlertFunc(
                    "update_role",
                    this.$e("empty_nickname_rol"),
                    "error",
                );
                this.inputRed(this.$refs.input_label);
                return;
            }

            if (this.$wire.permissions.length == 0) {
                this.showAlertFunc(
                    "update_role",
                    this.$e("empty_permissions"),
                    "error",
                );
                return;
            }

            this.$wire.call("updateRol");
        },

        clean() {
            this.$wire.id_rol = "";
            this.$wire.name = "";
            this.$wire.label = "";
            this.$wire.permissions = [];
            this.registerSelected = false;

            // 👇 Limpiar visualmente todos los checkboxes de permisos y de fila
            this.$nextTick(() => {
                // Desmarcar checkboxes de permisos (los 4 por fila)
                document
                    .querySelectorAll(
                        ".permission-col-acceder, .permission-col-escribir, .permission-col-eliminar, .permission-col-descargar",
                    )
                    .forEach((cb) => (cb.checked = false));

                // Desmarcar checkboxes de la primera columna (los de "marcar fila")
                document
                    .querySelectorAll(
                        'tbody tr td:first-child input[type="checkbox"]',
                    )
                    .forEach((cb) => (cb.checked = false));

                // Desmarcar checkboxes del tfoot (columnas y "marcar todo")
                document
                    .querySelectorAll('tfoot input[type="checkbox"]')
                    .forEach((cb) => (cb.checked = false));
            });
        },

        resetFilters() {
            this.$wire.filters.filterTown = "";
            this.$wire.filters.filterName = "";
            this.$wire.filters.filterUser = "";
            this.reloadTable();
        },
    }));
});
