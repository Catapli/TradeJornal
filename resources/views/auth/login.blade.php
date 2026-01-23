<x-guest-layout>
    <div class="flex min-h-screen w-full flex-wrap">

        <!-- COLUMNA IZQUIERDA: FORMULARIO -->
        <div class="flex w-full flex-col justify-center bg-white px-6 py-12 lg:w-1/2 lg:px-20 xl:w-5/12">

            <!-- Logo Móvil / Pequeño -->
            <div class="mb-8 flex justify-center lg:justify-start">
                <a href="/">
                    <img class="h-auto max-h-20 w-auto object-contain"
                         src="{{ asset('img/logo_trader_h.png') }}"
                         alt="TradeForge">
                </a>
            </div>

            <div class="w-full">
                <h2 class="mt-4 text-3xl font-black text-gray-900">Bienvenido de nuevo</h2>
                <p class="mt-2 text-sm text-gray-500">Introduce tus credenciales para acceder al dashboard.</p>
            </div>

            <div class="mt-8">
                <!-- Errores -->
                <x-validation-errors class="mb-4" />

                @session('status')
                    <div class="mb-4 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-600">
                        {{ $value }}
                    </div>
                @endsession

                <form class="space-y-6"
                      method="POST"
                      action="{{ route('login') }}"
                      x-data="{ showPassword: false }">
                    @csrf

                    <!-- Email -->
                    <div>
                        <x-label for="email"
                                 value="{{ __('Correo Electrónico') }}" />
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fa-regular fa-envelope text-gray-400"></i>
                            </div>
                            <x-input id="email"
                                     name="email"
                                     class="block w-full py-3 pl-10"
                                     type="email"
                                     :value="old('email')"
                                     required
                                     autofocus
                                     autocomplete="username"
                                     placeholder="ejemplo@tradeforge.com" />
                        </div>
                    </div>

                    <!-- Contraseña con Ojo -->
                    <div>
                        <div class="flex justify-between">
                            <x-label for="password"
                                     value="{{ __('Contraseña') }}" />
                            @if (Route::has('password.request'))
                                <a class="text-xs font-medium text-indigo-600 hover:text-indigo-500"
                                   href="{{ route('password.request') }}">
                                    {{ __('¿Olvidaste tu contraseña?') }}
                                </a>
                            @endif
                        </div>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fa-solid fa-lock text-gray-400"></i>
                            </div>
                            <!-- Input dinámico type text/password -->
                            <input id="password"
                                   name="password"
                                   class="block w-full rounded-md border-gray-300 py-3 pl-10 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   :type="showPassword ? 'text' : 'password'"
                                   required
                                   autocomplete="current-password"
                                   placeholder="••••••••" />

                            <!-- Botón Ojo -->
                            <button class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    type="button"
                                    @click="showPassword = !showPassword">
                                <i class="fa-regular"
                                   :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center"
                               for="remember_me">
                            <x-checkbox id="remember_me"
                                        name="remember" />
                            <span class="ml-2 text-sm text-gray-600">{{ __('Recuérdame') }}</span>
                        </label>
                    </div>

                    <!-- Botón Login -->
                    <div>
                        <button class="flex w-full justify-center rounded-md border border-transparent bg-gray-900 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                type="submit">
                            {{ __('Iniciar Sesión') }}
                        </button>
                    </div>
                </form>

                <!-- Separador -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-white px-2 text-gray-500">O continúa con</span>
                    </div>
                </div>

                <!-- Botón Google -->
                <a class="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                   href="{{ route('auth.google') }}">
                    <img class="h-5 w-5"
                         src="https://www.svgrepo.com/show/475656/google-color.svg"
                         alt="Google">
                    <span>Iniciar con Google</span>
                </a>

                <!-- Footer Registro -->
                <p class="mt-8 text-center text-sm text-gray-600">
                    ¿No tienes cuenta?
                    <a class="font-bold text-indigo-600 hover:text-indigo-500"
                       href="{{ route('register') }}">
                        Regístrate gratis
                    </a>
                </p>
            </div>
        </div>

        <!-- COLUMNA DERECHA: IMAGEN / MARCA -->
        <div class="relative hidden w-0 flex-1 bg-gray-900 lg:block">
            <!-- Imagen de Fondo (Pon una captura de tu dashboard o un gráfico bonito) -->
            <img class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-overlay"
                 src="https://images.unsplash.com/photo-1611974765270-ca1258634369?q=80&w=2664&auto=format&fit=crop"
                 alt="Trading Background">

            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40"></div>

            <div class="absolute bottom-0 left-0 p-20 text-white">
                <img class="h-52 w-auto brightness-0 invert"
                     src="{{ asset('img/logo_o.png') }}"
                     alt="">
                <h2 class="text-4xl font-bold leading-tight">Domina tu psicología,<br>maximiza tu rendimiento.</h2>
                <p class="mt-4 text-lg text-gray-300">La herramienta definitiva para traders que buscan consistencia matemática.</p>

                <!-- Pequeño testimonial o stats -->
                {{-- <div class="mt-8 flex items-center gap-4">
                    <div class="flex -space-x-2">
                        <img class="inline-block h-8 w-8 rounded-full ring-2 ring-gray-900"
                             src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&h=64&w=64"
                             alt="" />
                        <img class="inline-block h-8 w-8 rounded-full ring-2 ring-gray-900"
                             src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&h=64&w=64"
                             alt="" />
                        <img class="inline-block h-8 w-8 rounded-full ring-2 ring-gray-900"
                             src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&h=64&w=64"
                             alt="" />
                    </div>
                    <div class="text-sm font-medium text-gray-300">Usado por +1,000 traders</div>
                </div> --}}
            </div>
        </div>
    </div>
</x-guest-layout>
