{{--
    Carril lateral.

    Cerrado mide 80px y solo enseña iconos; al acercar el ratón se despliega a
    220px **por encima** del contenido (es `fixed`, así que nada se recoloca) y
    muestra los nombres agrupados en bloques.

    De doce entradas se ha bajado a nueve. Las que faltan no han
    desaparecido: son destinos puntuales que se alcanzan desde donde tienen
    sentido, no desde un menú permanente.
      · Importar        → botón en Operaciones (y atajo «I»)
      · Conexión        → botón en Cuentas
      · Histórico       → botón en Sesión
      · Backtesting     → botón en Estrategias
--}}
<aside class="group/sidebar fixed inset-y-0 left-0 z-50 flex w-20 flex-col overflow-hidden border-r border-gray-800 bg-[#0B1120] shadow-2xl transition-[width] duration-200 ease-out hover:w-[220px]">

    {{-- 1. CABECERA --}}
    <div class="flex h-16 shrink-0 items-center border-b border-gray-800/50">
        <a class="flex w-full items-center"
           href="{{ route('dashboard') }}"
           wire:navigate>
            <span class="flex w-20 shrink-0 justify-center">
                <img class="h-10 w-10 brightness-0 invert"
                     src="{{ asset('img/logo_o.webp') }}"
                     alt="{{ config('app.name', 'TradeForge') }}">
            </span>

            <span class="whitespace-nowrap text-base font-black tracking-tight text-white opacity-0 transition-opacity duration-200 group-hover/sidebar:opacity-100">
                {{ config('app.name', 'TradeForge') }}
            </span>
        </a>
    </div>

    {{-- 2. MENÚ --}}
    <nav class="flex flex-1 flex-col gap-1 overflow-y-auto overflow-x-hidden px-2 py-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

        {{-- Los módulos PRO se muestran siempre, con candado si no hay suscripción.
             Ocultarlos hacía que el usuario gratuito no supiera qué compraría. --}}
        @php($locked = !Auth::user()->hasProAccess())
        @php($pendientes = app(\App\Actions\Mistakes\CountPendingReview::class)->execute((int) Auth::id()))

        <x-sidebar-section :title="__('menu.group_daily')" />

        <x-sidebar-element name="{{ __('menu.dashboard') }}"
                           route="dashboard"
                           icon="fas fa-chart-pie" />

        <x-sidebar-element name="{{ __('menu.accounts') }}"
                           route="cuentas"
                           icon="fa-solid fa-wallet" />

        <x-sidebar-element name="{{ __('menu.trades') }}"
                           route="trades"
                           icon="fa-solid fa-list-check" />

        <x-sidebar-section :title="__('menu.group_method')" />

        <x-sidebar-element name="{{ __('menu.session') }}"
                           route="session"
                           icon="fa-solid fa-bolt"
                           :locked="$locked" />

        <x-sidebar-element name="{{ __('menu.journal') }}"
                           route="journal"
                           icon="fa-solid fa-book-bookmark"
                           :locked="$locked" />

        {{-- El repaso no lleva candado: es entrada de datos y está abierto a todos. --}}
        <x-sidebar-element name="{{ __('menu.review') }}"
                           route="review"
                           icon="fa-solid fa-clipboard-check"
                           :badge="$pendientes" />

        <x-sidebar-element name="{{ __('menu.mentor') }}"
                           route="mentor"
                           icon="fa-solid fa-user-graduate"
                           :locked="$locked" />

        {{-- Destino del correo del domingo: tiene que ser encontrable sin el correo. --}}
        <x-sidebar-element name="{{ __('menu.weekly_review') }}"
                           route="weekly.review"
                           icon="fa-solid fa-calendar-check"
                           :locked="$locked" />

        <x-sidebar-section :title="__('menu.group_analysis')" />

        <x-sidebar-element name="{{ __('menu.laboratory') }}"
                           route="reports"
                           icon="fa-solid fa-flask"
                           :locked="$locked" />

        <x-sidebar-element name="{{ __('menu.playbook') }}"
                           route="playbook"
                           icon="fa-solid fa-chess-board"
                           :locked="$locked" />

    </nav>

    {{-- 3. PIE --}}
    <div class="flex shrink-0 flex-col gap-1 border-t border-gray-800/50 px-2 py-3">
        @if (auth()->user()->isSuperAdmin())
            <x-sidebar-element name="{{ __('menu.manage_prop_firm') }}"
                               route="manage-prop-frim"
                               icon="fa-solid fa-shield-halved"
                               color="text-rose-500" />
            <x-sidebar-element name="{{ __('menu.logs') }}"
                               route="manage-logs"
                               icon="fa-solid fa-flag"
                               color="text-rose-500" />
            <x-sidebar-element name="{{ __('menu.admin_panel') }}"
                               route="admin-panel"
                               icon="fa-solid fa-screwdriver-wrench"
                               color="text-rose-500" />
        @endif

        <form method="POST"
              action="{{ route('logout') }}">
            @csrf
            <button class="group flex h-11 w-full items-center rounded-xl text-gray-500 transition-all hover:bg-gray-800 hover:text-white"
                    type="submit">
                <span class="flex w-16 shrink-0 justify-center">
                    <i class="fa-solid fa-power-off"></i>
                </span>
                <span class="whitespace-nowrap pr-4 text-sm font-semibold opacity-0 transition-opacity duration-200 group-hover/sidebar:opacity-100">
                    {{ __('labels.exit') }}
                </span>
            </button>
        </form>
    </div>

</aside>
