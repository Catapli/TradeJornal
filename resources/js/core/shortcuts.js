/**
 * Atajos de teclado globales.
 *
 *   d  → panel
 *   t  → operaciones
 *   i  → importar historial
 *   n  → nueva operación (si la página tiene botón; si no, va a operaciones)
 *   /  → enfocar el buscador de la página
 *
 * Se ignoran mientras se escribe: nada peor que un atajo que se dispara a mitad
 * de una nota. También se respetan los modificadores, para no pisar los atajos
 * del navegador (Ctrl+D, Cmd+T…).
 */

const isTyping = (target) => {
    if (!target) return false;

    const tag = target.tagName?.toLowerCase();

    return (
        tag === "input" ||
        tag === "textarea" ||
        tag === "select" ||
        target.isContentEditable === true ||
        target.closest?.("trix-editor") !== null
    );
};

const go = (url) => {
    if (url) window.location.href = url;
};

document.addEventListener("keydown", (event) => {
    if (event.ctrlKey || event.metaKey || event.altKey) return;
    if (isTyping(event.target)) return;

    const routes = window.tjRoutes ?? {};

    switch (event.key) {
        case "/": {
            const search = document.querySelector("[data-shortcut-search]");
            if (search) {
                event.preventDefault();
                search.focus();
                search.select?.();
            }
            break;
        }

        case "n":
        case "N": {
            const newButton = document.querySelector("[data-shortcut-new]");
            event.preventDefault();

            if (newButton) {
                newButton.click();
            } else {
                go(routes.trades);
            }
            break;
        }

        case "d":
        case "D":
            event.preventDefault();
            go(routes.dashboard);
            break;

        case "t":
        case "T":
            event.preventDefault();
            go(routes.trades);
            break;

        case "i":
        case "I":
            event.preventDefault();
            go(routes.import);
            break;

        default:
            break;
    }
});
