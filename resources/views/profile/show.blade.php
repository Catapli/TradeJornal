<x-app-layout>
    <div class="mt-14 min-h-screen bg-gray-50 pb-20 pt-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Título de Sección --}}
            <div class="mb-8">
                <h2 class="text-2xl font-black text-gray-900">Configuración de Cuenta</h2>
                <p class="text-sm text-gray-500">Gestiona tus credenciales y la conexión con el Agente MT5.</p>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">

                {{-- COLUMNA IZQUIERDA: TARJETA DE PERFIL + TOKEN --}}
                <div class="space-y-6 lg:col-span-1">

                    {{-- 1. DATOS DEL USUARIO --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="bg-indigo-50/50 px-6 py-8 text-center">
                            <div class="relative mx-auto mb-4 inline-block h-20 w-20 rounded-full bg-indigo-100 p-1 ring-4 ring-white">
                                <img class="h-full w-full rounded-full object-cover"
                                     src="{{ Auth::user()->profile_photo_url }}"
                                     alt="{{ Auth::user()->name }}" />
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">{{ Auth::user()->name }}</h3>
                            <p class="text-sm text-gray-500">{{ Auth::user()->email }}</p>

                            @if (Auth::user()->is_superadmin)
                                <span class="mt-2 inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                    Super Admin
                                </span>
                            @endif
                        </div>

                        <div class="border-t border-gray-100 px-6 py-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Estado</span>
                                <span class="flex items-center gap-1 font-bold text-emerald-600">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Activo
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-gray-500">Miembro desde</span>
                                <span class="font-medium text-gray-900">{{ Auth::user()->created_at->format('d M, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- @if (Auth::user()->subscribed('default')) --}}
                    {{-- 2. SYNC TOKEN (Vital para tu Python) --}}
                    <div class="overflow-hidden rounded-xl border border-indigo-100 bg-white shadow-sm">
                        <div class="border-b border-indigo-50 px-6 py-4">
                            <h4 class="flex items-center gap-2 font-bold text-gray-900">
                                <i class="fa-solid fa-key text-indigo-500"></i> TradeForge Token
                            </h4>
                            <p class="mt-1 text-xs text-gray-500">Usa este token en el Agente Python.</p>
                        </div>
                        <div class="p-6">
                            <div class="relative"
                                 x-data="{ copied: false }">
                                <input class="block w-full rounded-lg border-gray-200 bg-gray-50 py-3 pl-4 pr-12 font-mono text-sm text-gray-600 focus:border-indigo-500 focus:ring-indigo-500"
                                       type="text"
                                       readonly
                                       value="{{ Auth::user()->sync_token ?? 'NO_TOKEN_GENERATED' }}" />

                                <button class="absolute right-2 top-2 rounded-md p-1.5 text-gray-400 hover:bg-white hover:text-indigo-600 hover:shadow-sm"
                                        @click="navigator.clipboard.writeText('{{ Auth::user()->sync_token }}'); copied = true; setTimeout(() => copied = false, 2000)">
                                    <i class="fa-regular fa-copy"
                                       x-show="!copied"></i>
                                    <i class="fa-solid fa-check text-emerald-500"
                                       x-show="copied"
                                       style="display: none;"></i>
                                </button>
                            </div>
                            <div class="mt-3 flex items-start gap-2 rounded bg-yellow-50 p-2 text-xs text-yellow-800">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                <p>No compartas este token. Da acceso total a subir operaciones a tu cuenta.</p>
                            </div>
                        </div>
                    </div>
                    {{-- @endif --}}


                </div>

                {{-- COLUMNA DERECHA: SEGURIDAD (Password y 2FA) --}}
                <div class="space-y-6 lg:col-span-2">

                    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                        @livewire('profile.update-password-form')
                    @endif

                    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                        @livewire('profile.two-factor-authentication-form')
                    @endif

                    {{-- Sesiones de navegador (Opcional, si lo usas) --}}
                    <div class="mt-10 sm:mt-0">
                        @livewire('profile.logout-other-browser-sessions-form')
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
