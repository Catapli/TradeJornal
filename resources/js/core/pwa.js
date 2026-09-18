/**
 * Registro del service worker (R4, base de PWA).
 *
 * El fichero vive en `public/sw.js` y no pasa por Vite a propósito: un service
 * worker solo controla lo que cuelga de su propia ruta, y desde `/build/` no
 * alcanzaría a las páginas de la aplicación.
 *
 * Sin `sw.js` no hay instalación posible, pero tampoco hay notificaciones: esta
 * versión solo hace que TradeForge se pueda instalar y que la pérdida de red
 * enseñe una pantalla propia en vez del dinosaurio del navegador.
 */
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("/sw.js").catch(() => {
            // Un registro fallido —modo incógnito, permisos, http sin TLS— no
            // puede romper la aplicación: la web sigue funcionando sin él.
        });
    });
}
