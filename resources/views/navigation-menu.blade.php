<div>
    {{-- Navbar TradeForge --}}
    <nav class="fixed left-0 right-0 top-0 z-[150] h-14 bg-slate-900 bg-gradient-to-r px-4 shadow-2xl backdrop-blur-md sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between">
            {{-- LOGO TRADEFORGE --}}
            <div class="flex shrink-0 items-center space-x-3">
                <img class="flex w-full max-w-64 flex-col items-center p-2"
                     src="{{ asset('img/logo.png') }}"
                     alt="">
            </div>

            {{-- USER DROPDOWN --}}
            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right"
                            width="48">
                    <x-slot name="trigger">
                        <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-2 text-sm font-semibold text-white shadow-md backdrop-blur-sm transition-all hover:bg-white/30">
                            {{ Str::limit(Auth::user()->name, 12) }}
                            <svg class="-me-0.5 ms-2 h-4 w-4"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="1.5"
                                      d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </span>
                    </x-slot>
                    <x-slot name="content">
                        <div class="block px-4 py-2 text-xs font-medium text-gray-400">Cuenta</div>
                        <x-dropdown-link href="{{ route('profile.show') }}">Perfil</x-dropdown-link>
                        <div class="border-t border-gray-200"></div>
                        <form method="POST"
                              action="{{ route('logout') }}"
                              x-data>
                            @csrf
                            <x-dropdown-link href="{{ route('logout') }}"
                                             @click.prevent="$root.submit();">
                                Cerrar Sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- MOBILE HAMBURGER --}}
            <div class="flex items-center sm:hidden">
                <button class="rounded-lg p-2 text-slate-300 transition-all hover:bg-white/20 hover:text-white"
                        @click="$dispatch('open-mobile-menu')">
                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>
</div>
