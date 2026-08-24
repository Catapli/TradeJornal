document.addEventListener("alpine:init", () => {
    // COMPONENTE 1: Lógica del Dashboard (Gráficos, Alertas, UI General)
    Alpine.data("journal", () => ({
        typeButton: "", // Tipo de Boton para los modals

        init() {
            // El toast lo pinta el sistema global (core/notify.js).
            // Aquí solo cerramos el modal al recibir respuesta del servidor.
            window.addEventListener("show-alert", () => {
                this.showModal = false;
            });
        },

        //?========= Mostrar Alerta (delegada al sistema global)
        triggerAlert(message, type = "error") {
            window.tjToast(message, type);
        },
    }));
});
