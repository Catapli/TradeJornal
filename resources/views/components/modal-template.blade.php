@props(['show'])

<div class="fixed right-6 top-24 z-[60] w-full max-w-sm transform transition-all duration-300"
     x-show="{{ $show }}"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-12 scale-95"
     x-transition:enter-end="opacity-100 translate-x-0 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     @click.away="{{ $show }} = false"
     style="min-width: 350px;">

    <div class="relative overflow-hidden rounded-3xl border-4 p-6 shadow-2xl"
         :class="{
             'border-emerald-400/50 bg-gradient-to-br from-emerald-50 to-emerald-100': typeAlert === 'success',
             'border-red-400/50 bg-gradient-to-br from-red-50 to-red-100': typeAlert === 'error',
             'border-amber-400/50 bg-gradient-to-br from-amber-50 to-amber-100': typeAlert === 'warn'
         }">

        <div class="flex items-start gap-4">
            <!-- Icono Dinámico -->
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm"
                 :class="{
                     'bg-emerald-500/20 text-emerald-600': typeAlert === 'success',
                     'bg-red-500/20 text-red-600 animate-pulse': typeAlert === 'error',
                     'bg-amber-500/20 text-amber-600': typeAlert === 'warn'
                 }">

                <!-- Success Icon -->
                <template x-if="typeAlert === 'success'">
                    <svg class="h-7 w-7"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="2.5"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>

                <!-- Error Icon -->
                <template x-if="typeAlert === 'error'">
                    <svg class="h-7 w-7"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="2.5"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </template>

                <!-- Warning Icon -->
                <template x-if="typeAlert === 'warn'">
                    <svg class="h-7 w-7"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="2.5"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75A11.959 11.959 0 0112 2.714z" />
                    </svg>
                </template>
            </div>

            <!-- Texto -->
            <div class="flex-1">
                <p class="mt-1 text-sm leading-relaxed text-slate-600"
                   x-text="bodyAlert"></p>

                <!-- Botón de acción (Opcional, si typeButton no está vacío) -->
                <template x-if="typeButton !== ''">
                    <button class="mt-3 text-sm font-bold uppercase tracking-wider transition-colors hover:underline"
                            @click="{{ $show }} = false; $wire.call(typeButton)"
                            :class="{
                                'text-emerald-700': typeAlert === 'success',
                                'text-red-700': typeAlert === 'error',
                                'text-amber-700': typeAlert === 'warn'
                            }">
                        Confirmar
                    </button>
                </template>
            </div>

            <!-- Botón Cerrar -->
            <button class="text-slate-400 transition-colors hover:text-slate-600"
                    @click="{{ $show }} = false">
                <svg class="h-5 w-5"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="2"
                     stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Barra de progreso decorativa inferior -->
        <div class="absolute bottom-0 left-0 h-1.5 w-full bg-black/5">
            <div class="h-full transition-all duration-[5000ms] ease-linear"
                 :class="{
                     'bg-emerald-500': typeAlert === 'success',
                     'bg-red-500': typeAlert === 'error',
                     'bg-amber-500': typeAlert === 'warn'
                 }"
                 :style="{{ $show }} ? 'width: 100%' : 'width: 0%'">
            </div>
        </div>
    </div>
</div>
