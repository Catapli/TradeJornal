<x-form-section submit="updatePassword">
    <x-slot name="title">
        {{ __('labels.update_password') }}
    </x-slot>

    <x-slot name="description">
        {{ __('labels.up_psswd_desc') }}
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-label for="current_password"
                     value="{{ __('labels.current_psswd') }}" />
            <x-input id="current_password"
                     class="mt-1 block w-full"
                     type="password"
                     wire:model="state.current_password"
                     autocomplete="current-password" />
            <x-input-error class="mt-2"
                           for="current_password" />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="password"
                     value="{{ __('labels.new_psswd') }}" />
            <x-input id="password"
                     class="mt-1 block w-full"
                     type="password"
                     wire:model="state.password"
                     autocomplete="new-password" />
            <x-input-error class="mt-2"
                           for="password" />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="password_confirmation"
                     value="{{ __('labels.confirm_psswd') }}" />
            <x-input id="password_confirmation"
                     class="mt-1 block w-full"
                     type="password"
                     wire:model="state.password_confirmation"
                     autocomplete="new-password" />
            <x-input-error class="mt-2"
                           for="password_confirmation" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3"
                          on="saved">
            {{ __('labels.saved') }}
        </x-action-message>

        <x-button>
            {{ __('labels.save') }}
        </x-button>
    </x-slot>
</x-form-section>
