// Fuente única de verdad del tema (claro/oscuro) para el JS.
//
// El tema vive en la clase `dark` del <html>: la pone el script anti-FOUC de
// los layouts y la alterna el botón de navigation-menu. Este módulo expone esa
// información al resto del JS y avisa a quien se suscriba cuando cambia, para
// que los gráficos y los toasts no necesiten recargar la página.

const NEUTRALS = {
    light: {
        text: "#6b7280", // gray-500
        grid: "#e5e7eb", // gray-200
        surface: "#ffffff",
        border: "#e5e7eb",
    },
    dark: {
        text: "#9ca3af", // gray-400
        grid: "#374151", // gray-700
        surface: "#1f2937", // gray-800
        border: "#374151",
    },
};

const isDark = () => document.documentElement.classList.contains("dark");
const mode = () => (isDark() ? "dark" : "light");
const colors = () => NEUTRALS[mode()];

// --- ApexCharts -----------------------------------------------------------

// Sólo el "chrome" del gráfico (ejes, rejilla, leyenda, tooltip). Los colores
// de las series se dejan intactos: emerald/rose funcionan en ambos temas.
function apexThemeOptions() {
    const c = colors();
    const m = mode();

    return {
        theme: { mode: m },
        chart: { background: "transparent", foreColor: c.text },
        tooltip: { theme: m },
        grid: { borderColor: c.grid },
        xaxis: { labels: { style: { colors: c.text } } },
        yaxis: { labels: { style: { colors: c.text } } },
        legend: { labels: { colors: c.text } },
    };
}

// Mezcla profunda donde el tema gana sobre lo que traiga la config original
// (muchas configs traen colores neutros hardcodeados de la época sin tema).
function deepMerge(base, override) {
    if (Array.isArray(base)) return base.map((item) => deepMerge(item, override));

    const out = { ...base };
    for (const [key, value] of Object.entries(override)) {
        const isPlainObject = (v) => v && typeof v === "object" && !Array.isArray(v);
        out[key] = isPlainObject(value) && isPlainObject(out[key]) ? deepMerge(out[key], value) : value;
    }
    return out;
}

function applyApexTheme(options) {
    const theme = apexThemeOptions();
    const merged = deepMerge(options, theme);

    // yaxis admite objeto o array de ejes; deepMerge ya recorre el array,
    // pero el override tiene que aplicarse a cada eje por separado.
    if (Array.isArray(options.yaxis)) {
        merged.yaxis = options.yaxis.map((axis) => deepMerge(axis, { labels: { style: { colors: theme.yaxis.labels.style.colors } } }));
    }

    return merged;
}

// --- Registro de suscriptores --------------------------------------------

const listeners = new Set();
const charts = new Set();

function notify() {
    charts.forEach((chart) => {
        try {
            chart.updateOptions(apexThemeOptions(), false, false);
        } catch (e) {
            charts.delete(chart); // gráfico ya destruido
        }
    });
    listeners.forEach((cb) => cb(mode()));
}

window.tjTheme = {
    isDark,
    mode,
    colors,
    apexThemeOptions,
    applyApexTheme,

    // Suscripción genérica: cb(mode) en cada cambio de tema.
    onChange(cb) {
        listeners.add(cb);
        return () => listeners.delete(cb);
    },

    // Registra un gráfico ApexCharts para que siga al tema automáticamente.
    registerChart(chart) {
        if (chart) charts.add(chart);
        return chart;
    },

    unregisterChart(chart) {
        charts.delete(chart);
    },

    // Lo llama el botón del menú tras alternar la clase en <html>.
    notify,
};

// Fábrica que sustituye a `new ApexCharts(el, options)`: aplica el tema actual
// y deja el gráfico suscrito a los cambios posteriores.
window.tjChart = function (el, options) {
    const chart = new window.ApexCharts(el, applyApexTheme(options));
    return window.tjTheme.registerChart(chart);
};

// Si el usuario no ha elegido tema, seguimos al sistema operativo en caliente.
window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (e) => {
    if (localStorage.getItem("theme")) return;
    document.documentElement.classList.toggle("dark", e.matches);
    notify();
});

window.addEventListener("theme:changed", notify);
