{{-- Idioma, huso horario y resumen semanal por correo. --}}
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
     x-data
     x-init="
        @if ($this->needsTimezoneDetection())
            $nextTick(() => {
                const detected = Intl.DateTimeFormat().resolvedOptions().timeZone;
                if (detected) { $wire.set('timezone', detected); }
            });
        @endif
     ">

    <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <h4 class="flex items-center gap-2 font-bold text-gray-900 dark:text-gray-100">
            <i class="fa-solid fa-envelope-open-text text-indigo-500"></i>
            {{ __('weekly.settings.title') }}
        </h4>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('weekly.settings.lead') }}</p>
    </div>

    <div class="space-y-5 p-6">

        {{-- Resumen semanal --}}
        <label class="flex cursor-pointer items-start gap-3">
            <input class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900"
                   type="checkbox"
                   wire:model="weeklySummary">
            <span>
                <span class="block text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('weekly.settings.toggle') }}</span>
                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                    {{ __('weekly.settings.toggle_hint', ['hour' => sprintf('%02d:00', (int) config('retention.weekly_summary.hour'))]) }}
                </span>
            </span>
        </label>

        <div class="grid gap-4 sm:grid-cols-2">

            {{-- Huso horario --}}
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                       for="pref-timezone">{{ __('weekly.settings.timezone') }}</label>

                <select class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        id="pref-timezone"
                        wire:model="timezone">
                    @foreach ($this->timezones() as $region => $zones)
                        <optgroup label="{{ $region }}">
                            @foreach ($zones as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <x-input-error class="mt-1" for="timezone" />
            </div>

            {{-- Idioma --}}
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                       for="pref-locale">{{ __('weekly.settings.locale') }}</label>

                <select class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        id="pref-locale"
                        wire:model="locale">
                    @foreach (config('app.supported_locales') as $code)
                        <option value="{{ $code }}">{{ __('weekly.settings.locales.' . $code) }}</option>
                    @endforeach
                </select>

                <x-input-error class="mt-1" for="locale" />
            </div>
        </div>

        <div class="flex justify-end">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                    type="button"
                    wire:click="save"
                    wire:loading.attr="disabled">{{ __('weekly.settings.save') }}</button>
        </div>
    </div>
</div>
