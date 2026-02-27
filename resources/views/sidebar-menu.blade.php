<aside class="fixed inset-y-0 left-0 z-50 flex w-20 flex-col border-r border-gray-800 bg-[#0B1120] shadow-2xl">

    {{-- 1. LOGO HEADER --}}
    <div class="flex h-16 shrink-0 items-center justify-center border-b border-gray-800/50">
        {{-- ✅ FIX: shrink-0 evita que el logo se comprima en pantallas pequeñas --}}
        <a class="block"
           href="{{ route('dashboard') }}">
            <img class="h-10 w-10 brightness-0 invert"
                 src="{{ asset('img/logo_o.png') }}"
                 alt="">
        </a>
    </div>

    {{-- 2. MENÚ --}}
    {{-- ✅ FIX: overflow-y-auto permite scroll interno si no caben todos los items --}}
    {{-- ✅ FIX: overflow-x-visible mantiene los tooltips visibles lateralmente --}}
    {{-- ✅ FIX: gap-1 en vez de space-y-4 para adaptarse mejor a la altura --}}
    <nav class="flex flex-1 flex-col items-center gap-1 overflow-y-auto overflow-x-visible py-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

        <x-sidebar-element name="{{ __('menu.dashboard') }}"
                           route="dashboard"
                           icon="fas fa-chart-pie" />

        <x-sidebar-element name="{{ __('menu.accounts') }}"
                           route="cuentas"
                           icon="fa-solid fa-wallet" />

        @if (Auth::user()->subscribed('default'))
            <x-sidebar-element name="{{ __('menu.journal') }}"
                               route="journal"
                               icon="fa-solid fa-book-bookmark" />
        @endif

        @if (Auth::user()->subscribed('default'))
            <div class="my-1 h-px w-8 shrink-0 bg-gray-800"></div>
            <x-sidebar-element name="{{ __('menu.session') }}"
                               route="session"
                               icon="fa-solid fa-bolt" />
            <x-sidebar-element name="{{ __('menu.history_session') }}"
                               route="session-history"
                               icon="fa-solid fa-note-sticky" />
        @endif

        <div class="my-1 h-px w-8 shrink-0 bg-gray-800"></div>

        <x-sidebar-element name="{{ __('menu.trades') }}"
                           route="trades"
                           icon="fa-solid fa-list-check" />

        @if (Auth::user()->subscribed('default'))
            <x-sidebar-element name="{{ __('menu.calendar') }}"
                               route="calendar"
                               icon="fa-regular fa-calendar" />
            <x-sidebar-element name="{{ __('menu.laboratory') }}"
                               route="reports"
                               icon="fa-solid fa-flask" />
            <x-sidebar-element name="{{ __('menu.playbook') }}"
                               route="playbook"
                               icon="fa-solid fa-chess-board" />
        @endif

    </nav>

    {{-- 3. FOOTER --}}
    {{-- ✅ FIX: Eliminado h-20 fijo. Ahora es shrink-0 con padding controlado --}}
    {{-- ✅ FIX: pb-safe-area para respetar la taskbar de Windows/iOS --}}
    <div class="flex shrink-0 flex-col items-center gap-2 border-t border-gray-800/50 py-3">
        @if (auth()->user()->isSuperAdmin())
            <x-sidebar-element name="{{ __('menu.manage_prop_firm') }}"
                               route="manage-prop-frim"
                               icon="fa-solid fa-shield-halved"
                               color="text-rose-500" />
            <x-sidebar-element name="{{ __('menu.logs') }}"
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
                <span class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded border border-gray-700 bg-gray-900 px-2 py-1 text-xs font-bold text-white opacity-0 transition-opacity group-hover:opacity-100">
                    {{ __('labels.exit') }}
                </span>
            </button>
        </form>
    </div>

</aside>
