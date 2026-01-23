<x-guest-layout>
    <div class="flex min-h-screen w-full flex-wrap">

        <!-- COLUMNA IZQUIERDA: FORMULARIO -->
        <div class="flex w-full flex-col justify-center bg-white px-6 py-12 lg:w-1/2 lg:px-20 xl:w-5/12">

            <div class="mb-8 flex justify-center lg:justify-start">
                <a href="/">
                    <img class="h-auto max-h-16 w-auto object-contain"
                         src="{{ asset('img/logo_trader_h.png') }}"
                         alt="TradeForge">
                </a>
            </div>

            <div class="w-full">
                <h2 class="mt-4 text-3xl font-black text-gray-900">Crea tu cuenta</h2>
                <p class="mt-2 text-sm text-gray-500">Empieza a registrar tus operaciones hoy mismo.</p>
            </div>

            <div class="mt-8">
                <x-validation-errors class="mb-4" />

                <form class="space-y-5"
                      method="POST"
                      action="{{ route('register') }}"
                      x-data="{ showPassword: false }">
                    @csrf

                    <!-- Nombre -->
                    <div>
                        <x-label for="name"
                                 value="{{ __('Nombre Completo') }}" />
                        <x-input id="name"
                                 name="name"
                                 class="mt-1 block w-full py-3"
                                 type="text"
                                 :value="old('name')"
                                 required
                                 autofocus
                                 autocomplete="name"
                                 placeholder="John Doe" />
                    </div>

                    <!-- Email -->
                    <div>
                        <x-label for="email"
                                 value="{{ __('Correo Electrónico') }}" />
                        <x-input id="email"
                                 name="email"
                                 class="mt-1 block w-full py-3"
                                 type="email"
                                 :value="old('email')"
                                 required
                                 autocomplete="username"
                                 placeholder="ejemplo@tradeforge.com" />
                    </div>

                    <!-- Contraseña -->
                    <div>
                        <x-label for="password"
                                 value="{{ __('Contraseña') }}" />
                        <div class="relative mt-1">
                            <input id="password"
                                   name="password"
                                   class="block w-full rounded-md border-gray-300 py-3 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   :type="showPassword ? 'text' : 'password'"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Mínimo 8 caracteres" />
                            <button class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    type="button"
                                    @click="showPassword = !showPassword">
                                <i class="fa-regular"
                                   :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div>
                        <x-label for="password_confirmation"
                                 value="{{ __('Confirmar Contraseña') }}" />
                        <div class="relative mt-1">
                            <input id="password_confirmation"
                                   name="password_confirmation"
                                   class="block w-full rounded-md border-gray-300 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   :type="showPassword ? 'text' : 'password'"
                                   required
                                   autocomplete="new-password" />
                        </div>
                    </div>

                    <!-- Términos y Condiciones (Si usas Jetstream) -->
                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                        <div class="mt-4">
                            <x-label for="terms">
                                <div class="flex items-center">
                                    <x-checkbox id="terms"
                                                name="terms"
                                                required />
                                    <div class="ml-2 text-sm text-gray-600">
                                        {!! __('Acepto los :terms_of_service y la :privacy_policy', [
                                            'terms_of_service' => '<a target="_blank" href="' . route('terms.show') . '" class="underline text-sm text-indigo-600 hover:text-indigo-900">' . __('Términos') . '</a>',
                                            'privacy_policy' => '<a target="_blank" href="' . route('policy.show') . '" class="underline text-sm text-indigo-600 hover:text-indigo-900">' . __('Política de Privacidad') . '</a>',
                                        ]) !!}
                                    </div>
                                </div>
                            </x-label>
                        </div>
                    @endif

                    <!-- Botón Registro -->
                    <div class="pt-2">
                        <button class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                type="submit">
                            {{ __('Crear Cuenta') }}
                        </button>
                    </div>
                </form>

                <!-- Separador -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-white px-2 text-gray-500">O regístrate con</span>
                    </div>
                </div>

                <!-- Botón Google -->
                <a class="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                   href="{{ route('auth.google') }}">
                    <img class="h-5 w-5"
                         src="https://www.svgrepo.com/show/475656/google-color.svg"
                         alt="Google">
                    <span>Google</span>
                </a>

                <p class="mt-8 text-center text-sm text-gray-600">
                    ¿Ya tienes cuenta?
                    <a class="font-bold text-indigo-600 hover:text-indigo-500"
                       href="{{ route('login') }}">
                        Inicia Sesión
                    </a>
                </p>
            </div>
        </div>

        <!-- COLUMNA DERECHA: IMAGEN / MARCA (Reutilizamos o cambiamos imagen) -->
        <div class="relative hidden w-0 flex-1 bg-gray-900 lg:block">
            <img class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-overlay"
                 src="https://images.unsplash.com/photo-1642543492481-44e81e3914a7?q=80&w=2600&auto=format&fit=crop"
                 alt="Trading Analysis">
            <div class="absolute inset-0 bg-gradient-to-t from-indigo-900/80 via-gray-900/40"></div>

            <div class="absolute bottom-0 left-0 p-20 text-white">
                <h2 class="text-4xl font-bold leading-tight">Tu viaje hacia la<br>rentabilidad empieza aquí.</h2>
                <ul class="mt-6 space-y-4 text-gray-300">
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-check-circle text-emerald-400"></i> Sincronización automática MT4/MT5
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-check-circle text-emerald-400"></i> Análisis con Inteligencia Artificial
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-check-circle text-emerald-400"></i> Diario de Trading profesional
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-guest-layout>
