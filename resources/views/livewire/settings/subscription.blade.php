{{--
    Bloque de suscripción dentro de la aplicación.

    La matriz de planes vive en <x-pricing-table>, compartida con la landing y con
    /pricing, para que no vuelvan a divergir el texto comercial y lo que hace el código.
--}}
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

    <div class="mx-auto mb-10 max-w-2xl text-center">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">
            {{ __('landing.pricing.eyebrow') }}
        </p>
        <h1 class="mt-3 text-balance text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">
            {{ __('landing.pricing.title') }}
        </h1>
        <p class="mt-4 text-pretty text-gray-600 dark:text-gray-400">
            {{ __('landing.pricing.lead') }}
        </p>
    </div>

    <x-pricing-table mode="app"
                     :subscribed="auth()->user()?->subscribed('default') ?? false" />
</div>
