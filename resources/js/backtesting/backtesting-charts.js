window.initBacktestCharts = function (metrics, wire) {
    if (typeof ApexCharts === "undefined") return;
    ["chart-equity", "chart-r-dist"].forEach((id) => {
        const el = document.querySelector("#" + id);
        if (el?._apexChart) {
            el._apexChart.destroy();
            el._apexChart = null;
        }
    });

    const currency = metrics.r_mode
        ? "R"
        : (wire?._component?.currency ?? "USD");
    const isRMode = metrics.equity_curve.unit === "R";
    const yFmt = isRMode
        ? (v) => (v > 0 ? "+" : "") + v.toFixed(2) + "R"
        : (v) => v.toFixed(0) + " " + currency;

    // ── Curva de Capital ─────────────────────────────────────────
    const equityEl = document.querySelector("#chart-equity");
    if (equityEl && metrics.equity_curve.equity.length) {
        const c = new ApexCharts(equityEl, {
            chart: {
                type: "area",
                height: 180,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: { enabled: true, speed: 400 },
            },
            series: [
                {
                    name: isRMode ? "Capital (R)" : "Capital",
                    data: metrics.equity_curve.equity,
                },
                {
                    name: "Drawdown",
                    data: metrics.equity_curve.drawdown.map((v) => -v),
                },
            ],
            labels: metrics.equity_curve.labels,
            colors: ["#10b981", "#ef4444"],
            stroke: { curve: "smooth", width: [2, 1.5] },
            fill: {
                type: "gradient",
                gradient: { opacityFrom: [0.3, 0.15], opacityTo: [0.02, 0.02] },
            },
            xaxis: {
                labels: {
                    show: metrics.equity_curve.labels.length <= 30,
                    style: { fontSize: "11px" },
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: { formatter: yFmt, style: { fontSize: "11px" } },
                forceNiceScale: true,
            },
            tooltip: { y: { formatter: yFmt } },
            grid: { borderColor: "#f1f5f9", strokeDashArray: 4 },
            legend: { position: "top", fontSize: "12px" },
        });
        c.render();
        equityEl._apexChart = c;
    }

    // ── Distribución de R ────────────────────────────────────────
    const rDistEl = document.querySelector("#chart-r-dist");
    if (rDistEl && metrics.r_distribution.values.length) {
        const c = new ApexCharts(rDistEl, {
            chart: {
                type: "bar",
                height: 200,
                toolbar: { show: false },
                animations: { enabled: true, speed: 400 },
            },
            series: [{ name: "Trades", data: metrics.r_distribution.values }],
            xaxis: {
                categories: metrics.r_distribution.labels,
                labels: { style: { fontSize: "11px" } },
            },
            yaxis: {
                labels: {
                    formatter: (v) => Math.round(v),
                    style: { fontSize: "11px" },
                },
            },
            colors: metrics.r_distribution.colors,
            plotOptions: {
                bar: { distributed: true, borderRadius: 4, columnWidth: "65%" },
            },
            legend: { show: false },
            grid: { borderColor: "#f1f5f9", strokeDashArray: 4 },
            tooltip: { y: { formatter: (v) => v + " trades" } },
        });
        c.render();
        rDistEl._apexChart = c;
    }
};
