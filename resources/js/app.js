import "@dmuy/timepicker/dist/mdtimepicker.css";
import mdtimepicker from "@dmuy/timepicker";
import ApexCharts from "apexcharts";
import Swal from "sweetalert2";
import { createChart } from "lightweight-charts"; // 1. Importamos librería
import "./bootstrap";
import "./core/session-guard.js";
import "./core/theme.js";
import "./core/shortcuts.js";
import "./core/notify.js";
import "./core/trade-toast.js";
import "./core/pwa.js";
import translations from "./plugins/translations";

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { Spanish } from "flatpickr/dist/l10n/es.js";

// ============================================================
// 2. ASIGNAR VARIABLES GLOBALES (ANTES DE CARGAR TUS SCRIPTS)
// ============================================================
window.Swal = Swal;
window.ApexCharts = ApexCharts;
window.createChart = createChart; // <--- ¡AQUÍ LA HACEMOS GLOBAL!
window.mdtimepicker = mdtimepicker;

// Importar Trix y sus estilos
import Trix from "trix";
import "trix/dist/trix.css";

flatpickr.localize(Spanish);
window.flatpickr = flatpickr;

// ============================================================
// 3. IMPORTAR TUS SCRIPTS (AHORA YA PUEDEN VER LAS GLOBALES)
// Todos estáticos para garantizar el registro en alpine:init antes de que
// Livewire arranque Alpine.
// ============================================================
import "./logs/logs.js";
import "./accounts/accounts.js";
import "./dashboard/dashboard.js";
import "./journal/journal.js";
import "./trades/trades.js";
import "./playbook/playbook.js";
import "./session/session.js";
import "./session_history/session_history.js";
import "./propfirms/propfirms.js";
import "./reports/reports.js";
import "./trade_detail/trade_detail.js";
import "./backtesting/backtesting.js";
import "./backtesting/backtesting-charts.js";
import "./admin/adminpanel.js";

document.addEventListener("alpine:init", () => {
    /**
     * Tarjeta que el usuario puede plegar.
     *
     * Las tres tarjetas de puesta en marcha (primeros pasos, ritual pre-mercado y
     * datos de ejemplo) se apilan justo donde un principiante espera ver sus
     * números, y son las que más sitio ocupan precisamente cuando menos datos
     * hay. Cerrarlas del todo no sirve: la de primeros pasos aún tiene trabajo
     * pendiente. Plegarlas sí.
     *
     * El estado va en localStorage y no en la base: es una preferencia de vista
     * de este navegador, no un dato del usuario, y así no cuesta ni una consulta.
     * Todos los accesos van en try/catch porque en modo privado o con el
     * almacenamiento bloqueado el getter lanza.
     */
    Alpine.data("tfMinimizable", (key) => ({
        min: false,

        init() {
            try {
                this.min = localStorage.getItem("tf_min_" + key) === "1";
            } catch (e) {
                this.min = false;
            }
        },

        toggle() {
            this.min = !this.min;

            try {
                localStorage.setItem("tf_min_" + key, this.min ? "1" : "0");
            } catch (e) {
                // Sin persistencia, pero la tarjeta se pliega igual en esta sesión.
            }
        },
    }));

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
                // El signo se decide sobre el número YA redondeado: si no, un
                // -0,004 se pinta como "-0.00", que se lee como pérdida.
                const n = Number(num) || 0;
                const rounded = n.toFixed(2);
                const sign = Number(rounded) >= 0 ? "+" : "";

                return sign + (Number(rounded) === 0 ? "0.00" : rounded) + " " + symbol;
            };

            if (this.mode === "percentage") {
                return formatNumber(percent, "%");
            }

            return formatNumber(amount, "$");
        },
    });
});

Alpine.plugin(translations);
