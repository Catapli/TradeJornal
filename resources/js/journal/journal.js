document.addEventListener("alpine:init", () => {
    // COMPONENTE 1: Lógica del Dashboard (Gráficos, Alertas, UI General)
    Alpine.data("journal", () => ({
        //?====== Variables de Alerta
        typeAlert: "error",
        showAlert: false,
        bodyAlert: "",
        typeButton: "", // Tipo de Boton para los modals

        init() {
            //?========= Escuchador Alerta
            window.addEventListener("show-alert", (e) => {
                const data = e.detail[0] || e.detail; // Ajuste por si viene en array o no
                this.showModal = false; // Cerramos modal si está abierto
                this.triggerAlert(data.message, data.type);
            });
        },

        //?========= Mostrar Alerta
        triggerAlert(message, type = "error") {
            this.bodyAlert = message;
            this.typeAlert = type;
            this.showAlert = true;
            // Opcional: auto-ocultar a los 3 seg
            setTimeout(() => (this.showAlert = false), 4000);
        },
    }));
});
