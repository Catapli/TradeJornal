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

// ============================================================
// 3. IMPORTAR TUS SCRIPTS (AHORA YA PUEDEN VER LAS GLOBALES)
// ============================================================
import "./logs/logs.js";
import "./users/users.js";
import "./accounts/accounts.js";
import "./dashboard/dashboard.js"; // <--- Ahora cuando esto cargue, window.createChart YA existe

Alpine.plugin(translations);
