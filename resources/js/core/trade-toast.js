// Notificaciones de nuevos trades (SweetAlert + notificación de escritorio).
// Extraído del antiguo <script> inline de livewire/trade-toast.blade.php.
document.addEventListener("livewire:initialized", () => {
    Livewire.on("trigger-toast", (data) => {
        let notification = Array.isArray(data) ? data[0] : data;

        // 1. SWEETALERT (Dentro de la web)
        Swal.fire({
            title: notification.title,
            text: notification.message,
            icon: notification.type,
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 6000,
            timerProgressBar: true,
            background: "#ffffff",
            // Colores suaves para no estresar
            iconColor: notification.type === "success" ? "#10B981" : "#EF4444",
        });

        // 2. NOTIFICACIÓN DE SISTEMA (Escritorio)
        if (Notification.permission === "granted") {
            const iconUrl =
                notification.type === "success"
                    ? "https://cdn-icons-png.flaticon.com/512/190/190411.png" // Win (Verde)
                    : "https://cdn-icons-png.flaticon.com/512/929/929440.png"; // Loss (Escudo/Protección)

            try {
                const systemNotify = new Notification(notification.title, {
                    body: notification.message,
                    icon: iconUrl,
                    tag: "trade-alert", // Reemplaza la anterior para no llenar la pantalla
                    requireInteraction: false, // Se va sola a los pocos segundos
                    silent: false,
                });

                // Al hacer clic, llevamos al usuario de vuelta a la app
                systemNotify.onclick = function () {
                    window.focus();
                    this.close();
                };
            } catch (e) {
                console.error("Error notificación escritorio:", e);
            }
        }
    });
});
