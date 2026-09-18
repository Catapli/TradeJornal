@props(['align' => 'right'])

{{--
    Selector de idioma. Despacha `change_lang`, que recoge el componente Livewire
    <livewire:language-manager /> — montado tanto en layouts/app como en layouts/guest.

    Extraído del navigation-menu para poder usarlo también en las pantallas de auth,
    donde antes no había forma de cambiar de idioma (el LanguageManager estaba montado
    pero sin disparador visible).
--}}
<div class="relative"
     x-data="{
         open: false,
         current: '{{ app()->getLocale() }}',
         languages: {
             'es': { name: 'Español', code: 'es' },
             'en': { name: 'English', code: 'gb' },
         },
         select(lang) {
             this.current = lang;
             this.open = false;
             $dispatch('change_lang', { locale: lang });
         }
     }"
     @click.outside="open = false">

    <!-- BOTÓN TRIGGER -->
    <button class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm transition-all hover:bg-gray-50 dark:hover:bg-gray-900 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            @click="open = !open"
            type="button"
            :aria-expanded="open"
            aria-label="{{ __('labels.auth_change_language') }}">
        <img class="h-3 w-4 rounded-[1px] object-cover shadow-sm"
             :src="`https://flagcdn.com/24x18/${languages[current].code}.png`"
             alt="flag">
        <span class="hidden md:inline"
              x-text="languages[current].name"></span>
        <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 dark:text-gray-500 transition-transform duration-200"
           :class="open ? 'rotate-180' : ''"></i>
    </button>

    <!-- LISTA DESPLEGABLE -->
    <div class="absolute {{ $align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right' }} z-50 mt-2 w-40 rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 py-1 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none"
         x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">

        <template x-for="(lang, key) in languages"
                  :key="key">
            <button class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-900 hover:text-indigo-600"
                    type="button"
                    @click="select(key)"
                    :class="current === key ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 font-semibold' : ''">
                <img class="h-3 w-4 rounded-[1px] shadow-sm"
                     :src="`https://flagcdn.com/24x18/${lang.code}.png`"
                     alt="">
                <span x-text="lang.name"></span>
            </button>
        </template>
    </div>
</div>
