/**
 * adminpanel.js — TradeForge Admin Panel
 *
 * Responsabilidad: SOLO estado de UI.
 * Nada de lógica de negocio aquí — eso vive en AdminPanel.php
 */

document.addEventListener("alpine:init", () => {
    Alpine.data("adminPanel", () => ({
        // ─── Estado de tabs ──────────────────────────────────────────────

        activeTab: "overview",

        tabs: [
            { id: "overview", label: "Overview", icon: "fa-gauge" },
            { id: "users", label: "Usuarios", icon: "fa-users" },
            { id: "storage", label: "Almacenamiento", icon: "fa-hard-drive" },
            { id: "queues", label: "Colas", icon: "fa-layer-group" },
            { id: "mt5", label: "Monitor MT5", icon: "fa-tower-broadcast" },
        ],

        // ─── Estado local de filtros (espejos de las props Livewire) ─────

        /**
         * Espejo local de $wire.search.
         * Alpine lo actualiza al instante en cada keystroke.
         * Livewire se entera solo tras el debounce de 300ms en la vista.
         */
        localSearch: "",

        /**
         * Espejo local de $wire.filterStatus.
         * Mismo patrón: cambio visual inmediato, Livewire después.
         */
        localStatus: "all",

        // ─── Métodos de tabs ─────────────────────────────────────────────

        /**
         * Cambia el tab activo.
         * 1. Actualiza Alpine (instantáneo — sin round-trip).
         * 2. Sincroniza con $wire para que #[Url] actualice el query param.
         * 3. Actualiza el tab activo para que Alpine re-evalúe los bindings :class.
         */
        switchTab(tab) {
            this.activeTab = tab;
            this.$wire.set("activeTab", tab, false); // false = no forzar re-render
        },

        /**
         * Llamado desde x-init para sincronizar el estado Alpine
         * con el valor que Livewire ya tiene al montar el componente.
         * Necesario cuando el usuario llega con ?tab=users en la URL.
         *
         * @param {string} tab
         */
        syncTab(tab) {
            if (tab && this.tabs.some((t) => t.id === tab)) {
                this.activeTab = tab;
            }
        },

        /**
         * Indica si un tab es el activo.
         * Usado en :class y x-show de la vista.
         *
         * @param {string} tab
         * @returns {boolean}
         */
        isActive(tab) {
            return this.activeTab === tab;
        },

        // ─── Helpers de color para storage ──────────────────────────────

        /**
         * Clase CSS para la barra de progreso según porcentaje de uso.
         * Centralizado aquí para no meter lógica condicional en la vista.
         *
         * @param {number} percent
         * @returns {string}
         */
        storageBarColor(percent) {
            if (percent >= 80) return "bg-error";
            if (percent >= 60) return "bg-warning";
            return "bg-success";
        },

        /**
         * Clase CSS para el texto del porcentaje de uso.
         *
         * @param {number} percent
         * @returns {string}
         */
        storageTextColor(percent) {
            if (percent >= 80) return "text-error";
            if (percent >= 60) return "text-warning";
            return "text-success";
        },
    }));
});
