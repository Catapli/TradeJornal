<div @class([
    'rounded-xl border border-rose-100 bg-white shadow-sm transition-all duration-300 dark:border-rose-500/20 dark:bg-gray-900/30',
    'mt-4' => !$compact,
])
     x-data="{ open: @js($compact) }"> {{-- Estado Alpine para abrir/cerrar --}}

    {{-- CABECERA. En compacto no se pliega: se ha entrado justo a esto. --}}
    <div @class([
        'flex items-center justify-between bg-rose-50/50 dark:bg-rose-500/10',
        'cursor-pointer px-4 py-3' => !$compact,
        'px-4 py-2' => $compact,
    ])
         @if (!$compact) @click="open = !open" @endif>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <h4 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-rose-800 dark:text-rose-300">
                <i class="fa-solid fa-bug"></i> {{ __('labels.errors_audit') }}
            </h4>

            {{-- Badge de aviso IA (Solo si hay sugerencias) --}}
            @if (count($suggestions) > 0)
                @if ($compact)
                    {{-- Compacto: las sugerencias van aquí mismo, en una línea, en
                         vez de en una caja que se come cien píxeles de alto. --}}
                    @foreach ($suggestions as $suggestion)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300"
                              title="{{ $suggestion['reason'] }}">
                            <i class="fa-solid fa-lightbulb"></i>{{ $suggestion['name'] }}
                        </span>
                    @endforeach
                @else
                    <span class="inline-flex animate-pulse items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                        <i class="fa-solid fa-lightbulb"></i> {{ count($suggestions) }} {{ __('labels.suggestions') }}
                    </span>
                @endif
            @endif
        </div>

        {{-- Flecha Toggle --}}
        @unless ($compact)
            <button class="text-rose-400 transition-transform duration-200 hover:text-rose-600"
                    :class="open ? 'rotate-180' : ''">
                <i class="fa-solid fa-chevron-down"></i>
            </button>
        @endunless
    </div>

    {{-- CUERPO (Colapsable salvo en compacto) --}}
    <div @class(['px-4 pb-4 pt-2' => !$compact, 'px-4 pb-3 pt-2' => $compact])
         @unless ($compact) x-show="open" x-collapse @endunless>

        {{-- ⚠️ INFORME DEL FISCAL (Texto más grande y legible) --}}
        @if (!$compact && count($suggestions) > 0)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="mb-2 flex items-center gap-2 text-xs font-bold uppercase text-amber-800 dark:text-amber-300">
                    <i class="fa-solid fa-user-secret text-sm"></i> {{ __('labels.analysis_detected_patterns') }}
                </p>
                <ul class="space-y-1.5">
                    @foreach ($suggestions as $suggestion)
                        <li class="flex items-start gap-2 text-xs text-amber-900 dark:text-amber-200">
                            <i class="fa-solid fa-arrow-right mt-0.5 text-amber-600"></i>
                            <span>
                                {{ __('labels.posible') }} <strong class="font-bold">{{ $suggestion['name'] }}</strong>:
                                <span class="font-medium text-amber-800/80 dark:text-amber-200/80">{{ $suggestion['reason'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- NUBE DE TAGS --}}
        <div @class(['flex flex-wrap', 'gap-2' => !$compact, 'gap-1.5' => $compact])>
            @foreach ($availableMistakes as $mistake)
                @php
                    $isActive = in_array($mistake->id, $selectedMistakes);
                    $isSuggested = collect($suggestions)->contains('slug', $mistake->slug) && $mistake->slug;

                    // Estilos Base
                    $class = 'group relative flex select-none items-center rounded-lg border font-bold transition-all '
                        . ($compact ? 'text-[11px] ' : 'text-xs ');

                    if ($isActive) {
                        // 1. ACTIVO (Marcado por usuario): Rojo Sólido
                        $class .= 'bg-rose-600 border-rose-600 text-white shadow-md';
                    } elseif ($isSuggested) {
                        // 2. SUGERIDO (Fiscal): Ámbar muy visible (Borde grueso, fondo suave)
                        $class .= 'bg-amber-50 border-amber-400 text-amber-800 shadow-sm ring-1 ring-amber-400 hover:bg-amber-100';
                    } else {
                        // 3. NORMAL: Blanco / Gris
                        $class .= 'bg-white border-gray-200 text-gray-500 hover:border-rose-300 hover:text-rose-500 hover:shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:border-rose-500/50 dark:hover:text-rose-400';
                    }
                @endphp

                <div class="{{ $class }}"
                     wire:key="mistake-tag-{{ $mistake->id }}">

                    <button @class([
                                'flex cursor-pointer items-center gap-2',
                                'py-1.5 pl-3' => !$compact,
                                'py-1 pl-2.5' => $compact,
                                'pr-1.5' => $mistake->isCustom(),
                                'pr-3' => !$mistake->isCustom() && !$compact,
                                'pr-2.5' => !$mistake->isCustom() && $compact,
                            ])
                            title="{{ $mistake->display_description ?: __('labels.no_description') }}"
                            wire:click="toggleMistake({{ $mistake->id }})">

                        {{-- Punto de color (hex real, evita clases dinámicas de Tailwind) --}}
                        @unless ($isActive)
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full"
                                  style="background-color: {{ $mistake->color_hex }}"></span>
                        @endunless

                        <span>{{ $mistake->display_name }}</span>

                        {{-- Iconos de estado --}}
                        @if ($isActive)
                            <i class="fa-solid fa-check text-[10px]"></i>
                        @elseif($isSuggested)
                            <i class="fa-solid fa-exclamation-circle animate-bounce text-amber-600"></i>
                        @endif
                    </button>

                    {{-- Sólo los errores propios se editan --}}
                    @if ($mistake->isCustom())
                        <button class="cursor-pointer px-2 py-1.5 opacity-40 transition hover:opacity-100"
                                title="{{ __('labels.edit_mistake') }}"
                                wire:click="openEditForm({{ $mistake->id }})">
                            <i class="fa-solid fa-pen text-[10px]"></i>
                        </button>
                    @endif
                </div>
            @endforeach

            {{-- CREAR ERROR PROPIO --}}
            <button class="flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-gray-300 px-3 py-1.5 text-xs font-bold text-gray-400 transition-all hover:border-rose-400 hover:text-rose-500 dark:border-gray-600 dark:text-gray-400 dark:hover:border-rose-500/50 dark:hover:text-rose-400"
                    wire:click="openCreateForm">
                <i class="fa-solid fa-plus text-[10px]"></i> {{ __('labels.new_mistake') }}
            </button>
        </div>

        @unless ($compact)
            <p class="mt-2 text-[10px] text-gray-400 dark:text-gray-500">
                <i class="fa-regular fa-circle-question"></i> {{ __('labels.mistake_hint') }}
            </p>
        @endunless

        {{-- FORMULARIO DE ERROR PERSONALIZADO --}}
        @if ($showForm)
            <div class="mt-4 space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/50">

                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                    <i class="fa-solid {{ $editingId ? 'fa-pen' : 'fa-plus' }}"></i>
                    {{ $editingId ? __('labels.edit_mistake') : __('labels.new_mistake') }}
                </p>

                {{-- Nombre --}}
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-500 dark:text-gray-400">
                        {{ __('labels.mistake_name') }}
                    </label>
                    <input type="text"
                           class="w-full rounded-lg border-gray-300 bg-white text-xs focus:border-rose-400 focus:ring-rose-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                           maxlength="60"
                           placeholder="{{ __('labels.mistake_name_placeholder') }}"
                           wire:model="formName">
                    @error('formName')
                        <p class="mt-1 text-[10px] font-medium text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-500 dark:text-gray-400">
                        {{ __('labels.mistake_description') }}
                    </label>
                    <textarea rows="2"
                              class="w-full rounded-lg border-gray-300 bg-white text-xs focus:border-rose-400 focus:ring-rose-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                              maxlength="500"
                              placeholder="{{ __('labels.mistake_description_placeholder') }}"
                              wire:model="formDescription"></textarea>
                    @error('formDescription')
                        <p class="mt-1 text-[10px] font-medium text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-4">
                    {{-- Gravedad --}}
                    <div>
                        <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-500 dark:text-gray-400">
                            {{ __('labels.mistake_severity') }}
                        </label>
                        <select class="rounded-lg border-gray-300 bg-white py-1.5 text-xs focus:border-rose-400 focus:ring-rose-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                wire:model="formWeight">
                            <option value="1">{{ __('labels.mistake_severity_1') }}</option>
                            <option value="2">{{ __('labels.mistake_severity_2') }}</option>
                            <option value="3">{{ __('labels.mistake_severity_3') }}</option>
                        </select>
                        @error('formWeight')
                            <p class="mt-1 text-[10px] font-medium text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Color --}}
                    <div>
                        <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-500 dark:text-gray-400">
                            {{ __('labels.color') }}
                        </label>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($palette as $colorName => $hex)
                                <button type="button"
                                        class="h-5 w-5 rounded-full transition {{ $formColor === $colorName ? 'ring-2 ring-gray-800 ring-offset-1 dark:ring-white dark:ring-offset-gray-800' : 'hover:scale-110' }}"
                                        style="background-color: {{ $hex }}"
                                        title="{{ $colorName }}"
                                        wire:click="$set('formColor', '{{ $colorName }}')"></button>
                            @endforeach
                        </div>
                        @error('formColor')
                            <p class="mt-1 text-[10px] font-medium text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                    <div>
                        @if ($editingId)
                            <button class="text-xs font-bold text-gray-400 transition hover:text-rose-600"
                                    wire:click="deleteMistake({{ $editingId }})"
                                    wire:confirm="{{ __('labels.mistake_delete_confirm') }}">
                                <i class="fa-solid fa-trash text-[10px]"></i> {{ __('labels.mistake_delete') }}
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <button class="rounded-lg px-3 py-1.5 text-xs font-bold text-gray-500 transition hover:text-gray-800 dark:hover:text-gray-200"
                                wire:click="cancelForm">
                            {{ __('labels.mistake_cancel') }}
                        </button>
                        <button class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-50"
                                wire:click="saveMistake"
                                wire:loading.attr="disabled">
                            {{ __('labels.mistake_save') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- FEEDBACK PIE --}}
        @if (count($selectedMistakes) > 0)
            <div class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-2 dark:border-gray-700">
                <div class="flex -space-x-1">
                    @foreach ($selectedMistakes as $id)
                        <div class="h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-gray-800"
                             wire:key="dot-{{ $id }}"></div>
                    @endforeach
                </div>
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500">
                    {{ count($selectedMistakes) }} {{ __('labels.confirmed_errors') }}
                </p>
            </div>
        @endif
    </div>
</div>
