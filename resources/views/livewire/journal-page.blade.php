<div class="min-h-screen bg-gray-50 pb-5"
     x-data="journal()">

    {{-- CONTENEDOR PRINCIPAL CON ESTADO ALPINE --}}
    <div x-data="{
        initialLoad: true,
        init() {
            // Cuando Livewire termine de cargar sus scripts y efectos, quitamos el loader
            document.addEventListener('livewire:initialized', () => {
                this.initialLoad = false;
            });
    
            // Fallback de seguridad: por si Livewire ya cargó antes de este script
            setTimeout(() => { this.initialLoad = false }, 200);
        }
    }">

        {{-- 1. LOADER DE CARGA INICIAL (Pantalla completa al refrescar) --}}
        {{-- Se muestra mientras 'initialLoad' sea true. Tiene z-index máximo (z-50) --}}
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white"
             x-show="initialLoad"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Aquí tu componente loader --}}
            <div class="flex flex-col items-center">
                <x-loader />
            </div>
        </div>
    </div>

    {{-- ? Loading --}}
    <div wire:loading
         wire:target='updatedSelectedAccountId, insertAccount, updateAccount, deleteAccount'>
        <x-loader></x-loader>
    </div>

    {{-- ? Show Alerta --}}
    <x-modal-template show="showAlert">
    </x-modal-template>

    {{-- MODAL GESTIÓN DE REGLAS MAESTRAS --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm"
         x-data="{ show: @entangle('showRulesModal') }"
         x-show="show"
         style="display: none;">

        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
             @click.away="show = false">
            <h3 class="mb-4 text-lg font-bold text-gray-900">{{ __('labels.set_recurring_goals') }}</h3>
            <p class="mb-4 text-sm text-gray-500">{{ __('labels.explain_recurring_goals') }}</p>

            {{-- Lista de Reglas --}}
            <div class="mb-4 max-h-60 space-y-2 overflow-y-auto">
                @foreach ($userRules as $rule)
                    <div class="flex items-center justify-between rounded border border-gray-100 bg-gray-50 p-2">
                        <span class="{{ $rule->is_active ? 'text-gray-700' : 'text-gray-400 line-through' }} text-sm">{{ $rule->text }}</span>
                        <div class="flex gap-2">
                            <button class="{{ $rule->is_active ? 'text-emerald-500' : 'text-gray-400' }} text-xs"
                                    wire:click="toggleMasterRule({{ $rule->id }})">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                            <button class="text-xs text-rose-500"
                                    wire:click="deleteMasterRule({{ $rule->id }})">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Añadir Nueva --}}
            <div class="flex gap-2">
                <input class="flex-grow rounded-lg border-gray-300 text-sm focus:ring-indigo-500"
                       type="text"
                       wire:model="newRuleText"
                       placeholder="{{ __('labels.placeholder_new_rule') }}"
                       wire:keydown.enter="addMasterRule">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700"
                        wire:click="addMasterRule">
                    {{ __('labels.add') }}
                </button>
            </div>

            <div class="mt-6 flex justify-end">
                <button class="text-sm font-bold text-gray-500 hover:text-gray-700"
                        @click="show = false">{{ __('labels.close_s') }}</button>
            </div>
        </div>
    </div>

    {{-- HEADER FIJO --}}
    {{-- HEADER FIJO --}}
    <div class="sticky top-0 z-30 border-b border-gray-200 bg-white px-4 py-3 shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between">

            {{-- BLOQUE NAVEGACIÓN + TÍTULO --}}
            <div class="flex items-center gap-4">

                {{-- Botón Día Anterior --}}
                <button class="group flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition-all hover:border-indigo-600 hover:bg-indigo-50 hover:text-indigo-600 active:scale-95"
                        wire:click="prevDay">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>

                {{-- Título --}}
                <h1 class="flex select-none items-center gap-2 text-xl font-bold capitalize text-gray-900">
                    <i class="fa-solid fa-book-journal-whills text-indigo-600"></i>
                    {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
                </h1>

                {{-- Botón Día Siguiente --}}
                <button class="group flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition-all hover:border-indigo-600 hover:bg-indigo-50 hover:text-indigo-600 active:scale-95"
                        wire:click="nextDay">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>


            {{-- BOTÓN GUARDAR (Tu código original) --}}
            <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2 text-sm font-bold text-white shadow-md transition-all hover:bg-indigo-700 disabled:opacity-50"
                    wire:click="save"
                    wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('labels.save') }}</span>
                <span wire:loading><i class="fa-solid fa-circle-notch fa-spin"></i></span>
            </button>
        </div>
    </div>


    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 py-6 lg:grid-cols-12">

        {{-- === COLUMNA IZQUIERDA (PREPARACIÓN) === --}}
        <div class="space-y-6 lg:col-span-4">



            {{-- 2. PRE-MARKET --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-2">
                    <i class="fa-solid fa-sun text-orange-400"></i>
                    <h3 class="text-xs font-bold uppercase text-gray-700">{{ __('labels.premarket_mood') }}</h3>
                </div>
                <div class="space-y-4 p-4">
                    <div class="grid grid-cols-4 gap-2">
                        @foreach (['calm' => '😌', 'anxious' => '😰', 'confident' => '😎', 'tired' => '😴'] as $key => $emoji)
                            <button class="{{ $pre_market_mood === $key ? 'bg-indigo-50 border-indigo-400 scale-110' : 'border-gray-100 hover:bg-gray-50' }} flex h-10 items-center justify-center rounded border text-xl transition-all"
                                    wire:click="$set('pre_market_mood', '{{ $key }}')">
                                {{ $emoji }}
                            </button>
                        @endforeach
                    </div>
                    <textarea class="w-full rounded border-gray-200 bg-gray-50 p-2 text-xs"
                              wire:model.defer="pre_market_notes"
                              rows="2"
                              placeholder="{{ __('labels.previous_notes') }}"></textarea>
                </div>
            </div>

            {{-- 3. OBJETIVOS --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                    <h3 class="text-xs font-bold uppercase text-gray-700">
                        <i class="fa-solid fa-check-double mr-1 text-emerald-500"></i> {{ __('labels.objectives') }}
                    </h3>
                    <div class="flex gap-2">
                        {{-- Botón Configurar Plantilla --}}
                        <button class="text-gray-400 hover:text-indigo-600"
                                wire:click="openRulesManager"
                                title="{{ __('labels.edit_global_template') }}">
                            <i class="fa-solid fa-cog text-xs"></i>
                        </button>
                        {{-- Botón Añadir Manual (para excepciones del día) --}}
                        <button class="text-xs font-bold text-indigo-600 hover:underline"
                                wire:click="addObjective">{{ __('labels.add') }}</button>
                    </div>
                </div>
                <div class="space-y-2 p-4">
                    @foreach ($daily_objectives as $index => $obj)
                        <div class="flex items-center gap-2">
                            <input class="h-4 w-4 rounded border-gray-300 text-emerald-500 focus:ring-0"
                                   type="checkbox"
                                   wire:model.defer="daily_objectives.{{ $index }}.done">
                            <input class="flex-grow border-b border-gray-100 bg-transparent p-1 text-xs focus:border-indigo-300 focus:ring-0"
                                   type="text"
                                   wire:model.defer="daily_objectives.{{ $index }}.text"
                                   placeholder="{{ __('labels.objective...') }}">
                            <button class="text-gray-300 hover:text-red-500"
                                    wire:click="removeObjective({{ $index }})"><i class="fa-solid fa-times text-xs"></i></button>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- 3. CALENDARIO ECONÓMICO (NUEVO) --}}
            <livewire:economic-calendar :date="$date"
                                        :wire:key="'eco-'.$date" />
            {{-- 1. MINI CALENDARIO (Navegación SPA) --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <button class="p-1 text-gray-400 hover:text-indigo-600"
                            wire:click="prevMonth"><i class="fa-solid fa-chevron-left"></i></button>
                    <span class="text-sm font-bold capitalize text-gray-800">{{ $calendarRef->translatedFormat('F Y') }}</span>
                    <button class="p-1 text-gray-400 hover:text-indigo-600"
                            wire:click="nextMonth"><i class="fa-solid fa-chevron-right"></i></button>
                </div>

                {{-- Días Semana --}}
                <div class="mb-2 grid grid-cols-7 text-center text-[10px] font-bold text-gray-400">
                    <span>{{ __('labels.m') }}</span><span>{{ __('labels.t') }}</span><span>{{ __('labels.w') }}</span><span>{{ __('labels.tu') }}</span><span>{{ __('labels.f') }}</span><span>{{ __('labels.s') }}</span><span>{{ __('labels.su') }}</span>
                </div>

                {{-- Grid --}}
                <div class="grid grid-cols-7 gap-1">
                    @foreach ($this->miniCalendar as $day)
                        <button class="{{ $day['is_selected'] ? 'bg-indigo-600 text-white font-bold shadow-md' : ($day['is_today'] ? 'border border-indigo-500 text-indigo-600 bg-indigo-50' : ($day['is_current_month'] ? 'text-gray-600 hover:bg-gray-100' : 'text-gray-300')) }} relative h-8 rounded-lg text-xs font-medium transition-all"
                                wire:click="selectDate('{{ $day['date'] }}')">
                            {{ $day['day'] }}
                            @if ($day['has_entry'] && !$day['is_selected'])
                                <span class="absolute bottom-1 right-2.5 h-1 w-1 rounded-full bg-emerald-500"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- === COLUMNA DERECHA (RESULTADOS & REFLEXIÓN) === --}}
        <div class="space-y-6 lg:col-span-8">

            {{-- 1. KPIs SUPERIORES (Ahora con 4 columnas) --}}
            <div class="grid grid-cols-4 gap-4">
                {{-- PnL --}}
                <div class="rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm">
                    <p class="text-[10px] font-bold uppercase text-gray-500">{{ __('labels.pnl_day') }}</p>
                    <p class="{{ $dayPnL >= 0 ? 'text-emerald-500' : 'text-rose-500' }} text-xl font-black">
                        {{ $dayPnL >= 0 ? '+' : '' }}{{ number_format($dayPnL, 2) }}$
                    </p>
                </div>

                {{-- Trades --}}
                <div class="rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm">
                    <p class="text-[10px] font-bold uppercase text-gray-500">{{ __('labels.trades') }}</p>
                    <p class="text-xl font-black text-gray-800">{{ count($dayTrades) }}</p>
                </div>

                {{-- Errores (NUEVO) --}}
                @php
                    $totalErrors = collect($mistakesSummary)->sum();
                @endphp
                <div class="rounded-xl border border-rose-100 bg-white p-3 text-center shadow-sm">
                    <p class="text-[10px] font-bold uppercase text-rose-400">{{ __('labels.errors') }}</p>
                    <p class="{{ $totalErrors > 0 ? 'text-rose-600' : 'text-gray-400' }} text-xl font-black">
                        {{ $totalErrors }}
                    </p>
                </div>

                {{-- Disciplina --}}
                <div class="rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm">
                    <p class="text-[10px] font-bold uppercase text-gray-500">{{ __('labels.discipline') }}</p>
                    @php $score = number_format($entry->discipline_score, 1) ?? 0; @endphp
                    <span class="{{ $score >= 8 ? 'text-emerald-500' : ($score >= 5 ? 'text-amber-500' : 'text-rose-500') }} text-xl font-black">
                        {{ $score }}
                    </span>
                </div>
            </div>

            {{-- 2. LISTA DE OPERACIONES (Con Toggle y Scroll) --}}
            <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                 x-data="{ showList: false }"> {{-- ESTADO ALPINE --}}

                {{-- Cabecera con Toggle --}}
                <div class="flex cursor-pointer items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-3 transition-colors hover:bg-gray-100"
                     @click="showList = !showList">

                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-list-ul text-gray-400"></i>
                        <span class="text-xs font-bold uppercase text-gray-700">{{ __('labels.breakdown_operations') }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-medium text-gray-400">
                            {{ count($dayTrades) }} {{ __('labels.registers') }}
                        </span>
                        {{-- Flecha que rota --}}
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                           :class="showList ? 'rotate-180' : ''"></i>
                    </div>
                </div>

                {{-- Cuerpo Colapsable --}}
                <div class="max-h-[400px] overflow-y-auto transition-all"
                     x-show="showList"
                     x-cloak
                     x-collapse
                     {{-- Animación suave si tienes el plugin Alpine Collapse, si no, funciona igual --}}>

                    @if (count($dayTrades) > 0)
                        <div class="divide-y divide-gray-100">
                            @foreach ($dayTrades as $trade)
                                {{-- FILA DEL TRADE --}}
                                <div class="group relative flex cursor-pointer items-center justify-between px-4 py-3 transition-colors hover:bg-gray-50"
                                     wire:click="$dispatch('open-trade-detail', { tradeId: {{ $trade->id }} })">

                                    {{-- IZQUIERDA: Info Trade + Errores --}}
                                    <div class="flex items-center gap-4">
                                        {{-- Hora --}}
                                        <span class="w-10 font-mono text-xs text-gray-400">
                                            {{ \Carbon\Carbon::parse($trade->exit_time)->format('H:i') }}
                                        </span>

                                        <div class="flex flex-col gap-1">
                                            {{-- Simbolo y Dirección --}}
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-bold text-gray-800">
                                                    {{ $trade->tradeAsset->name ?? __('labels.unknown') }}
                                                </span>
                                                <span class="{{ $trade->direction == 'long' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded px-1.5 text-[9px] font-black uppercase">
                                                    {{ $trade->direction }}
                                                </span>
                                            </div>

                                            {{-- ETIQUETAS DE ERRORES --}}
                                            @if ($trade->mistakes->count() > 0)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach ($trade->mistakes as $mistake)
                                                        <span class="inline-flex items-center rounded border border-rose-100 bg-rose-50 px-1.5 py-0.5 text-[9px] font-bold text-rose-600">
                                                            <i class="fa-solid fa-bug mr-1 opacity-50"></i> {{ $mistake->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- DERECHA: PnL --}}
                                    <div class="text-right">
                                        <span class="{{ $trade->pnl >= 0 ? 'text-emerald-500' : 'text-rose-500' }} block text-sm font-black">
                                            {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }}$
                                        </span>
                                        <span class="absolute bottom-1 right-4 text-[10px] font-bold text-indigo-500 opacity-0 transition-opacity group-hover:opacity-100">
                                            {{ __('labels.saw') }} <i class="fa-solid fa-arrow-right ml-0.5"></i>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-32 flex-col items-center justify-center text-center text-gray-400">
                            <i class="fa-solid fa-box-open mb-2 text-2xl opacity-20"></i>
                            <p class="text-xs italic">{{ __('labels.no_activity_for_today') }}</p>
                        </div>
                    @endif
                </div>
            </div>



            {{-- 3. EDITOR PRINCIPAL (Trix) --}}
            <div class="flex min-h-[500px] flex-col rounded-xl border border-gray-200 bg-white shadow-sm"
                 wire:ignore> {{-- wire:ignore para que Livewire no toque el editor --}}

                {{-- Cabecera Editor --}}
                <div class="flex items-center justify-between gap-2 rounded-t-xl border-b border-gray-100 bg-gray-50 px-4 py-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-pen-nib text-indigo-500"></i>
                        <span class="text-xs font-bold uppercase text-gray-700">{{ __('labels.session_log') }}</span>
                    </div>

                    <button class="group flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-1 text-[10px] font-bold text-indigo-600 shadow-sm transition-all hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-50"
                            wire:click="generateAiDraft"
                            wire:loading.attr="disabled">
                        <span class="flex items-center gap-1"
                              wire:loading.remove
                              wire:target="generateAiDraft">
                            <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i> {{ __('labels.auto_write') }}
                        </span>
                        <span class="flex items-center gap-1"
                              wire:loading
                              wire:target="generateAiDraft">
                            <i class="fa-solid fa-circle-notch fa-spin text-indigo-500"></i> {{ __('labels.writting') }}
                        </span>
                    </button>
                </div>

                {{-- Cuerpo del Editor --}}
                <div class="relative flex-grow p-4"
                     x-data="{
                         value: @entangle('content').defer,
                     
                         init() {
                             // 1. Listeners básicos (Igual que antes)
                             this.$refs.trix.addEventListener('trix-change', (e) => this.value = e.target.value);
                     
                             Livewire.on('editor-content-updated', (data) => {
                                 let newContent = Array.isArray(data) ? data[0] : data;
                                 if (newContent) {
                                     this.$refs.trix.editor.loadHTML(newContent);
                                     this.value = newContent;
                                 }
                             });
                     
                             // 📸 2. LISTENER DE SUBIDA DE IMÁGENES (NUEVO)
                             addEventListener('trix-attachment-add', (e) => {
                                 if (e.attachment.file) {
                                     this.uploadFile(e.attachment);
                                 }
                             });
                         },
                     
                         // FUNCIÓN DE SUBIDA AJAX
                         uploadFile(attachment) {
                             // A. Preparar datos
                             let formData = new FormData();
                             formData.append('file', attachment.file);
                     
                             // B. Petición al Controlador Laravel
                             axios.post('{{ route('journal.upload') }}', formData, {
                                 headers: {
                                     'Content-Type': 'multipart/form-data',
                                     'X-CSRF-TOKEN': '{{ csrf_token() }}' // Seguridad Laravel
                                 },
                                 onUploadProgress: (progressEvent) => {
                                     // Barra de progreso nativa de Trix
                                     let progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                                     attachment.setUploadProgress(progress);
                                 }
                             }).then(response => {
                                 // C. Éxito: Sustituir el archivo local por la URL del servidor
                                 attachment.setAttributes({
                                     url: response.data.url,
                                     href: response.data.url // Para que sea clicable
                                 });
                             }).catch(error => {
                                 console.error('Upload error:', error);
                                 attachment.setAttributes({
                                     error: 'Falló la subida.'
                                 });
                             });
                         }
                     }">

                    <input id="x"
                           type="hidden"
                           x-model="value">
                    <trix-editor class="trix-content h-full min-h-[400px] border-none focus:outline-none"
                                 input="x"
                                 x-ref="trix"></trix-editor>

                </div>

                {{-- ESTILOS CSS PARA QUE NO SE VEA 'HORRIBLE' --}}
                {{-- Trix usa la clase .trix-content. Aquí restauramos los estilos que Tailwind borra --}}
                <style>
                    /* Ocultar la barra de herramientas fea de Trix si quieres un look más limpio,
                       o estilizarla. Aquí la dejo visible pero mejorada. */
                    trix-toolbar {
                        background: white;
                        border-bottom: 1px solid #f3f4f6;
                        padding: 0.5rem;
                        position: sticky;
                        top: 0;
                        z-index: 10;
                    }

                    trix-toolbar .trix-button {
                        background: white;
                        border: 1px solid #e5e7eb;
                        border-radius: 0.375rem;
                        margin-right: 0.25rem;
                    }

                    trix-toolbar .trix-button--active {
                        background: #e0e7ff;
                        /* Indigo-100 */
                        color: #4338ca;
                        /* Indigo-700 */
                    }

                    /* Área de texto */
                    trix-editor {
                        border: none !important;
                        /* Quitar borde por defecto */
                        box-shadow: none !important;
                        padding: 1rem !important;
                        font-size: 0.95rem;
                        color: #374151;
                        line-height: 1.7;
                        max-height: 600px;
                        overflow-y: auto;
                    }

                    /* Restaurar estilos básicos dentro del editor */
                    .trix-content h1 {
                        font-size: 1.5em;
                        font-weight: bold;
                        margin-bottom: 0.5em;
                    }

                    .trix-content strong {
                        font-weight: 700;
                        color: #111827;
                    }

                    .trix-content em {
                        font-style: italic;
                    }

                    .trix-content ul {
                        list-style-type: disc;
                        padding-left: 1.5em;
                        margin-bottom: 1em;
                    }

                    .trix-content ol {
                        list-style-type: decimal;
                        padding-left: 1.5em;
                        margin-bottom: 1em;
                    }

                    .trix-content li {
                        margin-bottom: 0.25em;
                    }

                    .trix-content blockquote {
                        border-left: 3px solid #cbd5e1;
                        padding-left: 1em;
                        color: #64748b;
                        font-style: italic;
                    }
                </style>
            </div>

        </div>

    </div>
</div>
