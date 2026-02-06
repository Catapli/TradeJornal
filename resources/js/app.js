import "datatables.net-dt";
import "datatables.net-dt/css/dataTables.dataTables.min.css";
import "@dmuy/timepicker/dist/mdtimepicker.css";
import mdtimepicker from "@dmuy/timepicker";
import Chart from "chart.js/auto";
import ApexCharts from "apexcharts";
import { createChart } from "lightweight-charts"; // 1. Importamos librería
import $ from "jquery";
import "./bootstrap";
import translations from "./plugins/translations";

// ============================================================
// 2. ASIGNAR VARIABLES GLOBALES (ANTES DE CARGAR TUS SCRIPTS)
// ============================================================
window.$ = window.jQuery = $;
window.Chart = Chart;
window.ApexCharts = ApexCharts;
window.createChart = createChart; // <--- ¡AQUÍ LA HACEMOS GLOBAL!
window.mdtimepicker = mdtimepicker;

// Importar Trix y sus estilos
import Trix from "trix";
import "trix/dist/trix.css";

// ============================================================
// 3. IMPORTAR TUS SCRIPTS (AHORA YA PUEDEN VER LAS GLOBALES)
// ============================================================
import "./logs/logs.js";
import "./users/users.js";
import "./accounts/accounts.js";
import "./dashboard/dashboard.js";
import "./journal/journal.js";
import "./trades/trades.js";
import "./economic/economic.js";
import "./playbook/playbook.js";
import "./session/session.js";
import "./session_history/session_history.js";
import "./propfirms/propfirms.js";

document.addEventListener("alpine:init", () => {
    // Store Global de Preferencias
    Alpine.store("viewMode", {
        mode: localStorage.getItem("tf_view_mode") || "currency", // 'currency' o 'percentage'

        toggle() {
            this.mode = this.mode === "currency" ? "percentage" : "currency";
            localStorage.setItem("tf_view_mode", this.mode);
        },

        format(amount, percent) {
            // Helper para añadir el + si es positivo (el - sale solo)
            const formatNumber = (num, symbol) => {
                const n = Number(num);
                const sign = n >= 0 ? "+" : "";
                return sign + n.toFixed(2) + " " + symbol;
            };

            if (this.mode === "percentage") {
                return formatNumber(percent, "%");
            }
            return formatNumber(amount, "$");
        },
    });
});

Alpine.plugin(translations);
