<?php

namespace App\Livewire;

use App\Actions\Backtesting\CalculateStrategyMetrics;
use App\Models\BacktestStrategy;
use App\Models\BacktestTrade;
use App\Services\AiService;
use App\Services\StorageService;
use App\WithAiLimits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Backtesting')]
class BacktestingPage extends Component
{
    use WithFileUploads;
    use WithPagination;
    use WithAiLimits;

    protected StorageService $storage;

    public function boot(StorageService $storage): void
    {
        $this->storage = $storage;
    }

    // ── Estrategia form ─────────────────────────────────────────
    public string $name        = '';
    public string $symbol      = '';
    public string $timeframe   = 'H1';
    public string $direction   = 'both';
    public string $description = '';
    public array  $rules       = [];
    public string $newRule     = '';
    public ?int   $editingId   = null;

    public bool $showArchived = false;

    // ── Trade form ───────────────────────────────────────────────
    public string $trade_date     = '';
    public string $direction_t    = 'long';
    public string $entry_price    = '';
    public string $exit_price     = '';
    public string $stop_loss      = '';
    public string $session        = '';
    public int    $setup_rating   = 3;
    public bool   $followed_rules = true;
    public array  $confluences    = [];
    public string $newConfluence  = '';
    public string $notes          = '';
    public ?int   $editingTradeId = null;
    public $screenshot            = null;

    // ── Navegación ───────────────────────────────────────────────
    public ?int $selectedStrategyId = null;

    // ── Filtros log ──────────────────────────────────────────────
    public string $filterOutcome = '';
    public string $filterSession = '';
    public string $sortBy        = 'trade_date';
    public string $sortDir       = 'desc';

    // ── Analytics ────────────────────────────────────────────────
    public array $metrics       = [];
    public bool  $metricsLoaded = false;

    // ─────────────────────────────────────────────────────────────
    // RENDER
    // ─────────────────────────────────────────────────────────────

    public function render()
    {
        $strategies         = collect();
        $archivedStrategies = collect();
        $archivedCount      = 0;
        $selectedStrategy   = null;
        $trades             = collect();

        if ($this->selectedStrategyId) {
            // Vista detalle: el listado está oculto, no hace falta calcularlo
            $selectedStrategy = BacktestStrategy::where('user_id', Auth::id())
                ->findOrFail($this->selectedStrategyId);

            $trades = $selectedStrategy->trades()
                ->when($this->filterOutcome === 'win',  fn($q) => $q->where('pnl_r', '>', 0))
                ->when($this->filterOutcome === 'loss', fn($q) => $q->where('pnl_r', '<', 0))
                ->when($this->filterOutcome === 'be',   fn($q) => $q->whereBetween('pnl_r', [-0.01, 0.01]))
                ->when($this->filterSession,            fn($q) => $q->where('session', $this->filterSession))
                ->orderBy($this->sortBy, $this->sortDir)
                ->orderBy('id', $this->sortDir)
                ->paginate(25)
                ->through(function ($trade) {
                    $trade->screenshot_url = $trade->screenshot
                        ? $this->storage->temporaryUrl($trade->screenshot)
                        : null;
                    return $trade;
                });
        } else {
            $strategies = BacktestStrategy::where('user_id', Auth::id())
                ->where('status', 'active')
                ->withCount('trades')
                ->withCount(['trades as winning_trades_count' => fn($q) => $q->where('pnl_r', '>', 0)])
                ->withSum('trades', 'pnl_r')
                ->orderByDesc('updated_at')
                ->get();

            $archivedCount = BacktestStrategy::where('user_id', Auth::id())
                ->where('status', 'archived')
                ->count();

            if ($this->showArchived) {
                $archivedStrategies = BacktestStrategy::where('user_id', Auth::id())
                    ->where('status', 'archived')
                    ->withCount('trades')
                    ->orderByDesc('updated_at')
                    ->get();
            }
        }

        return view('livewire.backtesting-page', compact('strategies', 'archivedStrategies', 'archivedCount', 'selectedStrategy', 'trades'));
    }


    // Método nuevo
    public function unarchive(int $id): void
    {
        BacktestStrategy::where('user_id', Auth::id())
            ->findOrFail($id)
            ->update(['status' => 'active']);

        $this->dispatch('notify', type: 'success', message: 'Estrategia reactivada');
    }

    // Actualiza toggleArchived
    public function toggleArchived(): void
    {
        $this->showArchived = !$this->showArchived;
    }

    // ─────────────────────────────────────────────────────────────
    // ESTRATEGIA CRUD
    // ─────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetForm();
        $this->dispatch('strategy-ready');
    }

    public function openEdit(int $id): void
    {
        $strategy = BacktestStrategy::where('user_id', Auth::id())->findOrFail($id);

        $this->editingId   = $strategy->id;
        $this->name        = $strategy->name;
        $this->symbol      = $strategy->symbol;
        $this->timeframe   = $strategy->timeframe;
        $this->direction   = $strategy->direction;
        $this->description = $strategy->description ?? '';
        $this->rules       = $strategy->rules ?? [];

        $this->dispatch('strategy-ready');
    }

    public function save(): void
    {
        // Antes: validateOnly('name,symbol,...') — cadena inválida que no validaba nada
        $this->validate([
            'name'        => 'required|string|max:100',
            'symbol'      => 'required|string|max:20',
            'timeframe'   => 'required|string|max:10',
            'direction'   => 'required|in:long,short,both',
            'description' => 'nullable|string|max:500',
        ]);

        $data = [
            'user_id'     => Auth::id(),
            'name'        => $this->name,
            'symbol'      => strtoupper($this->symbol),
            'timeframe'   => $this->timeframe,
            'direction'   => $this->direction,
            'description' => $this->description ?: null,
            'rules'       => $this->rules,
        ];

        if ($this->editingId) {
            BacktestStrategy::where('user_id', Auth::id())
                ->findOrFail($this->editingId)
                ->update($data);
        } else {
            BacktestStrategy::create($data);
        }

        $this->dispatch('strategy-saved');
    }

    public function archive(int $id): void
    {
        BacktestStrategy::where('user_id', Auth::id())
            ->findOrFail($id)
            ->update(['status' => 'archived']);

        $this->dispatch('notify', type: 'success', message: 'Estrategia archivada');
    }

    public function addRule(): void
    {
        $rule = trim($this->newRule);
        if ($rule !== '') {
            $this->rules[] = $rule;
            $this->newRule = '';
        }
    }

    public function removeRule(int $index): void
    {
        array_splice($this->rules, $index, 1);
    }

    public function resetForm(): void
    {
        $this->editingId   = null;
        $this->name        = '';
        $this->symbol      = '';
        $this->timeframe   = 'H1';
        $this->direction   = 'both';
        $this->description = '';
        $this->rules       = [];
        $this->newRule     = '';
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────
    // NAVEGACIÓN
    // ─────────────────────────────────────────────────────────────

    public function selectStrategy(int $id): void
    {
        $this->selectedStrategyId = $id;
        $this->filterOutcome      = '';
        $this->filterSession      = '';
        $this->metricsLoaded      = false;
        $this->resetPage();
        $this->resetTradeForm();
        $this->dispatch('strategy-selected');
    }

    public function updatedFilterOutcome(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSession(): void
    {
        $this->resetPage();
    }

    public function backToList(): void
    {
        $this->selectedStrategyId = null;
        $this->filterOutcome      = '';
        $this->filterSession      = '';
        $this->sortBy             = 'trade_date';
        $this->sortDir            = 'desc';
    }

    // ─────────────────────────────────────────────────────────────
    // TRADE CRUD
    // ─────────────────────────────────────────────────────────────

    public function openTradePanel(?int $tradeId = null): void
    {
        if ($tradeId) {
            $trade = BacktestTrade::where('backtest_strategy_id', $this->selectedStrategyId)
                ->where('user_id', Auth::id())
                ->findOrFail($tradeId);

            $this->editingTradeId  = $tradeId;
            $this->trade_date      = $trade->trade_date->format('Y-m-d');
            $this->direction_t     = $trade->direction;
            $this->entry_price     = (string) $trade->entry_price;
            $this->exit_price      = (string) $trade->exit_price;
            $this->stop_loss       = (string) ($trade->stop_loss ?? '');
            $this->session         = $trade->session ?? '';
            $this->setup_rating    = $trade->setup_rating ?? 3;
            $this->followed_rules  = $trade->followed_rules;
            $this->confluences     = $trade->confluences ?? [];
            $this->notes           = $trade->notes ?? '';
            $this->reset('screenshot');

            $this->dispatch(
                'open-trade-panel',
                existingScreenshot: $trade->screenshot
                    ? $this->storage->temporaryUrl($trade->screenshot)
                    : null,
            );
        } else {
            $this->resetTradeForm();
            $this->dispatch('open-trade-panel', existingScreenshot: null);
        }
    }

    public function saveTrade(): void
    {
        $this->validate([
            'trade_date'     => 'required|date',
            'direction_t'    => 'required|in:long,short',
            'entry_price'    => 'required|numeric|min:0',
            'exit_price'     => 'required|numeric|min:0',
            'stop_loss'      => 'nullable|numeric|min:0',
            'session'        => 'nullable|in:london,new_york,asia,other',
            'setup_rating'   => 'nullable|integer|min:1|max:5',
            'followed_rules' => 'boolean',
            'confluences'    => 'nullable|array',
            'notes'          => 'nullable|string|max:1000',
            'screenshot'     => 'nullable|image|max:10240',
        ]);

        $pnlR = $this->calculateR();

        // ── Gestión de screenshot ────────────────────────────────
        $screenshotPath = null;

        if ($this->editingTradeId) {
            $existing = BacktestTrade::where('backtest_strategy_id', $this->selectedStrategyId)
                ->where('user_id', Auth::id())
                ->findOrFail($this->editingTradeId);

            if ($this->screenshot) {
                if ($existing->screenshot) {
                    $this->storage->delete($existing->screenshot);
                }
                $ext            = $this->screenshot->getClientOriginalExtension() ?: 'png';
                $path           = 'users/' . Auth::id() . '/backtesting/' . $this->selectedStrategyId . '/' . $this->editingTradeId . '.' . $ext;
                $this->storage->putFile($path, $this->screenshot->readStream());
                $screenshotPath = $path;
            } else {
                $screenshotPath = $existing->screenshot;
            }
        } else {
            if ($this->screenshot) {
                $ext            = $this->screenshot->getClientOriginalExtension() ?: 'png';
                $path           = 'users/' . Auth::id() . '/backtesting/' . $this->selectedStrategyId . '/' . now()->timestamp . '.' . $ext;
                $this->storage->putFile($path, $this->screenshot->readStream());
                $screenshotPath = $path;
            }
        }

        $data = [
            'backtest_strategy_id' => $this->selectedStrategyId,
            'user_id'              => Auth::id(),
            'trade_date'           => $this->trade_date,
            'direction'            => $this->direction_t,
            'entry_price'          => $this->entry_price,
            'exit_price'           => $this->exit_price,
            'stop_loss'            => $this->stop_loss ?: null,
            'pnl_r'                => $pnlR,
            'session'              => $this->session ?: null,
            'setup_rating'         => $this->setup_rating ?: null,
            'followed_rules'       => $this->followed_rules,
            'confluences'          => $this->confluences ?: null,
            'notes'                => $this->notes ?: null,
            'screenshot'           => $screenshotPath,
        ];

        if ($this->editingTradeId) {
            BacktestTrade::where('backtest_strategy_id', $this->selectedStrategyId)
                ->where('user_id', Auth::id())
                ->findOrFail($this->editingTradeId)
                ->update($data);
        } else {
            BacktestTrade::create($data);
        }

        $this->resetTradeForm();
        $this->metricsLoaded = false;
        Cache::forget($this->metricsCacheKey());
        $this->dispatch('close-trade-panel', saved: true);
    }

    public function deleteTrade(int $tradeId): void
    {
        $trade = BacktestTrade::where('backtest_strategy_id', $this->selectedStrategyId)
            ->where('user_id', Auth::id())
            ->findOrFail($tradeId);

        if ($trade->screenshot) {
            $this->storage->delete($trade->screenshot);
        }

        $trade->delete();

        $this->metricsLoaded = false;
        Cache::forget($this->metricsCacheKey());
        $this->dispatch('notify', type: 'success', message: 'Trade eliminado');
    }

    public function sortColumn(string $column): void
    {
        $this->sortDir = $this->sortBy === $column
            ? ($this->sortDir === 'asc' ? 'desc' : 'asc')
            : 'desc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    public function addConfluence(): void
    {
        $tag = trim($this->newConfluence);
        if ($tag !== '' && !in_array($tag, $this->confluences, true)) {
            $this->confluences[] = $tag;
            $this->newConfluence = '';
        }
    }

    public function removeConfluence(int $index): void
    {
        array_splice($this->confluences, $index, 1);
    }

    // ─────────────────────────────────────────────────────────────
    // ANALYTICS
    // ─────────────────────────────────────────────────────────────

    public function loadAnalytics(): void
    {
        if (!$this->selectedStrategyId) return;

        $full = $this->getFullMetrics();

        // El estado Livewire solo guarda lo que renderiza el blade (KPIs y tablas).
        // Las series por-trade (trades_list, calendar_data) pesan mucho y viajarían
        // ida y vuelta en CADA interacción: van una sola vez en el evento y viven en JS.
        $this->metrics = array_diff_key($full, array_flip(['trades_list', 'calendar_data']));
        $this->metricsLoaded = true;

        $this->dispatch('analytics-ready', metrics: $full);
    }

    private function getFullMetrics(): array
    {
        $strategy = BacktestStrategy::where('user_id', Auth::id())
            ->findOrFail($this->selectedStrategyId);

        return Cache::remember(
            $this->metricsCacheKey(),
            now()->addMinutes(10),
            fn () => app(CalculateStrategyMetrics::class)->execute($strategy)
        );
    }

    private function metricsCacheKey(): string
    {
        return 'bt:metrics:' . $this->selectedStrategyId;
    }

    /**
     * Analiza la estrategia con IA a partir de sus métricas.
     * Cacheado por estrategia (mismos datos no repiten llamada ni consumen crédito).
     */
    public function analyzeStrategyWithAi(AiService $ai): void
    {
        if (!$this->selectedStrategyId) return;

        if (!$ai->isConfigured()) {
            $this->dispatch('notify', type: 'error', message: __('labels.gemini_api_key_missing'));
            return;
        }

        if (!$this->checkAiLimit()) return;

        $strategy = BacktestStrategy::where('user_id', Auth::id())
            ->findOrFail($this->selectedStrategyId);

        $metrics = $this->getFullMetrics();

        if (($metrics['total_trades'] ?? 0) < 5) {
            $this->dispatch('notify', type: 'warning', message: __('labels.need_min_5_trades'));
            return;
        }

        $prompt = __('ai.backtest_prompt', ['context' => $this->buildAiContext($strategy, $metrics)]);

        $result = $ai->complete(
            $prompt,
            temperature: 0.4,
            maxTokens: 1024,
            cacheKey: 'backtest:' . $strategy->id,
        );

        if ($result->ok) {
            $strategy->update(['ai_analysis' => $result->content]);

            if (!$result->fromCache) {
                $this->consumeAiCredit();
            }

            $this->dispatch('notify', type: 'success', message: 'Análisis generado');
        } else {
            $this->dispatch('notify', type: 'error', message: $result->userMessage());
        }
    }

    private function buildAiContext(BacktestStrategy $strategy, array $m): string
    {
        $dd  = $m['max_drawdown'];
        $ri  = $m['rules_impact'];
        $eff = $m['trader_efficiency'];

        $lines = [
            "Estrategia: {$strategy->name} | {$strategy->symbol} {$strategy->timeframe} | Dirección: {$strategy->direction}",
            'Reglas del setup: ' . (empty($strategy->rules) ? 'sin definir' : implode(' / ', $strategy->rules)),
            "Trades: {$m['total_trades']} | Win rate: {$m['win_rate']}% | Profit factor: {$m['profit_factor']}",
            "Avg win: {$m['avg_win']}R | Avg loss: {$m['avg_loss']}R | Expectancy: {$m['expectancy']}R | PnL total: {$m['total_pnl']}R",
            "Max drawdown: {$dd['amount']}R ({$dd['percent']}%) | SQN: {$m['sqn']} | Rachas: {$m['max_consecutive_wins']}W / {$m['max_consecutive_losses']}L",
            'Con reglas seguidas: ' . json_encode($ri['followed']) . ' | Sin seguir reglas: ' . json_encode($ri['not_followed']),
            'Por sesión (labels/pnl/wr/counts): ' . json_encode($m['pnl_by_session']),
            'Por día de la semana: ' . json_encode($m['daily_winrate']),
            'Por calidad de setup (1-5): ' . json_encode($m['rating_impact']),
            'Top confluencias: ' . json_encode(array_slice($m['confluence_analysis'], 0, 5)),
            "Disciplina: {$eff['rules_followed_pct']}% reglas seguidas | {$eff['high_quality_pct']}% setups A+",
        ];

        return implode("\n", $lines);
    }

    // ─────────────────────────────────────────────────────────────
    // REGLAS DE ESTRATEGIA
    // ─────────────────────────────────────────────────────────────

    public function addRuleToStrategy(): void
    {
        $rule = trim($this->newRule);
        if ($rule === '' || !$this->selectedStrategyId) return;

        $strategy = BacktestStrategy::where('user_id', Auth::id())->findOrFail($this->selectedStrategyId);
        $rules    = $strategy->rules ?? [];
        $rules[]  = $rule;
        $strategy->update(['rules' => $rules]);

        $this->newRule = '';
        $this->dispatch('notify', type: 'success', message: 'Regla añadida');
    }

    public function updateRule(int $index, string $value): void
    {
        $value = trim($value);
        if ($value === '' || !$this->selectedStrategyId) return;

        $strategy      = BacktestStrategy::where('user_id', Auth::id())->findOrFail($this->selectedStrategyId);
        $rules         = $strategy->rules ?? [];
        $rules[$index] = $value;
        $strategy->update(['rules' => array_values($rules)]);
    }

    public function removeRuleFromStrategy(int $index): void
    {
        $strategy = BacktestStrategy::where('user_id', Auth::id())->findOrFail($this->selectedStrategyId);
        $rules    = $strategy->rules ?? [];
        array_splice($rules, $index, 1);
        $strategy->update(['rules' => array_values($rules)]);

        $this->dispatch('notify', type: 'success', message: 'Regla eliminada');
    }

    public function moveRule(int $index, string $direction): void
    {
        $strategy = BacktestStrategy::where('user_id', Auth::id())->findOrFail($this->selectedStrategyId);
        $rules    = $strategy->rules ?? [];

        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if (!isset($rules[$swap])) return;

        [$rules[$index], $rules[$swap]] = [$rules[$swap], $rules[$index]];
        $strategy->update(['rules' => array_values($rules)]);
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVADOS
    // ─────────────────────────────────────────────────────────────

    private function calculateR(): ?float
    {
        if (!$this->stop_loss || !$this->entry_price || !$this->exit_price) return null;

        $risk = abs((float) $this->entry_price - (float) $this->stop_loss);
        if ($risk == 0) return null;

        $reward = (float) $this->exit_price - (float) $this->entry_price;
        if ($this->direction_t === 'short') $reward = -$reward;

        return round($reward / $risk, 4);
    }

    private function resetTradeForm(): void
    {
        $this->editingTradeId = null;
        $this->trade_date     = now()->format('Y-m-d');
        $this->direction_t    = 'long';
        $this->entry_price    = '';
        $this->exit_price     = '';
        $this->stop_loss      = '';
        $this->session        = '';
        $this->setup_rating   = 3;
        $this->followed_rules = true;
        $this->confluences    = [];
        $this->newConfluence  = '';
        $this->notes          = '';
        $this->reset('screenshot');
        $this->resetErrorBag();
    }
}
