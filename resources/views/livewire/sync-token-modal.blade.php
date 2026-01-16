<div>
    {{-- Botón principal en AccountPage --}}
    <button class="btn btn-emerald rounded-xl px-4 py-2 shadow-lg"
            wire:click="$toggle('showModal')">
        <svg class="mr-2 h-4 w-4">...</svg>
        Agente Automático
    </button>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             wire:click="$toggle('showModal')">
            <div class="border-emerald/20 mx-4 max-w-md rounded-3xl border bg-white/90 p-8 shadow-2xl backdrop-blur-xl"
                 wire:click.stop>
                <h3 class="mb-6 text-center text-2xl font-bold text-slate-900">
                    🚀 TradeForge Agent
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Sync Token
                        </label>
                        <div class="relative">
                            <input class="w-full rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-3 font-mono text-sm transition-all focus:border-emerald-400"
                                   type="text"
                                   readonly
                                   value="{{ $syncToken }}">
                            <button class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl bg-emerald-500 px-4 py-1 text-sm font-semibold text-white transition-all hover:bg-emerald-600"
                                    wire:click="copyToClipboard">
                                📋 Copiar
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            Pega en TradeForgeAgent.exe → Automático forever
                        </p>
                    </div>

                    <button class="w-full rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-3 font-bold text-white shadow-xl transition-all hover:from-amber-600 hover:to-orange-600"
                            wire:click="generateToken">
                        🔄 Nuevo Token (Refrescar)
                    </button>
                </div>

                <div class="mt-8 border-t border-slate-200 pt-6 text-center">
                    <p class="text-xs text-slate-500">
                        Válido hasta {{ \Carbon\Carbon::parse(auth()->user()->sync_token_expires_at)->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
{{-- 
@push('scripts')
    <script>
        document.addEventListener('livewire:navigated', () => {
            navigator.clipboard.writeText('{{ $syncToken }}');
        });
    </script>
@endpush --}}
