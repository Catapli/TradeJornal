<a href="/">
    {{-- Claro: logo a color. Oscuro: mismo logo en blanco (brightness-0 invert), igual
         que el logo del panel derecho de login/register — el texto oscuro del original
         se pierde contra el fondo dark:bg-gray-900 de x-authentication-card. --}}
    <img class="rounded-xl border border-black object-scale-down dark:hidden"
         src="{{ asset('img/logo_trader_h.webp') }}"
         alt="TradeForge">
    <img class="hidden object-scale-down brightness-0 invert dark:block"
         src="{{ asset('img/logo_trader_h.webp') }}"
         alt="TradeForge">
</a>
