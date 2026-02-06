<aside class="fixed inset-y-0 left-0 z-50 flex w-20 flex-col justify-between border-r border-gray-800 bg-[#0B1120] shadow-2xl">

    {{-- 1. LOGO HEADER --}}
    <div class="flex h-16 items-center justify-center border-b border-gray-800/50">
        <a class="block"
           href="{{ route('dashboard') }}">
            {{-- Usa solo el ICONO de tu logo, no el texto, porque no cabe en 80px --}}
            {{-- Si no tienes icono suelto, usa un FontAwesome provisional --}}
            <img class="h-20 w-20 brightness-0 invert"
                 src="{{ asset('img/logo_o.png') }}"
                 alt="">
        </a>
    </div>

    {{-- 2. MENÚ (Overflow visible para que se vean los tooltips) --}}
    <div class="flex flex-1 flex-col items-center space-y-4 overflow-visible py-6">

        <x-sidebar-element name="Dashboard"
                           route="dashboard"
                           icon="fas fa-chart-pie" />
        <x-sidebar-element name="Cuentas"
                           route="cuentas"
                           icon="fa-solid fa-wallet" />
        @if (Auth::user()->subscribed('default'))
            <x-sidebar-element name="Journal"
                               route="journal"
                               icon="fa-solid fa-book-bookmark" />
        @endif

        @if (Auth::user()->subscribed('default'))
            <div class="my-2 h-px w-8 bg-gray-800"></div> {{-- Separador --}}
            <x-sidebar-element name="Session"
                               route="session"
                               icon="fa-solid fa-bolt" />

            <x-sidebar-element name="Session History"
                               route="session-history"
                               icon="fa-solid fa-note-sticky" />
        @endif
        <div class="my-2 h-px w-8 bg-gray-800"></div> {{-- Separador --}}

        <x-sidebar-element name="Operaciones"
                           route="trades"
                           icon="fa-solid fa-list-check" />
        @if (Auth::user()->subscribed('default'))
            <x-sidebar-element name="Calendario"
                               route="calendar"
                               icon="fa-regular fa-calendar" />
            <x-sidebar-element name="Laboratorio"
                               route="reports"
                               icon="fa-solid fa-flask" />
            <x-sidebar-element name="{{ __('menu.playbook') }}"
                               route="playbook"
                               icon="fa-solid fa-chess-board" />
        @endif


    </div>

    {{-- 3. FOOTER (Perfil/Salir) --}}
    <div class="flex h-20 flex-col items-center justify-center space-y-3 border-t border-gray-800/50 pb-4">
        @if (auth()->user()->isSuperAdmin())
            <x-sidebar-element name="Prop Firms"
                               route="manage-prop-frim"
                               icon="fa-solid fa-shield-halved"
                               color="text-rose-500" />

            <x-sidebar-element name="Logs"
                               route="manage-logs"
                               icon="fa-solid fa-flag"
                               color="text-rose-500" />
        @endif

        <form method="POST"
              action="{{ route('logout') }}">
            @csrf
            <button class="group relative flex h-10 w-10 items-center justify-center rounded-xl text-gray-500 transition-all hover:bg-gray-800 hover:text-white"
                    type="submit">
                <i class="fa-solid fa-power-off"></i>

                {{-- Tooltip Salir --}}
                <span class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded border border-gray-700 bg-gray-900 px-2 py-1 text-xs font-bold text-white opacity-0 transition-opacity group-hover:opacity-100">
                    Salir
                </span>
            </button>
        </form>
    </div>
</aside>
