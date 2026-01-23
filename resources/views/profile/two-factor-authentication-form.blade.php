<div class="rounded-xl border border-gray-200 bg-white shadow-sm">
    {{-- Cabecera --}}
    <div class="border-b border-gray-100 px-6 py-4">
        <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900">
            <i class="fa-solid fa-mobile-screen-button text-indigo-500"></i> Autenticación de Doble Factor
        </h3>
        <p class="text-sm text-gray-500">Añade seguridad adicional a tu cuenta usando una aplicación de autenticación.</p>
    </div>

    <div class="p-6">
        {{-- Estado Actual --}}
        <h3 class="text-base font-medium text-gray-900">
            @if ($this->enabled)
                @if ($showingConfirmation)
                    {{ __('Termina de habilitar la autenticación de dos factores.') }}
                @else
                    <span class="flex items-center gap-2 font-bold text-emerald-600">
                        <i class="fa-solid fa-circle-check"></i> Has habilitado la autenticación de dos factores.
                    </span>
                @endif
            @else
                <span class="text-gray-600">
                    {{ __('No has habilitado la autenticación de dos factores.') }}
                </span>
            @endif
        </h3>

        <div class="mt-3 max-w-xl text-sm text-gray-600">
            <p>
                {{ __('Cuando la autenticación de dos factores está habilitada, se te pedirá un token seguro y aleatorio durante la autenticación. Puedes recuperar este token de la aplicación Google Authenticator de tu teléfono.') }}
            </p>
        </div>

        {{-- Lógica de Códigos QR y Setup (Mantenemos la lógica de Jetstream pero mejoramos los botones) --}}
        @if ($this->enabled)
            @if ($showingQrCode)
                <div class="mt-4 inline-block rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        @if ($showingConfirmation)
                            {{ __('Escanea el QR para confirmar') }}
                        @else
                            {{ __('Código QR de configuración') }}
                        @endif
                    </p>
                    <div class="inline-block rounded bg-white p-2 shadow-sm">
                        {!! $this->user->twoFactorQrCodeSvg() !!}
                    </div>
                </div>
            @endif

            {{-- ... (Resto de la lógica de input de código y recovery codes se mantiene igual, solo cambia los botones al final) ... --}}
        @endif

        {{-- Botones de Acción --}}
        <div class="mt-6 flex items-center">
            @if (!$this->enabled)
                <x-confirms-password wire:then="enableTwoFactorAuthentication">
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-md hover:bg-indigo-700"
                            type="button">
                        {{ __('Habilitar') }}
                    </button>
                </x-confirms-password>
            @else
                @if ($showingRecoveryCodes)
                    <x-confirms-password wire:then="regenerateRecoveryCodes">
                        <button class="mr-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"
                                type="button">
                            {{ __('Regenerar Códigos') }}
                        </button>
                    </x-confirms-password>
                @elseif ($showingConfirmation)
                    <x-confirms-password wire:then="confirmTwoFactorAuthentication">
                        <button class="mr-3 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700"
                                type="button">
                            {{ __('Confirmar') }}
                        </button>
                    </x-confirms-password>
                @else
                    <x-confirms-password wire:then="showRecoveryCodes">
                        <button class="mr-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"
                                type="button">
                            {{ __('Ver Códigos de Recuperación') }}
                        </button>
                    </x-confirms-password>
                @endif

                @if ($showingConfirmation)
                    <x-confirms-password wire:then="disableTwoFactorAuthentication">
                        <button class="rounded-lg text-sm font-bold text-gray-500 hover:text-gray-700 hover:underline"
                                type="button">
                            {{ __('Cancelar') }}
                        </button>
                    </x-confirms-password>
                @else
                    <x-confirms-password wire:then="disableTwoFactorAuthentication">
                        <button class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-100"
                                type="button">
                            {{ __('Deshabilitar') }}
                        </button>
                    </x-confirms-password>
                @endif
            @endif
        </div>
    </div>
</div>
