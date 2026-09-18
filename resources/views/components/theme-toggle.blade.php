@props(['class' => ''])

{{--
    Toggle claro/oscuro. Extraído de navigation-menu para poder reutilizarlo en el
    layout público (landing y precios), donde no hay barra de navegación de la app.

    Escribe en localStorage.theme y emite `theme:changed`, que es lo que escucha
    resources/js/core/theme.js para repintar los gráficos sin recargar.
--}}
<button {{ $attributes->merge(['class' => 'flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm transition-all hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-indigo-400 ' . $class]) }}
        x-data
        @click="
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            window.dispatchEvent(new CustomEvent('theme:changed', { detail: { dark: isDark } }));
        "
        type="button"
        aria-label="{{ __('labels.toggle_theme') }}"
        title="{{ __('labels.toggle_theme') }}">
    <i class="fa-solid fa-moon dark:hidden"></i>
    <i class="fa-solid fa-sun hidden dark:inline"></i>
</button>
