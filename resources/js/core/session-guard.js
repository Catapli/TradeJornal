// Mantiene viva la sesión y maneja su expiración.
// Extraído del antiguo <script> inline de layouts/app.blade.php.

// Refresca el token CSRF cada 30 minutos (antes del timeout de sesión)
setInterval(
    async () => {
        try {
            const { token } = await fetch("/csrf-refresh").then((r) =>
                r.json(),
            );

            // Actualiza el token en Livewire v3
            if (window.livewireScriptConfig) {
                window.livewireScriptConfig.csrf = token;
            }

            // Actualiza el meta tag
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) meta.setAttribute("content", token);
        } catch (e) {
            console.error("Error refrescando token CSRF:", e);
        }
    },
    30 * 60 * 1000,
);

// Si aun así una petición Livewire devuelve 419 (sesión caducada), recarga con token fresco
document.addEventListener("livewire:init", () => {
    Livewire.hook("request", ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault(); // Evita el alert nativo de Livewire
                window.location.reload();
            }
        });
    });
});
