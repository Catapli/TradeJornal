<div wire:poll.3s="checkNotifications">
    <script>
        document.addEventListener('livewire:initialized', () => {

            Livewire.on('trigger-toast', (data) => {
                let notification = Array.isArray(data) ? data[0] : data;

                // 1. SWEETALERT (Dentro de la web)
                Swal.fire({
                    title: notification.title,
                    text: notification.message,
                    icon: notification.type,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 6000,
                    timerProgressBar: true,
                    background: '#ffffff',
                    // Colores suaves para no estresar
                    iconColor: notification.type === 'success' ? '#10B981' : '#EF4444'
                });

                // 2. NOTIFICACIÓN DE SISTEMA (Escritorio)
                if (Notification.permission === "granted") {

                    // Definir iconos (Usa tus rutas locales o estos de CDN como fallback)
                    let iconUrl = notification.type === 'success' ?
                        'https://cdn-icons-png.flaticon.com/512/190/190411.png' // Win (Verde)
                        :
                        'https://cdn-icons-png.flaticon.com/512/929/929440.png'; // Loss (Escudo/Protección)

                    // Intentar usar imágenes locales si existen
                    // iconUrl = notification.type === 'success' ? '/images/win.png' : '/images/loss.png';

                    try {
                        const systemNotify = new Notification(notification.title, {
                            body: notification.message,
                            icon: iconUrl,
                            tag: 'trade-alert', // Reemplaza la anterior para no llenar la pantalla
                            requireInteraction: false, // Se va sola a los pocos segundos
                            silent: false
                        });

                        // Al hacer clic, llevamos al usuario al Dashboard
                        systemNotify.onclick = function() {
                            window.focus();
                            // Opcional: window.location.href = '/dashboard';
                            this.close();
                        };
                    } catch (e) {
                        console.error("Error notificación escritorio:", e);
                    }
                }
            });
        });
    </script>
</div>
