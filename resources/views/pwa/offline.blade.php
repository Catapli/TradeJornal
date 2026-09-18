{{-- Pantalla sin conexión. La cachea el service worker al instalarse y la sirve
     cuando falla una navegación, en lugar del error del navegador.

     Se sirve con el layout público a propósito: no puede depender de nada que
     necesite red ni sesión. --}}
<x-public-layout :title="__('pwa.offline.title')">

    <div class="mx-auto flex min-h-[60vh] max-w-xl flex-col items-center justify-center px-4 py-20 text-center">

        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
            <i class="fa-solid fa-wifi text-xl"></i>
        </span>

        <h1 class="mt-6 text-2xl font-black text-gray-900 dark:text-gray-100">{{ __('pwa.offline.title') }}</h1>

        <p class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ __('pwa.offline.lead') }}</p>

        <button class="mt-8 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-indigo-700"
                type="button"
                onclick="window.location.reload()">
            <i class="fa-solid fa-rotate-right text-xs"></i>
            {{ __('pwa.offline.retry') }}
        </button>
    </div>

</x-public-layout>
