<div class="max-w-fullxl mx-auto grid grid-cols-12 sm:px-6 lg:px-8"
     x-data="logs()"
     x-init="init()">

    <div wire:loading
         wire:target='insertTown, updateTown, findByID'>
        <x-loader></x-loader>
    </div>

    {{-- ? Loading Mediante JS --}}
    <div x-show="showLoading">
        <x-loader></x-loader>
    </div>

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
                        {{ __('labels.filters_logs') }}
                    </button>
                </li>
            </ul>
        </nav>
        <!-- Contenido de cada pestaña -->
        <div>
            <!-- Primera pestaña -->
            <div class="grid grid-cols-9 items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300">
                {{-- Input Municipio --}}
                <div class="col-span-2">

                    <livewire:datepicker variable="filters.date"
                                         icono='<i class="fa-regular fa-calendar-days"></i>'
                                         tooltip="Seleccione una fecha"
                                         placeholder="Seleccione una fecha" />
                </div>
                {{-- ? Input Municipio --}}
                <x-select-group id="filterTown"
                                class="col-span-2"
                                x-model="$wire.filters.town"
                                icono="<i class='fa-solid fa-city'></i>"
                                tooltip="{{ __('labels.entity') }}">
                    <x-slot name="options">
                        <option value=""
                                selected>{{ __('labels.select_entity') }}</option>
                        @foreach ($towns as $town)
                            <option value="{{ $town->id }}">{{ $town->town }}</option>
                        @endforeach
                    </x-slot>
                </x-select-group>
                {{-- <x-input-group class="col-span-2"
                               x-model="$wire.filters.town"
                               placeholder="{{ __('labels.name_mun') }}"
                               icono="<i class='fa-solid fa-city'></i>"
                               tooltip="{{ __('labels.name_mun') }}" /> --}}


                <x-select-group id="filterTown"
                                class="col-span-2"
                                x-model="$wire.filters.user"
                                icono="<i class='fa-solid fa-user'></i>"
                                tooltip="{{ __('labels.user') }}">
                    <x-slot name="options">
                        <option value=""
                                selected>{{ __('labels.select_user') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </x-slot>
                </x-select-group>
                {{-- <x-input-group class="col-span-2"
                               x-model="$wire.filters.user"
                               placeholder="{{ __('labels.user') }}"
                               icono="<i class='fa-solid fa-user'></i>"
                               tooltip="{{ __('labels.user') }}" /> --}}
                <x-input-group class="col-span-2"
                               x-model="$wire.filters.action"
                               placeholder="{{ __('labels.action') }}"
                               icono="<i class='fa-solid fa-circle-exclamation'></i>"
                               tooltip="{{ __('labels.action') }}" />
                {{-- Boton Limpiar --}}
                <div class="col-span-1 flex">
                    <x-button-floating class="col-span-1 p-2"
                                       color="secondary"
                                       @click="resetFilters()"
                                       icon="fa-solid fa-eraser"
                                       tooltip="{{ __('labels.reset_filters') }}" />

                    <x-button-floating class="col-span-1 p-2"
                                       color="primary"
                                       @click="reloadTable()"
                                       icon="fa-solid fa-magnifying-glass"
                                       tooltip="{{ __('labels.search') }}" />
                </div>
            </div>
        </div>
    </div>


    {{-- ? Parte Inferior --}}
    <div class="col-span-12 my-2 grid grid-cols-12 gap-5 bg-blue-50 p-5 shadow-xl sm:rounded-lg">
        {{-- Tabla --}}
        <div class="col-span-6">
            <nav class="border-b border-gray-200">
                <ul class="flex items-end"
                    aria-label="Tabs">
                    <li>
                        <button class='rounded-t-md border border-b-0 border-solid border-gray-300 bg-white px-4 py-2 text-lg font-medium text-blue-600 transition-all duration-300'
                                type="button">
                            {{ __('labels.logs') }}
                        </button>
                    </li>
                </ul>
            </nav>
            <div>
                <div id="container_table"
                     class="items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300"
                     wire:ignore>
                    <div class="p-3">
                        <table id="table_logs"
                               class="datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('labels.id') }}</th>
                                    <th>{{ __('labels.date') }}</th>
                                    <th>{{ __('labels.user') }}</th>
                                    <th>{{ __('labels.action') }}</th>
                                    <th>{{ __('labels.form') }}</th>
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
                            {{ __('labels.data_log') }}
                        </button>
                    </li>
                </ul>
            </nav>
            <div>
                <div id="container_data"
                     class="items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300"
                     x-bind:style="{ height: height }">
                    <div class="grid grid-cols-12 p-3">
                        {{--  ID --}}
                        <x-input-group id="input_id"
                                       class="col-span-6"
                                       x-model="$wire.logForm.id"
                                       disabled="true"
                                       placeholder="{{ __('labels.id') }}"
                                       icono="<i class='fa-solid fa-hashtag'></i>"
                                       tooltip="{{ __('labels.id') }}" />

                        {{-- Date --}}
                        <x-input-group class="col-span-6"
                                       x-model="$wire.logForm.date"
                                       disabled="true"
                                       placeholder="{{ __('labels.date') }}"
                                       icono="<i class='fa-solid fa-calendar-days'></i>"
                                       tooltip="{{ __('labels.date') }}" />

                        {{-- User --}}
                        <x-input-group class="col-span-6"
                                       x-model="$wire.logForm.username"
                                       disabled="true"
                                       placeholder="{{ __('labels.user') }}"
                                       icono="<i class='fa-solid fa-user'></i>"
                                       tooltip="{{ __('labels.user') }}" />

                        {{-- Town --}}
                        <x-input-group class="col-span-6"
                                       x-model="$wire.logForm.town_name"
                                       disabled="true"
                                       placeholder="{{ __('labels.town') }}"
                                       icono="<i class='fa-solid fa-city'></i>"
                                       tooltip="{{ __('labels.town') }}" />

                        {{-- Formulario --}}
                        <x-input-group class="col-span-6"
                                       x-model="$wire.logForm.form"
                                       disabled="true"
                                       placeholder="{{ __('labels.form') }}"
                                       icono="<i class='fa-solid fa-table-list'></i>"
                                       tooltip="{{ __('labels.form') }}" />

                        {{-- Accion --}}
                        <x-input-group class="col-span-6"
                                       x-model="$wire.logForm.action"
                                       disabled="true"
                                       placeholder="{{ __('labels.action') }}"
                                       icono="<i class='fa-solid fa-circle-exclamation'></i>"
                                       tooltip="{{ __('labels.action') }}" />

                        <x-textarea-group class="col-span-12"
                                          x-model="$wire.logForm.description"
                                          disabled="true"
                                          placeholder="{{ __('labels.description') }}"
                                          icono="<i class='fa-solid fa-circle-exclamation'></i>"
                                          tooltip="{{ __('labels.description') }}" />

                        {{-- <textarea></textarea> --}}

                    </div>
                </div>
            </div>
        </div>


    </div>
