/**
 * Service worker de TradeForge.
 *
 * Hace dos cosas y ninguna más:
 *
 *  1. Permite instalar la aplicación (un manifiesto sin service worker no basta
 *     en Chrome ni en Android).
 *  2. Cuando se cae la red, sirve una pantalla propia en lugar del error del
 *     navegador.
 *
 * Lo que **no** hace, deliberadamente: cachear HTML ni respuestas JSON de la
 * aplicación. Todo eso lleva datos de una cuenta concreta y quedaría en el disco
 * del navegador después de cerrar sesión — en un ordenador compartido, el
 * siguiente en entrar vería las operaciones del anterior. Solo se guardan
 * ficheros estáticos, que son iguales para todo el mundo.
 */

const VERSION = "tf-v1";
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = "/sin-conexion";

/** Rutas de solo lectura y sin datos personales. */
const PRECACHE = [
    OFFLINE_URL,
    "/img/pwa/icon-192.png",
    "/img/pwa/icon-512.png",
];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting())
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => !key.startsWith(VERSION))
                        .map((key) => caches.delete(key))
                )
            )
            .then(() => self.clients.claim())
    );
});

/** Estáticos que sí se pueden guardar: los sirve Vite o están en public/. */
function isStaticAsset(url) {
    return (
        url.pathname.startsWith("/build/") ||
        url.pathname.startsWith("/img/") ||
        url.pathname.startsWith("/fonts/")
    );
}

self.addEventListener("fetch", (event) => {
    const request = event.request;

    if (request.method !== "GET") {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Navegación: siempre la red primero — el diario tiene que estar al día.
    // Si no hay red, la pantalla de sin conexión.
    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request).catch(() =>
                caches.match(OFFLINE_URL).then((cached) => cached || Response.error())
            )
        );
        return;
    }

    if (!isStaticAsset(url)) {
        return;
    }

    // Estáticos: caché primero y refresco en segundo plano. Los ficheros de Vite
    // llevan huella en el nombre, así que un cambio nunca reutiliza el anterior.
    event.respondWith(
        caches.match(request).then((cached) => {
            const network = fetch(request)
                .then((response) => {
                    if (response && response.status === 200) {
                        const copy = response.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                    }

                    return response;
                })
                .catch(() => cached);

            return cached || network;
        })
    );
});
