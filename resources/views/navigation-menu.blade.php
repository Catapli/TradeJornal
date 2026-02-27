<div>
    {{-- Navbar TradeForge --}}
    {{-- CAMBIO: bg-white, border-b, eliminamos el margin-left negativo si lo hubiera --}}
    {{-- IMPORTANTE: sm:ml-20 para que empiece DESPUÉS del sidebar en escritorio --}}
    <nav class="fixed left-0 right-0 top-0 z-[40] h-16 border-b border-gray-200 bg-white/80 backdrop-blur-md transition-all sm:ml-20">

        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

            {{-- IZQUIERDA: TÍTULO O LOGO (En móvil se ve logo, en desktop título de sección o breadcrumbs) --}}
            <div class="flex items-center gap-4">
                {{-- Hamburger Móvil (Solo visible en móvil) --}}
                <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 sm:hidden"
                        @click="$dispatch('open-mobile-menu')">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                {{-- En Desktop, el logo ya está en el Sidebar. Aquí podemos poner un Título dinámico o dejarlo limpio --}}
                <div class="hidden font-bold text-gray-800 sm:block">
                    {{-- Puedes poner breadcrumbs aquí --}}
                    <span class="text-indigo-600">TradeForge</span>
                </div>

                {{-- Logo solo para Móvil --}}
                <div class="flex shrink-0 items-center sm:hidden">
                    <img class="h-8 w-auto"
                         src="{{ asset('img/logo.png') }}"
                         alt="TradeForge">
                </div>
            </div>

            {{-- ESTADO DE SUSCRIPCIÓN --}}
            <div class="mr-2 hidden items-center md:flex">
                @if (Auth::user()->subscribed('default'))
                    {{-- USUARIO PRO (Badge elegante) --}}
                    <div class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 shadow-sm">
                        <i class="fa-solid fa-crown text-emerald-500"></i>
                        <span>{{ __('labels.pro_plan') }}</span>
                    </div>
                @else
                    {{-- USUARIO FREE (Botón Call to Action) --}}
                    <a class="group flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600 transition-all hover:border-indigo-300 hover:bg-indigo-100 hover:text-indigo-700 hover:shadow-md"
                       href="{{ route('pricing') }}">
                        <i class="fa-regular fa-star transition-transform group-hover:scale-110"></i>
                        <span>{{ __('labels.upgrade_plan') }}</span>
                    </a>
                @endif
            </div>

            {{-- DERECHA: USER & IDIOMA --}}
            <div class="flex items-center gap-3">

                @if (Auth::user()->subscribed('default'))
                    <div class="flex items-center rounded-lg">
                        <a class="relative flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 ease-out"
                           href="{{ route('alerts') }}">

                            {{-- Icono --}}
                            <i class="fa-solid fa-bell text-lg transition-transform duration-200 group-hover:scale-110"></i> </a>
                    </div>
                @endif

                <div class="flex items-center rounded-lg bg-gray-200 p-1"
                     x-data>
                    <button class="rounded-md px-3 py-1 text-xs font-bold transition-all"
                            @click="$store.viewMode.mode = 'currency'; localStorage.setItem('tf_view_mode', 'currency')"
                            :class="$store.viewMode.mode === 'currency' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        $
                    </button>
                    <button class="rounded-md px-3 py-1 text-xs font-bold transition-all"
                            @click="$store.viewMode.mode = 'percentage'; localStorage.setItem('tf_view_mode', 'percentage')"
                            :class="$store.viewMode.mode === 'percentage' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        %
                    </button>
                </div>

                {{-- SELECTOR DE IDIOMA (Estilo Clean) --}}
                <div class="relative"
                     x-data="{
                         open: false,
                         current: '{{ app()->getLocale() }}',
                         languages: {
                             'es': { name: 'Español', code: 'es' },
                             'en': { name: 'English', code: 'gb' },
                         },
                         select(lang) {
                             this.current = lang;
                             this.open = false;
                             $dispatch('change_lang', { locale: lang });
                         }
                     }"
                     @click.outside="open = false">

                    <!-- BOTÓN TRIGGER (Blanco con borde suave) -->
                    <button class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition-all hover:bg-gray-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            @click="open = !open"
                            type="button">
                        <img class="h-3 w-4 rounded-[1px] object-cover shadow-sm"
                             :src="`https://flagcdn.com/24x18/${languages[current].code}.png`"
                             alt="flag">
                        <span class="hidden md:inline"
                              x-text="languages[current].name"></span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200"
                           :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <!-- LISTA DESPLEGABLE (Blanca limpia) -->
                    <div class="absolute right-0 z-50 mt-2 w-40 origin-top-right rounded-xl border border-gray-100 bg-white py-1 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none"
                         x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         style="display: none;">

                        <template x-for="(lang, key) in languages"
                                  :key="key">
                            <button class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600"
                                    @click="select(key)"
                                    :class="current === key ? 'bg-indigo-50 text-indigo-700 font-semibold' : ''">
                                <img class="h-3 w-4 rounded-[1px] shadow-sm"
                                     :src="`https://flagcdn.com/24x18/${lang.code}.png`">
                                <span x-text="lang.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- PERFIL DE USUARIO (Estilo Clean) --}}
                <x-dropdown align="right"
                            width="48">
                    <x-slot name="trigger">
                        <button
                                class="flex items-center gap-2 rounded-full border border-gray-200 bg-white p-1 pr-3 text-sm font-medium text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                <span class="text-xs font-bold">{{ substr(Auth::user()->name, 0, 2) }}</span>
                            </div>
                            <span class="hidden md:block">{{ Str::limit(Auth::user()->name, 12) }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="block px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-400">
                            {{ __('labels.account') }}
                        </div>

                        <x-dropdown-link class="flex items-center gap-2"
                                         href="{{ route('profile.show') }}">
                            <i class="fa-regular fa-user text-gray-400"></i> {{ __('labels.profile') }}
                        </x-dropdown-link>

                        <div class="my-1 border-t border-gray-100"></div>

                        <form method="POST"
                              action="{{ route('logout') }}"
                              x-data>
                            @csrf
                            <x-dropdown-link class="flex items-center gap-2 text-rose-600 hover:cursor-pointer hover:bg-rose-50 hover:text-rose-700"
                                             @click.prevent="$root.submit();">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> {{ __('labels.session_close') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>

            </div>
        </div>
    </nav>
</div>
