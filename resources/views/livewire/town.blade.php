<div class="max-w-fullxl mx-auto grid grid-cols-12 sm:px-6 lg:px-8"
     x-data="towns()"
     x-init="init()">

    {{-- ? Loading --}}
    <div wire:loading
         wire:target='insertTown, updateTown, deleteTown, findByID'>
        <x-loader></x-loader>
    </div>


    {{-- ? Loading Mediante JS --}}
    <div x-show="showLoading">
        <x-loader></x-loader>
    </div>

    {{-- ? Show Alerta --}}
    <x-modal-template show="showAlert">
    </x-modal-template>

    {{-- Filtros --}}
    <div class="col-span-12 my-2 bg-blue-50 p-5 shadow-xl sm:rounded-lg">
        <!-- Encabezado de pestañas -->
        <nav class="border-b border-gray-200">
            <ul class="flex items-end"
                aria-label="Tabs">
                <li>
                    <button class='rounded-t-md border border-b-0 border-solid border-gray-300 bg-white px-4 py-2 text-lg font-medium text-blue-600 transition-all duration-300'
                            type="button">
                        {{ __('labels.filters_mun') }}
                    </button>
                </li>
            </ul>
        </nav>
        <!-- Contenido de cada pestaña -->
        <div>
            <!-- Primera pestaña -->
            <div class="grid grid-cols-12 items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300">
                {{-- Input Municipio --}}
                <x-input-group id="filter_town"
                               class="col-span-5"
                               x-model="$wire.filter_town"
                               placeholder="{{ __('labels.name_mun') }}"
                               icono="<i class='fa-solid fa-building'></i>"
                               tooltip="{{ __('labels.name_mun') }}" />

                {{-- Input Activo --}}
                <x-select-group id="filter_active"
                                class="col-span-5"
                                x-model="$wire.filter_active"
                                icono="<i class='fa-solid fa-square-check'></i>"
                                tooltip="{{ __('labels.active') }}">
                    <x-slot name="options">
                        <option value="">{{ __('labels.all') }}</option>
                        <option value="true">{{ __('labels.active') }}</option>
                        <option value="false">{{ __('labels.not_active') }}</option>
                    </x-slot>
                </x-select-group>

                {{-- Boton Limpiar --}}
                <div class="col-span-2 flex justify-center gap-2 px-3">
                    <x-button-floating class="col-span-1"
                                       color="secondary"
                                       @click="resetFilters()"
                                       icon="fa-solid fa-eraser"
                                       tooltip="{{ __('labels.reset_filters') }}" />

                    <x-button-floating class="col-span-1"
                                       color="primary"
                                       @click="reloadTable()"
                                       icon="fa-solid fa-magnifying-glass"
                                       tooltip="{{ __('labels.search') }}" />
                </div>
            </div>
        </div>
    </div>

    {{-- Parte Inferior --}}
    <div class="col-span-12 my-2 grid grid-cols-12 gap-5 bg-blue-50 p-5 shadow-xl sm:rounded-lg">
        {{-- Tabla --}}
        <div class="col-span-6">
            <nav class="border-b border-gray-200">
                <ul class="flex items-end"
                    aria-label="Tabs">
                    <li>
                        <button class='rounded-t-md border border-b-0 border-solid border-gray-300 bg-white px-4 py-2 text-lg font-medium text-blue-600 transition-all duration-300'
                                type="button">
                            {{ __('labels.towns') }}
                        </button>
                    </li>
                </ul>
            </nav>
            <div>
                <div id="container_table"
                     class="items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300"
                     wire:ignore>
                    <div class="p-3">
                        <table id="table_towns"
                               class="datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('labels.id') }}</th>
                                    <th>{{ __('labels.poblation') }}</th>
                                    <th>{{ __('labels.active') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        {{-- Formulario --}}
        <div class="col-span-6">
            <nav class="border-b border-gray-200">
                <ul class="flex items-end"
                    aria-label="Tabs">
                    <li>
                        <button class='rounded-t-md border border-b-0 border-solid border-gray-300 bg-white px-4 py-2 text-lg font-medium text-blue-600 transition-all duration-300'
                                type="button">
                            {{ __('labels.data_mun') }}
                        </button>
                    </li>
                </ul>
            </nav>
            <div>
                <div id="container_data"
                     class="items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300"
                     x-bind:style="{ height: height }">
                    <div class="grid grid-cols-12 p-3">
                        {{-- Input ID --}}
                        <x-input-group id="input_id"
                                       class="col-span-6"
                                       x-model="$wire.id_town"
                                       disabled="true"
                                       placeholder="{{ __('labels.id') }}"
                                       icono="<i class='fa-solid fa-hashtag'></i>"
                                       tooltip="{{ __('labels.id') }}" />

                        <div class="col-span-3 flex items-center gap-5 px-4 py-2">
                            <div>
                                <span>{{ __('labels.active') }}</span>
                            </div>
                            <div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input class="peer sr-only"
                                           type="checkbox"
                                           x-model="$wire.active"
                                           value="" />
                                    <div
                                         class="group peer h-8 w-16 rounded-full bg-white ring-2 ring-red-500 duration-300 after:absolute after:left-1 after:top-1 after:flex after:h-6 after:w-6 after:items-center after:justify-center after:rounded-full after:bg-red-500 after:duration-300 peer-checked:ring-green-500 peer-checked:after:translate-x-8 peer-checked:after:bg-green-500 peer-hover:after:scale-95">
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="col-span-3 flex items-center gap-5 px-4 py-2">
                            <div>
                                <span>{{ __('labels.entity') }}</span>
                            </div>
                            <div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input class="peer sr-only"
                                           type="checkbox"
                                           x-model="$wire.entity"
                                           value="" />
                                    <div
                                         class="group peer h-8 w-16 rounded-full bg-white ring-2 ring-red-500 duration-300 after:absolute after:left-1 after:top-1 after:flex after:h-6 after:w-6 after:items-center after:justify-center after:rounded-full after:bg-red-500 after:duration-300 peer-checked:ring-green-500 peer-checked:after:translate-x-8 peer-checked:after:bg-green-500 peer-hover:after:scale-95">
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Municipio --}}
                        <x-input-group id="input_town"
                                       class="col-span-12"
                                       x-ref="input_town"
                                       lenght="100"
                                       x-model="$wire.town"
                                       placeholder="{{ __('labels.name_mun') }}"
                                       icono="<i class='fa-solid fa-building'></i>"
                                       tooltip="{{ __('labels.name_mun') }}" />

                        {{-- Email --}}
                        <x-input-group id="input_mail"
                                       class="col-span-12"
                                       x-ref="input_mail"
                                       lenght="150"
                                       x-model="$wire.email"
                                       placeholder="{{ __('labels.mail') }}"
                                       icono="<i class='fa-solid fa-at'></i>"
                                       tooltip="{{ __('labels.mail') }}" />

                        {{-- Telefono --}}
                        <x-input-group id="input_phone"
                                       class="col-span-6"
                                       x-ref="input_phone"
                                       lenght="20"
                                       x-model="$wire.phone"
                                       placeholder="{{ __('labels.phone') }}"
                                       icono="<i class='fa-solid fa-phone'></i>"
                                       tooltip="{{ __('labels.phone') }}" />

                        {{-- Codigo Postal --}}
                        <x-input-group id="input_postal_code"
                                       class="col-span-6"
                                       x-ref="input_postal_code"
                                       lenght="10"
                                       x-model="$wire.postal_code"
                                       placeholder="{{ __('labels.postal_code') }}"
                                       icono="<i class='fa-solid fa-envelopes-bulk'></i>"
                                       tooltip="{{ __('labels.postal_code') }}" />
                    </div>
                </div>
            </div>
        </div>


    </div>

    <div class="fixed bottom-10 right-5 z-20 flex w-auto rounded-2xl bg-backbuttons">
        {{-- Boton Nuevo --}}
        @if ($canWrite)
            <x-button-action type="new"
                             @click="insert()"
                             x-show="!registerSelected"
                             icon="fa-solid fa-file-circle-plus"
                             tooltip="{{ __('labels.new') }}" />
        @endif


        {{-- Boton limpiar --}}
        <x-button-action type="new"
                         @click="clean()"
                         x-show="registerSelected"
                         icon="fa-solid fa-eraser"
                         tooltip="{{ __('labels.clean') }}" />

        {{-- Boton Editar --}}
        @if ($canWrite)
            <x-button-action type="edit"
                             @click="update()"
                             x-show="registerSelected"
                             x-cloak
                             icon="fa-solid fa-file-pen"
                             tooltip="{{ __('labels.edit') }}" />
        @endif


        {{-- Boton Delete --}}
        @if ($canDelete)
            <x-button-action type="delete"
                             x-show="registerSelected"
                             @click="deleteTown()"
                             x-cloak
                             icon="fa-solid fa-trash"
                             tooltip="{{ __('labels.delete') }}" />
        @endif


    </div>

</div>
