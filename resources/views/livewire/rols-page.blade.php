<div class="max-w-fullxl mx-auto grid grid-cols-12 sm:px-6 lg:px-8"
     x-data="rols()"
     x-init="init()">

    {{-- ? Loading --}}
    <div wire:loading
         wire:target='insertZone, updateZone, deleteZone, findByID'>
        <x-loader></x-loader>
    </div>


    {{-- ? Loading Mediante JS --}}
    <div x-show="showLoading">
        <x-loader></x-loader>
    </div>

    {{-- ? Show Alerta --}}
    <x-modal-template show="showAlert">
    </x-modal-template>

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
                            {{ __('menu.only_rols') }}
                        </button>
                    </li>
                </ul>
            </nav>
            <div>
                <div id="container_table"
                     class="items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300"
                     wire:ignore>
                    <div class="p-3">
                        <table id="table_rols"
                               class="datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('labels.id') }}</th>
                                    <th>{{ __('labels.name') }}</th>
                                    <th>{{ __('labels.nickname') }}</th>
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
                                       class="col-span-4"
                                       x-model="$wire.id_rol"
                                       disabled="true"
                                       placeholder="{{ __('labels.id') }}"
                                       icono="<i class='fa-solid fa-hashtag'></i>"
                                       tooltip="{{ __('labels.id') }}" />

                        {{-- ? Nombre Rol --}}
                        <x-input-group id="input_name"
                                       class="col-span-4"
                                       x-ref="input_name"
                                       x-model="$wire.name"
                                       placeholder="{{ __('labels.name') }}"
                                       icono="<i class='fa-solid fa-pen-to-square'></i>"
                                       tooltip="{{ __('labels.name') }}" />

                        {{-- ? Label --}}
                        <x-input-group id="input_label"
                                       class="col-span-4"
                                       x-ref="input_label"
                                       x-model="$wire.label"
                                       placeholder="{{ __('labels.nickname') }}"
                                       icono="<i class='fa-solid fa-signature'></i>"
                                       tooltip="{{ __('labels.nickname') }}" />

                        <div class="col-span-12 mt-2 overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="w-12 p-3 text-center">
                                            {{-- <input class="rounded text-blue-600 focus:ring-blue-500"
                                                   type="checkbox" /> --}}
                                        </th>
                                        <th class="p-3 text-left text-sm font-medium text-gray-700">Sección</th>
                                        <th class="p-3 text-center text-sm font-medium text-gray-700">Acceder</th>
                                        <th class="p-3 text-center text-sm font-medium text-gray-700">Escribir</th>
                                        <th class="p-3 text-center text-sm font-medium text-gray-700">Eliminar</th>
                                        <th class="p-3 text-center text-sm font-medium text-gray-700">Descargar</th>
                                    </tr>
                                </thead>


                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($sections as $section)
                                        <tr class="hover:bg-gray-50"
                                            wire:key="permission-row-{{ $section->id }}-{{ $id_rol }}">
                                            <td class="p-1 text-center">
                                                <input class="rounded text-blue-600 focus:ring-blue-500"
                                                       type="checkbox"
                                                       x-data="{
                                                           allChecked: {{ $permissions[$section->id]['can_read'] && $permissions[$section->id]['can_write'] && $permissions[$section->id]['can_delete'] && $permissions[$section->id]['can_download'] ? 'true' : 'false' }}
                                                       }"
                                                       x-model="allChecked"
                                                       x-init="$watch('allChecked', val => {
                                                           $wire.set('permissions.{{ $section->id }}.can_read', val);
                                                           $wire.set('permissions.{{ $section->id }}.can_write', val);
                                                           $wire.set('permissions.{{ $section->id }}.can_delete', val);
                                                           $wire.set('permissions.{{ $section->id }}.can_download', val);
                                                       })" />
                                            </td>
                                            <td class="p-1 text-sm text-gray-800">{{ $section->label }}</td>
                                            <td class="p-1 text-center">
                                                <input class="permission-col-acceder rounded text-blue-600 focus:ring-blue-500"
                                                       type="checkbox"
                                                       wire:model="permissions.{{ $section->id }}.can_read" />
                                            </td>
                                            <td class="p-1 text-center">
                                                <input class="permission-col-escribir rounded text-blue-600 focus:ring-blue-500"
                                                       type="checkbox"
                                                       wire:model="permissions.{{ $section->id }}.can_write" />
                                            </td>
                                            <td class="p-1 text-center">
                                                <input class="permission-col-eliminar rounded text-blue-600 focus:ring-blue-500"
                                                       type="checkbox"
                                                       wire:model="permissions.{{ $section->id }}.can_delete" />
                                            </td>
                                            <td class="p-1 text-center">
                                                <input class="permission-col-descargar rounded text-blue-600 focus:ring-blue-500"
                                                       type="checkbox"
                                                       wire:model="permissions.{{ $section->id }}.can_download" />
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                                <tfoot class="bg-gray-100 font-medium">
                                    <tr>
                                        <td class="p-3 text-center">
                                            <input class="rounded text-blue-600 focus:ring-blue-500"
                                                   type="checkbox"
                                                   @click="toggleAllCheckboxes($event.target)" />
                                        </td>
                                        <td class="p-3 text-sm text-gray-700">Marcar Columna</td>
                                        <td class="p-3 text-center">
                                            <input class="rounded text-blue-600 focus:ring-blue-500"
                                                   type="checkbox"
                                                   @click="toggleColumnCheckboxes('permission-col-acceder')" />
                                        </td>
                                        <td class="p-3 text-center">
                                            <input class="rounded text-blue-600 focus:ring-blue-500"
                                                   type="checkbox"
                                                   @click="toggleColumnCheckboxes('permission-col-escribir')" />
                                        </td>
                                        <td class="p-3 text-center">
                                            <input class="rounded text-blue-600 focus:ring-blue-500"
                                                   type="checkbox"
                                                   @click="toggleColumnCheckboxes('permission-col-eliminar')" />
                                        </td>
                                        <td class="p-3 text-center">
                                            <input class="rounded text-blue-600 focus:ring-blue-500"
                                                   type="checkbox"
                                                   @click="toggleColumnCheckboxes('permission-col-descargar')" />
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>


                    </div>
                </div>
            </div>
        </div>


    </div>

    <div class="fixed bottom-10 right-5 z-20 flex w-auto rounded-2xl bg-backbuttons">
        {{-- Boton Nuevo --}}
        @if ($this->canWrite)
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
        @if ($this->canWrite)
            <x-button-action type="edit"
                             @click="update()"
                             x-show="registerSelected"
                             x-cloak
                             icon="fa-solid fa-file-pen"
                             tooltip="{{ __('labels.edit') }}" />
        @endif


        {{-- Boton Delete --}}
        @if ($this->canDelete)
            <x-button-action type="delete"
                             x-show="registerSelected"
                             @click="deleteRol()"
                             x-cloak
                             icon="fa-solid fa-trash"
                             tooltip="{{ __('labels.delete') }}" />
        @endif


    </div>
</div>
