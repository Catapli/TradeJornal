<x-action-section>
    <x-slot name="title">
        {{ __('labels.two_factor_auth') }}
    </x-slot>

    <x-slot name="description">
        {{ __('labels.description_2fa') }}
    </x-slot>

    <x-slot name="content">
        <h3 class="text-lg font-medium text-gray-900">
            @if ($this->enabled)
                @if ($showingConfirmation)
                    {{ __('labels.finish_2fa') }}
                @else
                    {{ __('labels.enabled_2fa') }}
                @endif
            @else
                {{ __('labels.not_enabled_2fa') }}
            @endif
        </h3>

        <div class="mt-3 max-w-xl text-sm text-gray-600">
            <p>
                {{ __('labels.desc_short_2fa') }}
            </p>
        </div>

        @if ($this->enabled)
            @if ($showingQrCode)
                <div class="mt-4 max-w-xl text-sm text-gray-600">
                    <p class="font-semibold">
                        @if ($showingConfirmation)
                            {{ __('labels.finish_desc_2fa') }}
                        @else
                            {{ __('labels.finish_desc2_2fa') }}
                        @endif
                    </p>
                </div>

                <div class="mt-4 inline-block bg-white p-2">
                    {!! $this->user->twoFactorQrCodeSvg() !!}
                </div>

                <div class="mt-4 max-w-xl text-sm text-gray-600">
                    <p class="font-semibold">
                        {{ __('labels.setup_key') }}: {{ decrypt($this->user->two_factor_secret) }}
                    </p>
                </div>

                @if ($showingConfirmation)
                    <div class="mt-4">
                        <x-label for="code"
                                 value="{{ __('labels.code') }}" />

                        <x-input id="code"
                                 name="code"
                                 class="mt-1 block w-1/2"
                                 type="text"
                                 inputmode="numeric"
                                 autofocus
                                 autocomplete="one-time-code"
                                 wire:model="code"
                                 wire:keydown.enter="confirmTwoFactorAuthentication" />

                        <x-input-error class="mt-2"
                                       for="code" />
                    </div>
                @endif
            @endif

            @if ($showingRecoveryCodes)
                <div class="mt-4 max-w-xl text-sm text-gray-600">
                    <p class="font-semibold">
                        {{ __('labels.recovery_codes_desc') }}
                    </p>
                </div>

                <div class="mt-4 grid max-w-xl gap-1 rounded-lg bg-gray-100 px-4 py-4 font-mono text-sm">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="mt-5">
            @if (!$this->enabled)
                <x-confirms-password wire:then="enableTwoFactorAuthentication">
                    <x-button type="button"
                              wire:loading.attr="disabled">
                        {{ __('labels.enable') }}
                    </x-button>
                </x-confirms-password>
            @else
                @if ($showingRecoveryCodes)
                    <x-confirms-password wire:then="regenerateRecoveryCodes">
                        <x-secondary-button class="me-3">
                            {{ __('labels.regenerate_codes') }}
                        </x-secondary-button>
                    </x-confirms-password>
                @elseif ($showingConfirmation)
                    <x-confirms-password wire:then="confirmTwoFactorAuthentication">
                        <x-button class="me-3"
                                  type="button"
                                  wire:loading.attr="disabled">
                            {{ __('labels.confirm') }}
                        </x-button>
                    </x-confirms-password>
                @else
                    <x-confirms-password wire:then="showRecoveryCodes">
                        <x-secondary-button class="me-3">
                            {{ __('labels.show_recovery_codes') }}
                        </x-secondary-button>
                    </x-confirms-password>
                @endif

                @if ($showingConfirmation)
                    <x-confirms-password wire:then="disableTwoFactorAuthentication">
                        <x-secondary-button wire:loading.attr="disabled">
                            {{ __('labels.cancel') }}
                        </x-secondary-button>
                    </x-confirms-password>
                @else
                    <x-confirms-password wire:then="disableTwoFactorAuthentication">
                        <x-danger-button wire:loading.attr="disabled">
                            {{ __('labels.disable') }}
                        </x-danger-button>
                    </x-confirms-password>
                @endif

            @endif
        </div>
    </x-slot>
</x-action-section>
