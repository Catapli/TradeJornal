<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 text-sm font-medium text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form method="POST"
              action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="email"
                         value="{{ __('Correo Electrónico') }}" />
                <x-input id="email"
                         name="email"
                         class="mt-1 block w-full"
                         type="email"
                         :value="old('email')"
                         required
                         autofocus
                         autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password"
                         value="{{ __('Contraseña') }}" />
                <x-input id="password"
                         name="password"
                         class="mt-1 block w-full"
                         type="password"
                         required
                         autocomplete="current-password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center"
                       for="remember_me">
                    <x-checkbox id="remember_me"
                                name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ __('Recuerdame') }}</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">

                <x-button class="bg-red-400">
                    <a href="{{ route('auth.google') }}"> {{ __('auth.loginGoogle') }}</a>
                </x-button>

                @if (Route::has('password.request'))
                    <a class="rounded-md text-center text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                       href="{{ route('password.request') }}">
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </a>
                @endif
                <x-button class="ms-4">
                    {{ __('Entrar') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
