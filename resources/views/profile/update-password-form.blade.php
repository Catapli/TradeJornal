<div class="rounded-xl border border-gray-200 bg-white shadow-sm">
    {{-- Cabecera --}}
    <div class="border-b border-gray-100 px-6 py-4">
        <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900">
            <i class="fa-solid fa-shield-halved text-indigo-500"></i> Actualizar Contraseña
        </h3>
        <p class="text-sm text-gray-500">Asegúrate de usar una contraseña larga y aleatoria para mantener tu cuenta segura.</p>
    </div>

    {{-- Formulario --}}
    <form class="p-6"
          wire:submit.prevent="updatePassword">
        <div class="grid grid-cols-1 gap-6">

            {{-- Contraseña Actual --}}
            <div class="max-w-xl">
                <x-label class="mb-1 font-bold text-gray-700"
                         for="current_password"
                         value="{{ __('Contraseña Actual') }}" />
                <x-input id="current_password"
                         class="mt-1 block w-full"
                         type="password"
                         wire:model="state.current_password"
                         autocomplete="current-password" />
                <x-input-error class="mt-2"
                               for="current_password" />
            </div>

            {{-- Nueva Contraseña --}}
            <div class="max-w-xl">
                <x-label class="mb-1 font-bold text-gray-700"
                         for="password"
                         value="{{ __('Nueva Contraseña') }}" />
                <x-input id="password"
                         class="mt-1 block w-full"
                         type="password"
                         wire:model="state.password"
                         autocomplete="new-password" />
                <x-input-error class="mt-2"
                               for="password" />
            </div>

            {{-- Confirmar --}}
            <div class="max-w-xl">
                <x-label class="mb-1 font-bold text-gray-700"
                         for="password_confirmation"
                         value="{{ __('Confirmar Contraseña') }}" />
                <x-input id="password_confirmation"
                         class="mt-1 block w-full"
                         type="password"
                         wire:model="state.password_confirmation"
                         autocomplete="new-password" />
                <x-input-error class="mt-2"
                               for="password_confirmation" />
            </div>
        </div>

        {{-- Footer con Acción --}}
        <div class="mt-6 flex items-center gap-4">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-md transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    type="submit">
                {{ __('Guardar') }}
            </button>

            <x-action-message class="me-3"
                              on="saved">
                <span class="flex items-center gap-1 text-sm font-bold text-emerald-600">
                    <i class="fa-solid fa-check-circle"></i> Guardado correctamente.
                </span>
            </x-action-message>
        </div>
    </form>
</div>
