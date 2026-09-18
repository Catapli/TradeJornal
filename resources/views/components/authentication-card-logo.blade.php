<a href="/">
    {{-- Claro: wordmark a color. Oscuro: el logo-icono en blanco (brightness-0 invert),
         igual que el de la columna derecha de login/register — el wordmark completo
         invertido salía ilegible a este tamaño, el icono sí funciona en blanco. --}}
    <img class="h-32 w-auto rounded-xl border border-black object-contain dark:hidden"
         src="{{ asset('img/logo_trader_h.webp') }}"
         alt="TradeForge">
    <img class="hidden h-32 w-auto object-contain brightness-0 invert dark:block"
         src="{{ asset('img/logo_o.webp') }}"
         alt="TradeForge">
</a>
