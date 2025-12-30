document.addEventListener("alpine:init", () => {
    Alpine.data("users", () => ({
        showLoading: false,
        typeButton: "", // Tipo de Boton para los modals
        alertTitle: "", // Titulo para la alerta
        showAlert: false, // Mostrar alerta
        typeAlert: "error", // Tipo de Alerta
        bodyAlert: "", // Mensaje para la alerta
        height: "auto",
        isInitialized: false,
        registerSelected: false,
        init() {
            if (this.isInitialized) return; // Coomprobar que esta inicializado
            this.isInitialized = true;
            const self = this;

            if (!$.fn.DataTable.isDataTable("#table_users")) {
                self.tableTowns = $("#table_users")
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
                                d.town = self.$wire.filter_town;
                                d.name = self.$wire.filter_name;
                                d.active = self.$wire.filter_active;
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

            window.addEventListener("show-alert", (event) => {
                let e = event.detail[0];
                if (e.type == "success") {
                    this.showAlertSucces(e.title, e.message, e.type, e.event);
                } else {
                    this.showAlertFunc(e.title, this.$e(e.message), "error");
                }
            });
        },

        // ? Mostrar alerta de éxito
        showAlertSucces(alertTitle, bodyAlert, typeAlert, event) {
            if (event == "delete") {
                this.typeButton = "";
            }
            this.showAlertFunc(alertTitle, this.$s(bodyAlert), typeAlert);
            this.reloadTable();
            this.clean();
        },

        // ? Mostrar alerta
        showAlertFunc(alertTitle, bodyAlert, typeAlert) {
            this.alertTitle = this.$t(alertTitle);
            this.bodyAlert = bodyAlert;
            this.typeAlert = typeAlert;
            this.showAlert = true;
        },

        // ? Recargar la tabla
        reloadTable() {
            this.showLoading = true;
            const self = this;
            $("#table_users").DataTable().ajax.reload();
            $("#table_users").on("draw.dt", function () {
                $("#table_users").DataTable().columns.adjust();
                this.height =
                    document.getElementById("container_table").offsetHeight +
                    "px";

                document.getElementById("container_data").style.minHeight =
                    this.height;
                self.showLoading = false;
            });
        },

        // ? Eliminar usuario
        deleteUser() {
            this.typeButton = "deleteUser";
            this.showAlertFunc(
                "delete_user",
                this.$e("sure_delete_user") + this.$wire.user + "?",
                "warn",
            );
        },

        update() {
            // ? Comprobar nombre usuario vacio
            if (this.$wire.user == null || this.$wire.user.trim() == "") {
                this.showAlertFunc(
                    "update_user",
                    this.$e("empty_name_user"),
                    "error",
                );
                this.inputRed(this.$refs.input_name);
                return;
            }

            // ? Comprobar email
            if (this.$wire.email == null || this.$wire.email.trim() == "") {
                this.showAlertFunc(
                    "update_user",
                    this.$e("empty_email"),
                    "error",
                );
                this.inputRed(this.$refs.input_email);
                return;
            }

            // ? Comprobar formato email
            if (!this.isValidEmail(this.$wire.email)) {
                this.showAlertFunc(
                    "update_user",
                    this.$e("format_email"),
                    "error",
                );
                this.inputRed(this.$refs.input_email);
                return;
            }

            this.$wire.call("updateUser");
        },

        // ? Insertar usuario
        insertUser() {
            // ? Comprobar nombre usuario vacio
            if (this.$wire.user == null || this.$wire.user.trim() == "") {
                this.showAlertFunc(
                    "insert_user",
                    this.$e("empty_name_user"),
                    "error",
                );
                this.inputRed(this.$refs.input_name);
                return;
            }

            // ? Comprobar email
            if (this.$wire.email == null || this.$wire.email.trim() == "") {
                this.showAlertFunc(
                    "insert_user",
                    this.$e("empty_email"),
                    "error",
                );
                this.inputRed(this.$refs.input_email);
                return;
            }

            // ? Comprobar formato email
            if (!this.isValidEmail(this.$wire.email)) {
                this.showAlertFunc(
                    "insert_user",
                    this.$e("format_email"),
                    "error",
                );
                this.inputRed(this.$refs.input_email);
                return;
            }

            // ? Comprobar password vacio
            if (
                this.$wire.password == null ||
                this.$wire.password.trim() == ""
            ) {
                this.showAlertFunc(
                    "insert_user",
                    this.$e("empty_passwd"),
                    "error",
                );
                return;
            }

            // ? Comprobar Repeat password vacio
            if (
                this.$wire.repeat_password == null ||
                this.$wire.repeat_password.trim() == ""
            ) {
                this.showAlertFunc(
                    "insert_user",
                    this.$e("empty_r_passwd"),
                    "error",
                );
                return;
            }

            if (this.$wire.password != this.$wire.repeat_password) {
                this.showAlertFunc(
                    "insert_user",
                    this.$e("both_password_error"),
                    "error",
                );
                return;
            }

            this.$wire.call("insertUser");
        },

        // ? Comprobar formato email
        isValidEmail(email) {
            // Expresión regular para validar el formato del email
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
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

        // ? Mostrar
        showAlertFunc(alertTitle, bodyAlert, typeAlert) {
            this.alertTitle = this.$t(alertTitle);
            this.bodyAlert = bodyAlert;
            this.typeAlert = typeAlert;
            this.showAlert = true;
        },

        // ? Limpiar el formulario
        clean() {
            this.$wire.id_user = "";
            this.$wire.town_selected = "";
            this.$wire.email = "";
            this.$wire.user = "";
            this.$wire.password = "";
            this.$wire.repeat_password = "";
            this.$wire.active = false;
            this.registerSelected = false;
        },

        // ? Reiniciar los filtros
        resetFilters() {
            this.$wire.filter_name = "";
            this.$wire.filter_town = "";
            this.$wire.filter_active = "";
            this.reloadTable();
        },
    }));
});
