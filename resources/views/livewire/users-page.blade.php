<div class="max-w-fullxl mx-auto grid grid-cols-12 sm:px-6 lg:px-8"
     x-data="users()"
     {{-- x-init="init()" --}}>


    {{-- ? Loading --}}
    <div wire:loading
         wire:target='findUserById,insertUser, deleteUser'>
        <x-loader></x-loader>
    </div>


    {{-- ? Loading Mediante JS --}}
    <div x-show="showLoading">
        <x-loader></x-loader>
    </div>

    {{-- ? Show Alerta --}}
    <x-modal-template show="showAlert">
    </x-modal-template>


    {{-- ? Filtros --}}
    <div class="col-span-12 my-2 bg-blue-50 p-5 shadow-xl sm:rounded-lg">
        {{-- ? Encabezadode pestañas --}}
        <nav class="border-b border-gray-200">
            <ul class="flex items-end"
                aria-label="Tabs">
                <li>
                    <button class='rounded-t-md border border-b-0 border-solid border-gray-300 bg-white px-4 py-2 text-lg font-medium text-blue-600 transition-all duration-300'
                            type="button">
                        {{ __('labels.filters_users') }}
                    </button>
                </li>
            </ul>
        </nav>
        <div>
            <div class="grid grid-cols-12 items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white p-2 shadow-lg transition-all duration-300">
                {{-- ? municipio --}}
                <x-select-group id="filter_town"
                                class="col-span-3"
                                x-model="$wire.filter_town"
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

                {{-- ? nombre Usuario --}}
                <x-input-group id="filter_name"
                               class="col-span-3"
                               x-model="$wire.filter_name"
                               placeholder="{{ __('labels.user') }}"
                               icono="<i class='fa-solid fa-user'></i>"
                               tooltip="{{ __('labels.user') }}" />

                {{-- ? Activo --}}
                <x-select-group id="filter_active"
                                class="col-span-3"
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
                <div class="col-span-3 flex justify-center gap-2 px-3">
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
                            {{ __('labels.users') }}
                        </button>
                    </li>
                </ul>
            </nav>
            <div>
                <div id="container_table"
                     class="items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300"
                     wire:ignore>
                    <div class="p-3">
                        <table id="table_users"
                               class="datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('labels.id') }}</th>
                                    <th>{{ __('labels.user') }}</th>
                                    <th>{{ __('labels.entity') }}</th>
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
                            {{ __('labels.data_user') }}
                        </button>
                    </li>
                </ul>
            </nav>
            <div>
                <div id="container_data"
                     class="items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300"
                     x-bind:style="{ height: height }">
                    <div class="grid grid-cols-12 p-3">
                        {{-- ? ID --}}
                        <x-input-group id="input_id"
                                       class="col-span-6"
                                       x-model="$wire.id_user"
                                       disabled="true"
                                       placeholder="{{ __('labels.id') }}"
                                       icono="<i class='fa-solid fa-hashtag'></i>"
                                       tooltip="{{ __('labels.id') }}" />

                        {{-- ? Activo --}}
                        <div class="col-span-6 flex items-center justify-center gap-5 px-4 py-2">
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

                        {{-- ? Nombre Usuario --}}
                        <x-input-group id="input_name"
                                       class="col-span-6"
                                       x-ref="input_name"
                                       x-model="$wire.user"
                                       placeholder="{{ __('labels.name_user') }}"
                                       icono="<i class='fa-solid fa-user'></i>"
                                       tooltip="{{ __('labels.name_user') }}" />

                        {{-- ? Email --}}
                        <x-input-group id="input_email"
                                       class="col-span-6"
                                       x-ref="input_email"
                                       x-model="$wire.email"
                                       x-bind:disabled="registerSelected"
                                       placeholder="{{ __('labels.email') }}"
                                       icono="<i class='fa-solid fa-at'></i>"
                                       tooltip="{{ __('labels.email') }}" />




                        {{-- ? Password --}}
                        <x-input-passwd id="input_passwd"
                                        class="col-span-6"
                                        x-ref="input_passwd"
                                        x-model="$wire.password"
                                        x-bind:disabled="registerSelected"
                                        placeholder="{{ __('labels.password') }}"
                                        icono="<i class='fa-solid fa-fingerprint'></i>"
                                        tooltip="{{ __('labels.password') }}" />

                        {{-- ? Repeat Password --}}
                        <x-input-passwd id="input_r_passwd"
                                        class="col-span-6"
                                        x-ref="input_r_passwd"
                                        x-bind:disabled="registerSelected"
                                        x-model="$wire.repeat_password"
                                        placeholder="{{ __('labels.r_password') }}"
                                        icono="<i class='fa-solid fa-fingerprint'></i>"
                                        tooltip="{{ __('labels.r_password') }}" />



                        {{-- ? municipio --}}
                        <x-select-group id="town_selected"
                                        class="col-span-12"
                                        x-model="$wire.town_selected"
                                        icono="<i class='fa-solid fa-city'></i>"
                                        tooltip="{{ __('labels.entity') }}">
                            <x-slot name="options">
                                <option value="">{{ __('labels.select_entity') }}</option>
                                @foreach ($towns as $town)
                                    <option value="{{ $town->id }}">{{ $town->town }}</option>
                                @endforeach
                            </x-slot>
                        </x-select-group>

                        {{-- ? Rol --}}
                        <x-select-group id="role_selected"
                                        class="col-span-12"
                                        x-model="$wire.role_selected"
                                        icono="<i class='fa-solid fa-users-gear'></i>"
                                        tooltip="{{ __('labels.rol') }}">
                            <x-slot name="options">
                                <option value="">{{ __('labels.select_rol') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->label }}</option>
                                @endforeach
                            </x-slot>
                        </x-select-group>
                    </div>
                </div>
            </div>
        </div>


        <div class="fixed bottom-5 right-5 z-20 flex w-auto rounded-2xl bg-backbuttons p-2">
            {{-- Boton Nuevo --}}
            @if ($canWrite)
                <x-button-action type="new"
                                 @click="insertUser()"
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
                                 @click="deleteUser()"
                                 x-cloak
                                 icon="fa-solid fa-trash"
                                 tooltip="{{ __('labels.delete') }}" />
            @endif

        </div>
    </div>
</div>
