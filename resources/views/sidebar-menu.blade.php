<aside id="logo-sidebar"
       class="fixed left-0 top-0 z-50 h-screen w-auto max-w-12 -translate-x-full transition-transform sm:translate-x-0"
       aria-label="Sidebar">

    <div class="h-full overflow-y-auto bg-secondary">
        <a class="flex h-14 w-12 items-center p-2"
           href="{{ route('dashboard') }}">
            <img class="flex w-full flex-col items-center"
                 src="{{ asset('img/detrafic_logo.png') }}"
                 alt="">
        </a>
        <ul class="cursor-pointer font-medium">
            {{-- ? Dashboard --}}
            @if (auth()->user()->canDo('dashboard', 'r'))
                <x-sidebar-element name="{{ __('menu.dashboard') }}"
                                   route="dashboard"
                                   icon="fas fa-chart-bar" />
            @endif



            {{-- ? Towns --}}
            @if (auth()->user()->canDo('towns', 'r'))
                <x-sidebar-element name="{{ __('menu.towns') }}"
                                   route="municipios"
                                   icon="fa-solid fa-city" />
            @endif

            {{-- ? Users --}}
            @if (auth()->user()->canDo('users', 'r'))
                <x-sidebar-element name="{{ __('menu.users') }}"
                                   route="users"
                                   icon="fa-solid fa-user-group" />
            @endif

            {{-- ? Logs --}}
            @if (auth()->user()->canDo('logs', 'r'))
                <x-sidebar-element name="{{ __('menu.logs') }}"
                                   route="logs"
                                   icon="fa-solid fa-file-lines" />
            @endif

            {{-- ? Rols --}}
            @if (auth()->user()->canDo('rols', 'r'))
                <x-sidebar-element name="{{ __('menu.rols') }}"
                                   route="rols"
                                   icon="fa-solid fa-users-gear" />
            @endif



        </ul>
    </div>
</aside>
