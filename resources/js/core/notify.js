// Sistema unificado de notificaciones (SweetAlert2).
// Único punto de render de toasts de la app. Escucha los canales históricos
// (show-alert, notify, error, toast) para no tener que tocar el backend.
// Las notificaciones de nuevos trades (trigger-toast) viven aparte en
// trade-toast.js porque además lanzan notificación de escritorio.

const TYPE_MAP = {
    success: "success",
    error: "error",
    warning: "warning",
    warn: "warning", // alias usado por el antiguo <x-modal-template>
    info: "info",
    question: "question",
};

// Traduce el mensaje si resulta ser una clave del diccionario global
// (users/rols envían claves; el resto de páginas ya envían texto final).
function translateMaybe(message, icon) {
    if (typeof message !== "string") return message;
    const T = window.translations ?? {};
    if (icon === "error" || icon === "warning") {
        return T.errors?.[message] ?? T.success?.[message] ?? message;
    }
    return T.success?.[message] ?? T.errors?.[message] ?? message;
}

// API pública: render de un toast. Es el único sitio que llama a Swal.fire
// para notificaciones genéricas.
window.tjToast = function (message, type = "success") {
    if (!window.Swal || !message) return;

    const icon = TYPE_MAP[type] ?? "success";

    window.Swal.fire({
        toast: true,
        position: "top-end",
        icon,
        title: translateMaybe(message, icon),
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
        background: window.tjTheme?.colors().surface ?? "#ffffff",
        color: window.tjTheme?.isDark() ? "#f3f4f6" : undefined,
    });
};

// Normaliza los payloads heterogéneos a {message, type}.
function normalize(payload, defaultType) {
    const data = Array.isArray(payload) ? payload[0] : payload;
    if (data == null) return null;
    if (typeof data === "string") return { message: data, type: defaultType };
    return {
        message: data.message ?? data.text ?? "",
        type: data.type ?? defaultType,
    };
}

function show(payload, defaultType = "success") {
    const n = normalize(payload, defaultType);
    if (n && n.message) window.tjToast(n.message, n.type);
}

// Registro único (evita listeners duplicados si el bundle se evalúa dos veces).
if (!window.__tjNotifyReady) {
    window.__tjNotifyReady = true;

    // Livewire 3 emite los dispatch como CustomEvent en window (detail = [params]).
    window.addEventListener("show-alert", (e) => show(e.detail, "success"));
    window.addEventListener("toast", (e) => show(e.detail, "success"));

    document.addEventListener("livewire:initialized", () => {
        Livewire.on("notify", (data) => show(data, "success"));
        Livewire.on("error", (data) => show(data, "error"));
    });
}
