document.addEventListener("alpine:init", () => {
    Alpine.data("logs", () => ({
        height: "auto",
        registerSelected: false,

        //Modal Alerta
        showAlert: false, // Mostrar alerta
        alertTitle: "", // Titulo para la alerta
        typeAlert: "error", // Tipo de Alerta
        typeButton: "",
        bodyAlert: "", // Mensaje para la alerta
        tableTowns: null,
        isInitialized: false,
        showLoading: false,
        init() {
            if (this.isInitialized) return; // Coomprobar que esta inicializado
            this.isInitialized = true;
            const self = this;

            if (!$.fn.DataTable.isDataTable("#table_logs")) {
                self.tableTowns = $("#table_logs")
                    .on("init.dt", () => this.resizeContainers())
                    .on("draw.dt", () => this.resizeContainers())
                    .DataTable({
                        ajax: {
                            url: "/logs/data",
                            data: function (d) {
                                d.date = self.$wire.logFilter.date;
                                d.town = self.$wire.logFilter.town;
                                d.user = self.$wire.logFilter.user;
                                d.action = self.$wire.logFilter.action;
                            },
                        },
                        lengthMenu: [5, 10, 20, 25, 50],
                        pageLength: 5,
                        order: [[1, "desc"]],
                        columns: [
                            { data: "id" },
                            { data: "created_at" },
                            { data: "user.name" },
                            { data: "action" },
                            { data: "form" },
                        ],
                        columnDefs: [
                            { width: "5%", targets: 0 },
                            {
                                targets: 3, // Índice de la columna a formatear
                                render: function (data, type, row) {
                                    if (data == "SELECT") {
                                        return (
                                            '<span class="bg-red-400">' +
                                            data +
                                            "</span>"
                                        );
                                    } else {
                                        return data;
                                    }
                                },
                            },
                        ],

                        pagingType: "numbers",
                        language: {
                            url: "/datatable/es-ES.json",
                        },
                        createdRow: function (row, data, dataIndex) {
                            $(row).on("click", function () {
                                self.$wire.call("findByID", data.id);
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
            });
        },

        reloadTable() {
            this.showLoading = true;
            const self = this;
            $("#table_logs").DataTable().ajax.reload();
            $("#table_logs").on("draw.dt", function () {
                $("#table_logs").DataTable().columns.adjust();
                this.height =
                    document.getElementById("container_table").offsetHeight +
                    "px";

                document.getElementById("container_data").style.minHeight =
                    this.height;
                self.showLoading = false;
            });
        },

        resizeContainers() {
            $("#table_logs").DataTable().columns.adjust();

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

        resetFilters() {
            this.$wire.logFilter.date = null;
            this.$wire.logFilter.town = null;
            this.$wire.logFilter.user = null;
            this.$wire.logFilter.action = null;
            this.reloadTable();
        },
    }));
});
