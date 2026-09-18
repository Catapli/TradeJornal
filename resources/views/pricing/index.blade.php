{{--
    /pricing es pública desde la Fase 1 del ROADMAP: antes vivía dentro del grupo
    `auth`, así que nadie podía saber cuánto costaba el producto sin registrarse.

    Con sesión iniciada se sirve dentro del layout de la aplicación y con el
    componente Livewire que abre el checkout de Stripe. Sin sesión, la misma
    tabla en el layout público, con los CTA apuntando a registro.
--}}

@auth
    <x-app-layout>
        <div class="mt-[55px] py-2">
            @livewire('settings.subscription')
        </div>
    </x-app-layout>
@else
    <x-public-layout :title="__('landing.pricing.eyebrow')"
                     :description="__('landing.pricing.title')">

        <section class="py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <div class="mx-auto mb-12 max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">
                        {{ __('landing.pricing.eyebrow') }}
                    </p>
                    <h1 class="mt-3 text-balance text-4xl font-black tracking-tight text-gray-900 dark:text-white sm:text-5xl">
                        {{ __('landing.pricing.title') }}
                    </h1>
                    <p class="mt-4 text-pretty text-lg text-gray-600 dark:text-gray-400">
                        {{ __('landing.pricing.lead') }}
                    </p>
                </div>

                <x-pricing-table mode="public" />

                {{-- FAQ: las mismas dudas que en la landing, porque quien llega
                     directo a /pricing no ha pasado por ellas. --}}
                <div class="mx-auto mt-20 max-w-3xl">
                    <h2 class="text-center text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                        {{ __('landing.faq.title') }}
                    </h2>

                    <div class="mt-8 divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900"
                         x-data="{ open: null }">
                        @foreach (__('landing.faq.items') as $i => $item)
                            <div>
                                <button class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500 dark:hover:bg-gray-800/50"
                                        type="button"
                                        @click="open = open === {{ $i }} ? null : {{ $i }}"
                                        :aria-expanded="(open === {{ $i }}).toString()"
                                        aria-controls="pricing-faq-{{ $i }}">
                                    <span class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $item['q'] }}</span>
                                    <i class="fa-solid fa-chevron-down shrink-0 text-xs text-gray-400 transition-transform duration-200"
                                       :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                                </button>
                                <div class="px-6 pb-5 text-sm leading-relaxed text-gray-600 dark:text-gray-400"
                                     id="pricing-faq-{{ $i }}"
                                     x-show="open === {{ $i }}"
                                     x-collapse
                                     style="display: none;">
                                    {{ $item['a'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </x-public-layout>
@endauth
