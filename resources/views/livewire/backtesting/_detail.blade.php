@if ($selectedStrategy)

    {{-- HEADER --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <button class="flex items-center gap-1.5 text-sm text-gray-500 transition-colors hover:text-gray-900"
                    wire:click="backToList()">
                <svg class="h-4 w-4"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver
            </button>
            <div class="h-4 w-px bg-gray-200"></div>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $selectedStrategy->name }}</h1>
                <p class="text-xs text-gray-400">{{ $selectedStrategy->symbol }} · {{ $selectedStrategy->timeframe }} · {{ $selectedStrategy->currency }}</p>
            </div>
        </div>

        {{-- Botón solo visible en tab trade log --}}
        <button class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-500"
                x-show="activeTab === 'log'"
                @click="openTradePanel()">
            <svg class="h-4 w-4"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4" />
            </svg>
            Añadir Trade
        </button>
    </div>

    {{-- TABS --}}
    <div class="mb-6 flex items-center gap-1 border-b border-gray-200">
        <button class="-mb-px flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors"
                @click="activeTab = 'log'"
                :class="activeTab === 'log'
                    ?
                    'border-b-2 border-blue-600 text-blue-600' :
                    'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'">
            <svg class="h-4 w-4"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
            </svg>
            Trade Log
            @if ($trades->count() > 0)
                <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-xs tabular-nums text-gray-500">
                    {{ $trades->count() }}
                </span>
            @endif
        </button>
        <button class="-mb-px flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors"
                @click="setActiveTab('analytics')"
                :class="activeTab === 'analytics'
                    ?
                    'border-b-2 border-blue-600 text-blue-600' :
                    'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'">
            <svg class="h-4 w-4"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            Analytics
        </button>
        <button class="-mb-px flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors"
                @click="activeTab = 'rules'"
                :class="activeTab === 'rules'
                    ?
                    'border-b-2 border-blue-600 text-blue-600' :
                    'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'">
            <svg class="h-4 w-4"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Reglas
        </button>
    </div>

    {{-- CONTENIDO TABS --}}
    <div x-show="activeTab === 'log'">
        @include('livewire.backtesting._trade-log')
    </div>

    <div x-show="activeTab === 'analytics'"
         style="display:none">
        @include('livewire.backtesting._analytics')
    </div>

    <div x-show="activeTab === 'rules'"
         style="display:none">
        @include('livewire.backtesting._rules')
    </div>

@endif
