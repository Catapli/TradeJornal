<div class="mx-auto max-w-4xl py-10">
    <div class="mb-10 text-center">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Mejora tu Trading</h2>
        <p class="mt-2 text-gray-500">Desbloquea cuentas ilimitadas y estadísticas avanzadas.</p>
    </div>

    <div class="grid gap-8 md:grid-cols-2">
        <!-- Plan Mensual -->
        <div class="relative rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-emerald-500 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Mensual</h3>
            <div class="my-4">
                <span class="text-4xl font-bold text-gray-900 dark:text-white">9,99€</span>
                <span class="text-gray-500">/mes</span>
            </div>
            <ul class="mb-6 space-y-3 text-gray-600 dark:text-gray-300">
                <li class="flex items-center">✅ Cuentas Ilimitadas</li>
                <li class="flex items-center">✅ Métricas Avanzadas</li>
                <li class="flex items-center">✅ Soporte Prioritario</li>
            </ul>
            <button class="w-full rounded-lg bg-gray-900 px-4 py-2 text-white transition hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600"
                    wire:click="subscribe('monthly')"
                    wire:loading.attr="disabled">
                <span wire:loading.remove
                      wire:target="subscribe('monthly')">Elegir Mensual</span>
                <span wire:loading
                      wire:target="subscribe('monthly')">Redirigiendo...</span>
            </button>
        </div>

        <!-- Plan Anual -->
        <div class="relative scale-105 transform rounded-2xl border-2 border-emerald-500 bg-white p-6 shadow-xl dark:bg-gray-800">
            <div class="absolute right-0 top-0 rounded-bl-lg rounded-tr-lg bg-emerald-500 px-3 py-1 text-xs font-bold text-white">
                AHORRA 2 MESES
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Anual</h3>
            <div class="my-4">
                <span class="text-4xl font-bold text-gray-900 dark:text-white">100€</span>
                <span class="text-gray-500">/año</span>
            </div>
            <ul class="mb-6 space-y-3 text-gray-600 dark:text-gray-300">
                <li class="flex items-center">✅ Todo lo del mensual</li>
                <li class="flex items-center">✅ <strong>2 meses gratis</strong></li>
                <li class="flex items-center">✅ Acceso a Betas</li>
            </ul>
            <button class="w-full rounded-lg bg-emerald-600 px-4 py-2 font-bold text-white shadow-lg transition hover:bg-emerald-700"
                    wire:click="subscribe('yearly')"
                    wire:loading.attr="disabled">
                <span wire:loading.remove
                      wire:target="subscribe('yearly')">Elegir Anual</span>
                <span wire:loading
                      wire:target="subscribe('yearly')">Redirigiendo...</span>
            </button>
        </div>
    </div>
</div>
