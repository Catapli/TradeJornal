<div>
    @if ($paginator->hasPages())
        @php(isset($scrollTo) ? $scrollTo : false)
        <nav class="flex items-center justify-between"
             role="navigation"
             aria-label="Pagination Navigation">

            {{-- Vista Móvil (Flechas simples) --}}
            <div class="flex flex-1 justify-between sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="relative inline-flex cursor-default items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-400">
                        {!! __('pagination.previous') !!}
                    </span>
                @else
                    <button class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-700 ring-gray-300 transition duration-150 ease-in-out hover:text-gray-500 focus:outline-none focus:ring active:bg-gray-100"
                            wire:click="previousPage"
                            wire:loading.attr="disabled"
                            @if ($scrollTo) x-on:click="$el.closest('body') && $el.closest('body').scrollIntoView({behavior:'smooth'})" @endif
                            type="button">
                        {!! __('pagination.previous') !!}
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-700 ring-gray-300 transition duration-150 ease-in-out hover:text-gray-500 focus:outline-none focus:ring active:bg-gray-100"
                            wire:click="nextPage"
                            wire:loading.attr="disabled"
                            @if ($scrollTo) x-on:click="$el.closest('body') && $el.closest('body').scrollIntoView({behavior:'smooth'})" @endif
                            type="button">
                        {!! __('pagination.next') !!}
                    </button>
                @else
                    <span class="relative ml-3 inline-flex cursor-default items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-400">
                        {!! __('pagination.next') !!}
                    </span>
                @endif
            </div>

            {{-- Vista Escritorio --}}
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">

                {{-- Info de resultados (Mostrando 1 a 10 de 50) --}}
                <div>
                    <p class="text-sm leading-5 text-gray-500">
                        Mostrando
                        <span class="font-bold text-gray-800">{{ $paginator->firstItem() }}</span>
                        a
                        <span class="font-bold text-gray-800">{{ $paginator->lastItem() }}</span>
                        de
                        <span class="font-bold text-indigo-600">{{ $paginator->total() }}</span>
                        resultados
                    </p>
                </div>

                {{-- Botonera --}}
                <div>
                    <span class="relative z-0 inline-flex gap-1 rounded-md shadow-sm"> {{-- Gap-1 separa los botones --}}

                        {{-- Botón Anterior --}}
                        @if ($paginator->onFirstPage())
                            <span aria-disabled="true"
                                  aria-label="{{ __('pagination.previous') }}">
                                <span class="relative inline-flex cursor-default items-center rounded-l-md border border-gray-200 bg-white px-2 py-2 text-sm font-medium leading-5 text-gray-300"
                                      aria-hidden="true">
                                    <svg class="h-5 w-5"
                                         fill="currentColor"
                                         viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                              clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </span>
                        @else
                            <button class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium leading-5 text-gray-500 transition hover:bg-gray-50 hover:text-indigo-600"
                                    wire:click="previousPage"
                                    wire:loading.attr="disabled"
                                    rel="prev"
                                    aria-label="{{ __('pagination.previous') }}">
                                <svg class="h-5 w-5"
                                     fill="currentColor"
                                     viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                          clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif

                        {{-- Números de Página --}}
                        @foreach ($elements as $element)
                            {{-- "..." Separador --}}
                            @if (is_string($element))
                                <span aria-disabled="true">
                                    <span class="relative inline-flex cursor-default items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-700">{{ $element }}</span>
                                </span>
                            @endif

                            {{-- Array de Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        {{-- 🔴 AQUÍ EL CAMBIO: ESTADO ACTIVO DESTACADO --}}
                                        <span aria-current="page">
                                            <span class="relative inline-flex scale-105 transform cursor-default items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-bold leading-5 text-white shadow-md">
                                                {{ $page }}
                                            </span>
                                        </span>
                                    @else
                                        {{-- Estado Inactivo --}}
                                        <button class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600"
                                                wire:click="gotoPage({{ $page }})"
                                                wire:loading.attr="disabled"
                                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                            {{ $page }}
                                        </button>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Botón Siguiente --}}
                        @if ($paginator->hasMorePages())
                            <button class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium leading-5 text-gray-500 transition hover:bg-gray-50 hover:text-indigo-600"
                                    wire:click="nextPage"
                                    wire:loading.attr="disabled"
                                    rel="next"
                                    aria-label="{{ __('pagination.next') }}">
                                <svg class="h-5 w-5"
                                     fill="currentColor"
                                     viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                          clip-rule="evenodd" />
                                </svg>
                            </button>
                        @else
                            <span aria-disabled="true"
                                  aria-label="{{ __('pagination.next') }}">
                                <span class="relative inline-flex cursor-default items-center rounded-r-md border border-gray-200 bg-white px-2 py-2 text-sm font-medium leading-5 text-gray-300"
                                      aria-hidden="true">
                                    <svg class="h-5 w-5"
                                         fill="currentColor"
                                         viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                              clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </span>
                        @endif
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
