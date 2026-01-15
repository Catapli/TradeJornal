import "datatables.net-dt";
import "datatables.net-dt/css/dataTables.dataTables.min.css";
import "@dmuy/timepicker/dist/mdtimepicker.css";
import mdtimepicker from "@dmuy/timepicker";
import Chart from "chart.js/auto";
import ApexCharts from "apexcharts";
import $ from "jquery";
import "./bootstrap";
import "./logs/logs.js";
import "./users/users.js";
import "./accounts/accounts.js";
import "./dashboard/dashboard.js";
import translations from "./plugins/translations";
window.$ = window.jQuery = $;
window.Chart = Chart;
window.ApexCharts = ApexCharts;
window.mdtimepicker = mdtimepicker;

Alpine.plugin(translations);
