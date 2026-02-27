<div class="relative"
     x-data="singleDatePicker()">
    <!-- Input field -->
    <div class="relative justify-items-center px-2 py-1">
        <div class="peer flex w-full rounded-lg shadow-sm">
            <div class="inline-flex min-w-[55px] items-center justify-center rounded-s-md border border-e-0 border-gray-200 bg-gray-50 px-4 dark:border-neutral-600 dark:bg-neutral-700">
                <span class="text-lg text-gray-500 dark:text-neutral-400">{!! $icono !!}</span>
            </div>
            <input class="block w-full rounded-e-lg border-gray-200 px-4 py-3 pe-11 text-sm shadow-inner focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:pointer-events-none disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                   autocomplete="off"
                   type="text"
                   x-model="$wire.date"
                   @click="showPicker = true"
                   readonly
                   placeholder="{{ $placeholder }}">
        </div>

        <!-- Tooltip -->
        <div class="absolute -bottom-4 left-1/2 z-20 -translate-x-1/2 translate-y-2 scale-95 whitespace-nowrap rounded bg-neutral-950 px-2 py-1 text-center text-sm text-white opacity-0 transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 dark:bg-white dark:text-neutral-900"
             role="tooltip">
            {{ $tooltip }}
        </div>
    </div>

    <!-- Calendar Picker -->
    <div class="absolute left-0 top-12 z-50 mt-1 w-[300px] rounded-lg border bg-white p-4 shadow-lg"
         x-show="showPicker"
         @click.away="showPicker = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <!-- Calendar Header -->
        <div class="mb-4 flex items-center justify-between">
            <button class="text-gray-600 hover:text-gray-900"
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
            <div class="text-lg font-bold"
                 x-text="monthNames[month] + ' ' + year"></div>
            <button class="text-gray-600 hover:text-gray-900"
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
                <div class="py-1 text-center text-sm text-gray-600"
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
                <div class="cursor-pointer rounded-lg py-1 text-center hover:bg-blue-50"
                     @click="selectDate(date)"
                     x-text="date"
                     :class="{
                         'bg-blue-500 text-white hover:bg-blue-600': isSelected(date),
                         'text-gray-300 cursor-not-allowed hover:bg-white': !isSelectable(date)
                     }"></div>
            </template>
        </div>

        <!-- Selected date preview -->
        <div class="mt-4 text-sm text-gray-600"
             x-show="selectedDate">
            <div class="flex justify-between">
                <span>{{ __('labels.date') }}:</span>
                <span x-text="selectedDate"></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-4 flex justify-end space-x-2">
            <button class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800"
                    @click="clearDate()">{{ __('labels.clean') }}</button>
            <button class="rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600"
                    @click="applyDate()"> {{ __('labels.apply') }}</button>
        </div>
    </div>
</div>

<script>
    function singleDatePicker() {
        return {
            showPicker: false,
            selectedDate: '',
            month: '',
            year: '',
            daysInMonth: [],
            blankDays: [],
            monthNames: [this.$l('january'), this.$l('february'), this.$l('march'), this.$l('april'), this.$l('may'), this.$l('june'), this.$l('july'), this.$l('august'), this.$l('september'), this.$l('october'), this.$l('november'), this.$l(
                'december')],
            init() {
                const today = new Date();
                this.month = today.getMonth();
                this.year = today.getFullYear();
                this.getNoOfDays();


                if (this.$wire.$parent.get(this.$wire.variable)) {
                    this.$wire.date = this.$wire.$parent.get(this.$wire.variable)
                }

                Livewire.on('resetDatepicker', () => {
                    this.clearDate();
                    this.$wire.date = '';
                });

                Livewire.on('applyDate', (data) => {
                    if (this.$wire.variable == data[1]) {
                        this.$wire.date = data[0];
                        this.$wire.$parent.$set(this.$wire.variable, data[0])
                    }
                });


            },

            isSelected(date) {
                const currentDate = this.formatDate(new Date(this.year, this.month, date));
                return currentDate === this.selectedDate;
            },

            isSelectable(date) {
                const currentDate = new Date(this.year, this.month, date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return currentDate <= today; // solo fechas hasta hoy
            },

            selectDate(date) {
                if (!this.isSelectable(date)) return;
                this.selectedDate = this.formatDate(new Date(this.year, this.month, date));
            },

            getNoOfDays() {
                const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                const firstDay = new Date(this.year, this.month).getDay();
                this.blankDays = Array(firstDay).fill(null);
                this.daysInMonth = Array(daysInMonth).fill(null).map((_, i) => i + 1);
            },

            formatDate(date) {
                const day = date.getDate().toString().padStart(2, '0');
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
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

            clearDate() {
                this.selectedDate = '';
            },

            applyDate() {
                var variable = this.$wire.variable;
                this.dateRangeText = this.selectedDate;
                this.$wire.date = this.dateRangeText;
                this.$wire.$parent.$set(variable, this.dateRangeText)
                this.showPicker = false;
            }
        };
    }
</script>
