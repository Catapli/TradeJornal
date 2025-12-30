<div class="relative"
     x-data="timePicker()">
    <!-- Input field -->
    <div class="relative justify-items-center px-2 py-1">
        <div class="peer flex w-full rounded-lg shadow-sm">
            <div class="inline-flex min-w-fit items-center rounded-s-md border border-e-0 border-gray-200 bg-gray-50 px-4 dark:border-neutral-600 dark:bg-neutral-700">
                <span class="text-lg text-gray-500 dark:text-neutral-400">{!! $icono !!}</span>
            </div>
            <input class="block w-full rounded-e-lg border-gray-200 px-4 py-3 pe-11 text-sm shadow-inner focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:pointer-events-none disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                   type="text"
                   x-model="$wire.time"
                   @click="showPicker = true"
                   readonly
                   placeholder="{{ $placeholder }}">
        </div>

        <!-- Tooltip -->
        <div class="absolute -bottom-4 left-1/2 z-20 -translate-x-1/2 translate-y-2 scale-95 whitespace-nowrap rounded bg-neutral-950 px-2 py-1 text-center text-sm text-white opacity-0 transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 dark:bg-white dark:text-neutral-950"
             role="tooltip">
            {{ $tooltip }}
        </div>
    </div>

    <!-- Time Picker Popup -->
    <div class="absolute left-0 top-12 z-50 mt-1 w-[300px] rounded-lg border bg-white p-4 shadow-lg dark:bg-neutral-800"
         x-show="showPicker"
         @click.away="showPicker = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Clock Visual -->
        <div class="mb-4 flex justify-center">
            <div class="relative h-40 w-40 rounded-full border-4 border-gray-300 dark:border-neutral-600">
                <!-- Hour hand -->
                <div class="absolute left-1/2 top-1/2 h-1 w-1/2 origin-left -translate-x-1/2 -translate-y-1/2 rounded bg-blue-600"
                     :style="`transform: translate(-50%, -50%) rotate(${(hours || 0) * 30 + (minutes || 0) * 0.5}deg)`"></div>
                <!-- Minute hand -->
                <div class="absolute left-1/2 top-1/2 h-0.5 w-3/4 origin-left -translate-x-1/2 -translate-y-1/2 rounded bg-gray-700 dark:bg-gray-300"
                     :style="`transform: translate(-50%, -50%) rotate(${(minutes || 0) * 6}deg)`"></div>
                <!-- Center dot -->
                <div class="absolute left-1/2 top-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gray-800 dark:bg-gray-200"></div>

                <!-- Hour markers -->
                <template x-for="h in 12"
                          :key="h">
                    <div class="absolute left-1/2 top-1/2 h-1 w-2 origin-center -translate-x-1/2 -translate-y-1/2"
                         :style="`transform: translate(-50%, -50%) rotate(${h * 30}deg) translateY(-85px)`">
                        <span class="block text-xs font-bold text-gray-600 dark:text-gray-300"
                              x-text="h === 0 ? 12 : h"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Selectors (fallback) -->
        <div class="mb-4 grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hora</label>
                <select class="w-full rounded border border-gray-300 px-3 py-2 dark:bg-neutral-700 dark:text-white"
                        x-model="hours">
                    <template x-for="h in Array.from({length: 24}, (_, i) => i)"
                              :key="h">
                        <option :value="h"
                                x-text="h < 10 ? '0'+h : h"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minutos</label>
                <select class="w-full rounded border border-gray-300 px-3 py-2 dark:bg-neutral-700 dark:text-white"
                        x-model="minutes">
                    <template x-for="m in Array.from({length: 60}, (_, i) => i)"
                              :key="m">
                        <option :value="m"
                                x-text="m < 10 ? '0'+m : m"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-2">
            <button class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                    type="button"
                    @click="clearTime()">Limpiar</button>
            <button class="rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600"
                    type="button"
                    @click="applyTime()">Aplicar</button>
        </div>
    </div>
</div>

<script>
    function timePicker() {
        return {
            showPicker: false,
            hours: '',
            minutes: '',
            init() {
                const now = new Date();
                this.hours = now.getHours();
                this.minutes = now.getMinutes();
            },
            clearTime() {
                this.hours = '';
                this.minutes = '';
            },
            applyTime() {
                if (this.hours === '' || this.minutes === '') {
                    this.$wire.time = '';
                } else {
                    const h = String(this.hours).padStart(2, '0');
                    const m = String(this.minutes).padStart(2, '0');
                    this.$wire.time = `${h}:${m}`;
                }
                this.showPicker = false;
            }
        }
    }
</script>
