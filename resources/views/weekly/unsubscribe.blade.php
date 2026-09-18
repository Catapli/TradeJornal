{{-- Confirmación de baja del resumen semanal. Se llega desde el enlace firmado
     del correo, sin sesión. --}}
<x-public-layout :title="__('weekly.unsubscribe.title')">

    <div class="mx-auto flex min-h-[60vh] max-w-xl flex-col items-center justify-center px-4 py-20 text-center">

        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
            <i class="fa-solid fa-check text-xl"></i>
        </span>

        <h1 class="mt-6 text-2xl font-black text-gray-900 dark:text-gray-100">
            {{ __('weekly.unsubscribe.title') }}
        </h1>

        <p class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
            {{ __('weekly.unsubscribe.lead', ['email' => $user->email]) }}
        </p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-indigo-700"
               href="{{ route('dashboard') }}">
                {{ __('weekly.unsubscribe.back') }}
            </a>

            <a class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 px-6 py-3 text-sm font-bold text-gray-800 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-800"
               href="{{ route('profile.show') }}">
                {{ __('weekly.unsubscribe.resubscribe') }}
            </a>
        </div>
    </div>

</x-public-layout>
