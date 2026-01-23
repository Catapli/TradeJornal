<aside id="logo-sidebar"
       class="fixed left-0 top-0 z-50 h-screen w-auto max-w-12 -translate-x-full transition-transform sm:translate-x-0"
       aria-label="Sidebar">

    <div class="h-full overflow-y-auto bg-secondary"
         wire:ignore>
        <a class="flex h-14 w-12 items-center p-2"
           href="{{ route('dashboard') }}">
            <div class="flex shrink-0 items-center space-x-3">
                {{-- <img class="flex w-full max-w-64 flex-col items-center p-2"
                     src="{{ asset('img/logo_trader_h.png') }}"
                     alt=""> --}}
            </div>
        </a>
        <ul class="cursor-pointer font-medium">
            {{-- ? Dashboard --}}
            <x-sidebar-element name="{{ __('menu.dashboard') }}"
                               route="dashboard"
                               icon="fas fa-chart-bar" />

            {{-- ? Account Page --}}
            <x-sidebar-element name="{{ __('menu.accounts') }}"
                               route="cuentas"
                               icon="fa-solid fa-circle-dollar-to-slot" />

            {{-- ? Journal Page --}}
            <x-sidebar-element name="{{ __('menu.journal') }}"
                               route="journal"
                               icon="fa-solid fa-book" />

            {{-- ? Calendario Economico --}}
            <x-sidebar-element name="{{ __('menu.calendar') }}"
                               route="calendar"
                               icon="fa-solid fa-calendar-days" />

            {{-- ? Calendario Economico --}}
            <x-sidebar-element name="{{ __('menu.trades') }}"
                               route="trades"
                               icon="fa-solid fa-chart-simple" />




            {{--  {{~~ ? Towns ~~}}
            @if (auth()->user()->canDo('towns', 'r'))
                <x-sidebar-element name="{{ __('menu.towns') }}"
                                   route="municipios"
                                   icon="fa-solid fa-city" />
            @endif

            {{~~ ? Users ~~}}
            @if (auth()->user()->canDo('users', 'r'))
                <x-sidebar-element name="{{ __('menu.users') }}"
                                   route="users"
                                   icon="fa-solid fa-user-group" />
            @endif

            {{~~ ? Logs ~~}}
            @if (auth()->user()->canDo('logs', 'r'))
                <x-sidebar-element name="{{ __('menu.logs') }}"
                                   route="logs"
                                   icon="fa-solid fa-file-lines" />
            @endif

            {{~~ ? Rols ~~}}
            @if (auth()->user()->canDo('rols', 'r'))
                <x-sidebar-element name="{{ __('menu.rols') }}"
                                   route="rols"
                                   icon="fa-solid fa-users-gear" />
            @endif --}}



        </ul>
    </div>
</aside>
