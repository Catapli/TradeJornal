<div class="relative"
     x-data="singleDatePicker()"
     x-on:updateMyDatePicker.window="updateDisplayValue($event.detail.date)"> {{-- Nombre de función cambiado --}}
    <!-- Input field -->

    <div class="relative justify-items-center px-2 py-1">
        <div class="peer flex w-full rounded-lg shadow-sm">
            <div class="inline-flex min-w-fit items-center rounded-s-md border border-e-0 border-gray-200 bg-gray-50 px-4 dark:border-neutral-600 dark:bg-neutral-700">
                <span class="text-lg text-gray-500 dark:text-neutral-400">{!! $icono !!}</span>
            </div>
            {{-- x-model se mantiene, ahora se sincronizará con la fecha única --}}
            <input class='block w-full rounded-e-lg border-gray-200 px-4 py-3 pe-11 text-sm shadow-inner focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:pointer-events-none disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600'
                   autocomplete="off"
                   type="text"
                   x-model="$wire.date"
                   {{-- Se espera que $wire.date sea la propiedad Livewire --}}
                   @click="showPicker = true"
                   @keydown.escape="showPicker = false"
                   {{-- Añadido: cerrar con Esc --}}
                   readonly
                   placeholder="{{ $placeholder }}">
        </div>
        <div class="absolute -bottom-4 left-1/2 z-20 -translate-x-1/2 translate-y-2 scale-95 whitespace-nowrap rounded bg-neutral-950 px-2 py-1 text-center text-sm text-white opacity-0 transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 peer-focus:translate-y-0 peer-focus:scale-100 peer-focus:opacity-100 dark:bg-white dark:text-neutral-900"
             role="tooltip">
            {{ $tooltip }}
        </div>
    </div>

    <!-- Calendar Picker -->
    <div class="absolute left-0 top-12 z-50 mt-1 w-[300px] rounded-lg border bg-white p-4 shadow-lg dark:border-neutral-700 dark:bg-neutral-800"
         x-show="showPicker"
         @click.away="showPicker = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;"> {{-- Evita FOUC (Flash Of Unstyled Content) --}}
        <!-- Calendar Header -->
        <div class="mb-4 flex items-center justify-between">
            <button class="text-gray-600 hover:text-gray-900 dark:text-neutral-400 dark:hover:text-neutral-200"
                    type="button"
                    @click="previousMonth()">
                <svg class="h-6 w-6"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div class="text-lg font-bold text-gray-800 dark:text-neutral-200"
                 x-text="monthNames[month] + ' ' + year"></div>
            <button class="text-gray-600 hover:text-gray-900 dark:text-neutral-400 dark:hover:text-neutral-200"
                    type="button"
                    @click="nextMonth()">
                <svg class="h-6 w-6"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-1">
            <!-- Days header -->
            <template x-for="(day, index) in ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa']"
                      :key="index">
                <div class="py-1 text-center text-sm text-gray-600 dark:text-neutral-400"
                     x-text="day"></div>
            </template>

            <!-- Blank days -->
            <template x-for="(blankday, index) in blankDays"
                      :key="'blank' + index">
                <div class="py-1 text-center"></div>
            </template>

            <!-- Days -->
            <template x-for="(date, index) in daysInMonth"
                      :key="'day' + index">
                <div class="cursor-pointer rounded-lg py-1 text-center text-gray-700 hover:bg-blue-100 dark:text-neutral-300 dark:hover:bg-blue-900"
                     @click="selectDate(date)"
                     x-text="date"
                     :class="{
                         'bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-600': isSelected(date),
                         /* 'bg-blue-100': isInRange(date), <-- Eliminado */
                         'text-gray-300 cursor-not-allowed hover:bg-transparent dark:text-neutral-600 dark:hover:bg-transparent': !isSelectable(date)
                     }">
                </div>
            </template>
        </div>

        {{-- Sección "Range Preview" eliminada --}}

        <!-- Actions -->
        <div class="mt-4 flex justify-end space-x-2">
            <button class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 dark:text-neutral-400 dark:hover:text-neutral-200"
                    @click="clearDate()"> {{-- Nombre de función cambiado --}}
                {{ __('labels.clean') }}
            </button>
            <button class="rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600"
                    @click="applyDate()"> {{-- Nombre de función cambiado --}}
                {{ __('labels.apply') }}
            </button>
        </div>
    </div>
</div>

<script>
    function singleDatePicker() { // Nombre de función cambiado
        return {
            showPicker: false,
            // dateRangeText: '', // Eliminado
            startDate: '', // Representa la fecha única seleccionada
            // endDate: '', // Eliminado
            month: '',
            year: '',
            daysInMonth: [],
            blankDays: [],
            monthNames: [this.$l('january'), this.$l('february'), this.$l('march'), this.$l('april'), this.$l('may'), this.$l('june'), this.$l('july'), this.$l('august'), this.$l('september'), this.$l('october'), this.$l('november'), this.$l(
                'december')],

            // Guarda la propiedad Livewire para fácil acceso (asume que se llama 'date')
            wireDate: null,


            init() {
                window.addEventListener("updateMyDatePicker", (event) => {
                    let e = event.detail.date;
                    this.$wire.date = e;
                });

                window.addEventListener("resetDatePicker", (event) => {
                    this.$wire.date = null;
                });
                // Inicializa wireDate con el valor actual de la propiedad Livewire
                this.wireDate = this.$wire.get('date'); // Asegúrate que 'date' es el nombre correcto de tu propiedad Livewire

                // Si hay un valor inicial en Livewire y es válido, úsalo para inicializar
                if (this.wireDate && this.isValidDate(this.parseDate(this.wireDate))) {
                    this.startDate = this.wireDate;
                    const initialDate = this.parseDate(this.wireDate);
                    this.month = initialDate.getMonth();
                    this.year = initialDate.getFullYear();
                } else {
                    // Si no, usa la fecha de hoy para la vista inicial del calendario
                    let today = new Date();
                    this.month = today.getMonth();
                    this.year = today.getFullYear();
                    this.startDate = ''; // Asegura que no haya selección inicial
                }
                this.getNoOfDays();

                // Observa cambios externos en la propiedad Livewire
                this.$watch('$wire.date', (newValue) => {
                    if (newValue !== this.startDate) { // Solo actualiza si es diferente
                        if (newValue && this.isValidDate(this.parseDate(newValue))) {
                            this.startDate = newValue;
                            const newDate = this.parseDate(newValue);
                            // Cambia la vista del calendario si la nueva fecha está en otro mes/año
                            if (newDate.getMonth() !== this.month || newDate.getFullYear() !== this.year) {
                                this.month = newDate.getMonth();
                                this.year = newDate.getFullYear();
                                this.getNoOfDays(); // Regenera los días del mes
                            }
                        } else if (!newValue) { // Si Livewire la pone a null/''
                            this.startDate = '';
                            // Opcional: resetear vista a hoy si se limpia externamente
                            // let today = new Date();
                            // this.month = today.getMonth();
                            // this.year = today.getFullYear();
                            // this.getNoOfDays();
                        }
                    }
                });
            },

            isValidDate(d) {
                return d instanceof Date && !isNaN(d);
            },

            isSelected(date) {
                const currentDate = this.formatDate(new Date(this.year, this.month, date));
                return currentDate === this.startDate; // Compara solo con startDate
            },

            // isInRange ya no es necesaria para la lógica principal
            /*
            isInRange(date) {
                // Esta función ya no se usa activamente en las clases CSS
                return false;
            },
            */

            isSelectable(date) {
                const currentDate = new Date(this.year, this.month, date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                // Permite seleccionar fechas hasta hoy inclusive
                return currentDate <= today;
                // return true; // Descomenta para permitir cualquier fecha
            },

            selectDate(date) {
                if (!this.isSelectable(date)) return;

                // Simplemente asigna la fecha seleccionada a startDate
                this.startDate = this.formatDate(new Date(this.year, this.month, date));

                // Opcional: Aplicar y cerrar inmediatamente al hacer clic
                // this.applyDate();
            },

            getNoOfDays() {
                let daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                // getDay() devuelve 0 para Domingo, 1 para Lunes...
                let firstDayOfWeek = new Date(this.year, this.month, 1).getDay(); // Domingo = 0

                this.blankDays = Array(firstDayOfWeek).fill(null);
                this.daysInMonth = Array(daysInMonth).fill(null).map((_, i) => i + 1);
            },

            formatDate(date) {
                if (!this.isValidDate(date)) return '';
                let day = date.getDate().toString().padStart(2, '0');
                let month = (date.getMonth() + 1).toString().padStart(2, '0'); // Mes es 1-based para mostrar
                let year = date.getFullYear();
                return `${day}/${month}/${year}`; // Formato DD/MM/YYYY
            },

            parseDate(dateStr) {
                // Intenta parsear el formato DD/MM/YYYY
                if (!dateStr || typeof dateStr !== 'string') return null;
                const parts = dateStr.split('/');
                if (parts.length === 3) {
                    const day = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10); // Mes es 1-based en el string
                    const year = parseInt(parts[2], 10);
                    // Verifica que sean números válidos
                    if (!isNaN(day) && !isNaN(month) && !isNaN(year) && month >= 1 && month <= 12) {
                        // Creamos la fecha (Month en Date es 0-based)
                        const date = new Date(year, month - 1, day);
                        // Verificación extra: ¿la fecha creada coincide con los números?
                        if (date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day) {
                            return date;
                        }
                    }
                }
                return null; // Retorna null si el formato no es válido o la fecha no existe
            },


            previousMonth() {
                if (this.month === 0) {
                    this.year--;
                    this.month = 11;
                } else {
                    this.month--;
                }
                this.getNoOfDays();
            },

            nextMonth() {
                if (this.month === 11) {
                    this.year++;
                    this.month = 0;
                } else {
                    this.month++;
                }
                this.getNoOfDays();
            },

            clearDate() { // Nombre de función cambiado
                this.startDate = '';
                this.$wire.date = ''; // Actualiza Livewire a través de x-model
                // Opcional: cerrar al limpiar
                // this.showPicker = false;
                // Opcional: resetear vista a hoy
                // let today = new Date();
                // this.month = today.getMonth();
                // this.year = today.getFullYear();
                // this.getNoOfDays();
            },

            applyDate() { // Nombre de función cambiado
                // Asigna la fecha seleccionada a la propiedad Livewire
                // x-model debería encargarse de esto, pero lo hacemos explícito para asegurar
                var variable = this.$wire.variable;
                this.$wire.date = this.startDate;
                this.$wire.$parent.$set(variable, this.startDate)
                this.showPicker = false;
            }
        }
    }
</script>
