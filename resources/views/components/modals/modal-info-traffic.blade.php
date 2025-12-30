@props(['show', 'img_amb', 'img_lpr', 'video'])
<div class="fixed inset-0 z-50 flex justify-center overflow-auto rounded-br-xl rounded-tl-xl rounded-tr-xl bg-opacity-100 backdrop-blur-md transition-opacity duration-300 ease-out"
     data-modal-backdrop="static"
     x-show="{{ $show }}"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-90"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-90"
     @keydown.window.escape="{{ $show }} = false"
     @keydown.window.enter="{{ $show }} = false"
     x-cloak
     tabindex="-1">
    <div class="relative w-full max-w-7xl overflow-y-visible p-8">





        <!-- Modal content -->
        <div class="relative rounded-lg border border-solid border-primary bg-primary shadow-2xl dark:bg-gray-700"
             @click.away="{{ $show }} = false">

            {{-- ? Loading --}}
            <div wire:loading
                 wire:target='getRegisterTable'>
                <x-loader-modal></x-loader-modal>
            </div>

            <!-- Modal header -->
            <div class="flex min-h-14 items-center justify-between rounded-t-lg border-primary bg-primary px-4 py-2">
                <div class="flex w-auto gap-x-3 rounded-t-md">
                    <img class="h-14"
                         src="{{ asset('img/detrafic_logo.png') }}"
                         alt="Icono" />

                </div>
                <div class="content-end text-2xl font-semibold text-white">
                    {{ __('labels.data_traffic_reg') }}
                </div>

                <div>
                    <i class="fa-solid fa-xmark cursor-pointer text-3xl text-white transition-all duration-200 ease-in-out hover:scale-125"
                       @click="{{ $show }} = false"></i>
                </div>
            </div>
            <!-- Modal body -->
            <div class="min-h-15 flex h-auto min-h-16 w-full bg-white">
                <div class="flex w-full"
                     x-data="{ file: 'lpr', activeTab: 'data' }"
                     x-init="$watch('{{ $show }}', value => {
                         if (value) activeTab = 'data'
                     })">
                    <div class="grid w-full grid-cols-12 p-3">
                        <div class="col-span-6 grid grid-cols-12 gap-y-3 px-3 py-2">
                            <div class="col-span-12 h-full w-full">
                                <div class="flex justify-center"
                                     x-show="file === 'amb'">
                                    @if ($img_amb !== null)
                                        <img class="h-[500px] w-full"
                                             src="{{ $img_amb->public_url }}"
                                             alt="">
                                    @else
                                        <img class="h-[500px] w-full object-scale-down"
                                             src="{{ asset('img/nodisponible.png') }}"
                                             alt="">
                                    @endif
                                </div>
                                <div class="flex justify-center"
                                     x-show="file === 'lpr'">
                                    @if ($img_lpr !== null)
                                        <img class="h-[500px] w-full"
                                             src="{{ $img_lpr->public_url }}"
                                             alt="">
                                    @else
                                        <img class="h-[500px] w-full object-scale-down"
                                             src="{{ asset('img/nodisponible.png') }}"
                                             alt="">
                                    @endif

                                </div>
                                <div x-show="file === 'video'">
                                    @if ($video !== null)
                                        <video class="h-[500px] w-full"
                                               controls>
                                            <source src="{{ $video->public_url }}"
                                                    type="video/mp4">
                                        </video>
                                    @else
                                        <img class="h-[500px] w-full object-scale-down"
                                             src="{{ asset('img/nodisponible.png') }}"
                                             alt="">
                                    @endif

                                </div>
                            </div>
                            <div class="col-span-12 h-min">
                                <div class="flex gap-1">


                                    {{-- ? Descargar Imagen --}}
                                    <x-buttons.button-modal class="flex-1"
                                                            x-show="$wire.canDownload"
                                                            @click="resetFilters()"
                                                            icon="fa-solid fa-download"
                                                            tooltip="{{ __('labels.download_img') }}" />
                                    {{-- ? Ver PDF --}}
                                    <x-buttons.button-modal class="flex-1"
                                                            @click="resetFilters()"
                                                            text_icon="PDF"
                                                            x-show="$wire.canDownload"
                                                            disabled="true"
                                                            icon="fa-solid fa-file-pdf"
                                                            tooltip="{{ __('labels.download_pdf') }}" />

                                    {{-- ? Imagen LPR --}}
                                    <x-buttons.button-modal class="flex-1"
                                                            @click="file = 'lpr'"
                                                            disabled="file === 'lpr'"
                                                            icon="fa-regular fa-image"
                                                            text_icon="LPR"
                                                            tooltip="{{ __('labels.img_lpr') }}" />

                                    {{-- ? Imagen AMB --}}
                                    <x-buttons.button-modal class="flex-1"
                                                            @click="file = 'amb'"
                                                            {{-- disabled="true" --}}
                                                            disabled="file === 'amb'"
                                                            icon="fa-regular fa-image"
                                                            text_icon="AMB"
                                                            tooltip="{{ __('labels.img_amb') }}" />
                                    {{-- ? Video --}}
                                    <x-buttons.button-modal class="flex-1"
                                                            @click="file = 'video'"
                                                            disabled="file === 'video'"
                                                            icon="fa-solid fa-video"
                                                            text_icon="{{ __('labels.video') }}"
                                                            tooltip="{{ __('labels.video') }}" />
                                    {{-- ? Zoom --}}
                                    <x-buttons.button-modal class="flex-1"
                                                            @click="resetFilters()"
                                                            disabled="true"
                                                            icon="fa-solid fa-magnifying-glass-plus"
                                                            tooltip="{{ __('labels.zoom') }}" />
                                </div>
                            </div>
                        </div>
                        <div class="col-span-6 px-3 py-2">
                            {{-- ? Pestañas --}}
                            <nav class="border-b border-gray-200">
                                <ul class="flex items-end"
                                    aria-label="Tabs">
                                    {{-- ? Pestaña 1 --}}
                                    <li class="cursor-pointer hover:cursor-pointer">
                                        <button class="cursor-pointer rounded-t-md border border-b-0 border-solid border-gray-300 px-4 py-2 text-lg font-medium transition-all duration-300"
                                                @click="activeTab = 'data'"
                                                :class="{
                                                    'bg-blue-100 text-blue-600': activeTab === 'data',
                                                    'text-gray-500 hover:bg-gray-100': activeTab !== 'data'
                                                }"
                                                type="button">
                                            {{ __('labels.data_veh') }}
                                        </button>
                                    </li>
                                    {{-- ? Pestaña 2 --}}
                                    <li class="cursor-pointer">
                                        <button class="cursor-pointer rounded-t-md border border-b-0 border-solid border-gray-300 px-4 py-2 text-lg font-medium transition-all duration-300"
                                                @click="activeTab = 'map'"
                                                :class="{
                                                    'bg-blue-100 text-blue-600': activeTab === 'map',
                                                    'text-gray-500 hover:bg-gray-100': activeTab !== 'map'
                                                }"
                                                type="button">
                                            {{ __('labels.map') }}
                                        </button>
                                    </li>
                                </ul>
                            </nav>

                            {{-- ? Contenido cada pestaña --}}
                            <div>
                                {{-- ? Contenido 1 --}}
                                <div class="grid grid-cols-12 items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white p-2 shadow-lg transition-all duration-300"
                                     x-show="activeTab === 'data'">
                                    {{-- ? Camara --}}
                                    <x-input-group id="camera"
                                                   class="col-span-12"
                                                   x-model="$wire.camera"
                                                   disabled="true"
                                                   back="true"
                                                   placeholder="{{ __('labels.camera') }}"
                                                   icono="<i class='fa-solid fa-video text-white'></i>"
                                                   tooltip="{{ __('labels.camera') }}" />

                                    {{-- ? Matricula --}}
                                    <x-input-group id="plate"
                                                   class="col-span-6"
                                                   x-model="$wire.plate"
                                                   disabled="true"
                                                   back="true"
                                                   plate="true"
                                                   placeholder="{{ __('labels.plate') }}"
                                                   icono=" <img class='h-6' src='{{ asset('img/detrafic_logo.png') }}'/>"
                                                   tooltip="{{ __('labels.plate') }}" />

                                    {{-- ? Dia --}}
                                    <x-input-group id="date"
                                                   class="col-span-6"
                                                   x-model="$wire.date"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.date') }}"
                                                   icono=" <i class='fa-regular fa-calendar-days'></i>"
                                                   tooltip="{{ __('labels.date') }}" />

                                    {{-- ? Hora --}}
                                    <x-input-group id="hour"
                                                   class="col-span-6"
                                                   x-model="$wire.time"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.hour') }}"
                                                   icono=" <i class='fa-regular fa-calendar-days'></i>"
                                                   tooltip="{{ __('labels.hour') }}" />

                                    {{-- ? Municipio --}}
                                    <x-input-group id="town"
                                                   class="col-span-6"
                                                   x-model="$wire.town"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.town') }}"
                                                   icono=" <i class='fa-solid fa-city'></i>"
                                                   tooltip="{{ __('labels.town') }}" />

                                    {{-- ? Latitud Y Longitud --}}
                                    <x-input-group id="coords"
                                                   class="col-span-6"
                                                   x-model="$wire.coords"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.coords') }}"
                                                   icono=" <i class='fa-solid fa-location-dot'></i>"
                                                   tooltip="{{ __('labels.coords') }}" />

                                    {{-- ? Direccion --}}
                                    <x-input-group id="direction"
                                                   class="col-span-6"
                                                   x-model="$wire.direction"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.direction') }}"
                                                   icono=" <i class='fa-solid fa-arrow-right-arrow-left fa-rotate-90'></i>"
                                                   tooltip="{{ __('labels.direction') }}" />

                                    {{-- ? Marca --}}
                                    <x-input-group id="brand"
                                                   class="col-span-6"
                                                   x-model="$wire.brand"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.brand_null') }}"
                                                   icono=" <i class='fa-solid fa-trademark'></i>"
                                                   tooltip="{{ __('labels.brand') }}" />

                                    {{-- ? Tipo de Vehiculo --}}
                                    <x-input-group id="type_veh"
                                                   class="col-span-6"
                                                   x-model="$wire.type_veh"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.typeVeh_null') }}"
                                                   icono=" <i class='fa-solid fa-car'></i>"
                                                   tooltip="{{ __('labels.typeVeh') }}" />

                                    {{-- ? Color --}}
                                    <x-input-group id="color"
                                                   class="col-span-6"
                                                   x-model="$wire.color"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.color_null') }}"
                                                   icono=" <i class='fa-solid fa-palette'></i>"
                                                   tooltip="{{ __('labels.color') }}" />

                                    {{-- ? Pais --}}
                                    <x-input-group id="country"
                                                   class="col-span-6"
                                                   x-model="$wire.country"
                                                   disabled="true"
                                                   placeholder="{{ __('labels.country_null') }}"
                                                   icono=" <i class='fa-solid fa-globe'></i>"
                                                   tooltip="{{ __('labels.country') }}" />
                                </div>

                                {{-- ? Contenido 2 --}}
                                <div class="rounded-b-md border border-gray-300 bg-white p-4"
                                     x-show="activeTab === 'map'">
                                    <div id="map"
                                         class="h-100 w-full"
                                         x-init="$watch('activeTab', value => {
                                             if (value === 'map') initMap()
                                         })">
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col-span-12">
                            <div id="container_table_similar"
                                 class="items-center rounded-b-md rounded-r-md border border-solid border-gray-300 bg-white shadow-lg transition-all duration-300"
                                 wire:ignore>
                                <div class="px-3">
                                    <table id="table_af_similars"
                                           class="datatable">
                                        <thead>
                                            <tr>
                                                <th>{{ __('labels.date') }}</th>
                                                <th>{{ __('labels.camera') }}</th>
                                                <th>{{ __('labels.poblation_org') }}</th>
                                                <th>{{ __('labels.direction') }}</th>
                                                <th>{{ __('labels.zone') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Modal Footer --}}
            <div class="flex h-auto w-full justify-end rounded-b-lg border-t border-gray-200 bg-white px-4 py-2">
                <i class="fa-solid fa-circle-check cursor-pointer text-3xl transition-all duration-200 ease-in-out hover:scale-125 hover:text-green-500"
                   @click="{{ $show }} = false"></i>
            </div>
        </div>
    </div>

</div>
