<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Rules\AdoptFinding;
use App\Actions\Rules\DetectFindings;
use App\Concerns\RequiresProAccess;
use App\LogActions;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradingRule;
use App\Support\Demo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * De hallazgo a regla (Fase 5 · P7).
 *
 * Un gráfico no cambia a nadie. Una frase con la cifra delante —«a partir de tu
 * cuarta operación pierdes 1.240 €»— y un botón que la convierte en algo que vas
 * a ver antes de operar, sí.
 *
 * Vive en el Laboratorio porque es donde están los datos de los que sale, y
 * respeta su filtro de cuenta: el hallazgo se calcula sobre lo que estás mirando.
 */
class FindingsPanel extends Component
{
    use LogActions;
    use RequiresProAccess;

    /** Filtro de cuenta heredado del Laboratorio: 'all' o un id. */
    public string $accountId = 'all';

    /** Hallazgo abierto en el diálogo de adopción, o null. */
    public ?string $adopting = null;

    public string $ruleText = '';

    /** 'account' o 'all' */
    public string $scope = 'account';

    public ?int $scopeAccountId = null;

    public function mount(string $accountId = 'all'): void
    {
        $this->accountId = $accountId;
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function findings(): array
    {
        $query = Trade::forUserActiveAccounts(Auth::id())->with('mistakes');

        if ($this->accountId !== 'all') {
            $query->where('account_id', (int) $this->accountId);
        }

        $adoptadas = $this->rules->pluck('source_key')->all();

        return collect(app(DetectFindings::class)->execute($query->get()))
            ->map(function (array $f) use ($adoptadas): array {
                $f['sentence'] = $this->sentence($f);
                $f['proposal'] = $this->proposal($f);
                $f['adopted'] = in_array($f['key'], $adoptadas, true);

                return $f;
            })
            ->all();
    }

    /** @return Collection<int, TradingRule> */
    #[Computed]
    public function rules(): Collection
    {
        return TradingRule::where('user_id', Auth::id())
            ->with('account:id,name')
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get();
    }

    /** @return Collection<int, Account> */
    #[Computed]
    public function accounts(): Collection
    {
        return Account::where('user_id', Auth::id())
            ->where('status', '!=', 'burned')
            ->get(['id', 'name']);
    }

    public function startAdopting(string $key): void
    {
        $finding = collect($this->findings)->firstWhere('key', $key);

        if (!$finding) {
            return;
        }

        $this->adopting = $key;
        $this->ruleText = $finding['proposal'];

        // Se propone la cuenta que estás mirando; si miras todas, la regla nace
        // global, que es lo que el filtro está diciendo.
        $this->scope = $this->accountId === 'all' ? 'all' : 'account';
        $this->scopeAccountId = $this->accountId === 'all' ? null : (int) $this->accountId;
    }

    public function cancelAdopting(): void
    {
        $this->adopting = null;
        $this->ruleText = '';
    }

    public function adopt(): void
    {
        if (Demo::active()) {
            $this->dispatch('show-alert', type: 'error', message: __('landing.demo.blocked'));

            return;
        }

        $finding = collect($this->findings)->firstWhere('key', $this->adopting);

        if (!$finding || trim($this->ruleText) === '') {
            return;
        }

        $accountId = $this->scope === 'all' ? null : $this->scopeAccountId;

        if ($this->scope === 'account' && !$this->ownsAccount($accountId)) {
            $this->dispatch('show-alert', type: 'error', message: __('rules.adopt.no_account'));

            return;
        }

        $rule = app(AdoptFinding::class)->execute(
            (int) Auth::id(),
            $finding,
            $accountId,
            trim($this->ruleText),
        );

        $this->insertLog(
            action: 'adopt_rule',
            form: 'FindingsPanel',
            description: "Hallazgo {$finding['key']} adoptado como regla #{$rule->id}",
        );

        $this->reset(['adopting', 'ruleText']);
        unset($this->rules, $this->findings);

        $this->dispatch('show-alert', type: 'success', message: $rule->isEnforceable() && $accountId !== null
            ? __('rules.adopt.enforced')
            : __('rules.adopt.saved'));
    }

    public function toggle(int $ruleId): void
    {
        if (Demo::active()) {
            $this->dispatch('show-alert', type: 'error', message: __('landing.demo.blocked'));

            return;
        }

        $rule = TradingRule::where('user_id', Auth::id())->find($ruleId);

        if (!$rule) {
            return;
        }

        $rule->update(['is_active' => !$rule->is_active]);
        unset($this->rules);

        $this->dispatch('show-alert', type: 'success', message: $rule->is_active
            ? __('rules.mine.toggled_on')
            : __('rules.mine.toggled_off'));
    }

    public function remove(int $ruleId): void
    {
        if (Demo::active()) {
            $this->dispatch('show-alert', type: 'error', message: __('landing.demo.blocked'));

            return;
        }

        $rule = TradingRule::where('user_id', Auth::id())->find($ruleId);

        if (!$rule) {
            return;
        }

        $rule->delete();
        unset($this->rules, $this->findings);

        $this->dispatch('show-alert', type: 'success', message: __('rules.mine.deleted'));
    }

    public function render()
    {
        return view('livewire.findings-panel');
    }

    // ─────────────────────────────────────────────────────────────

    private function ownsAccount(?int $accountId): bool
    {
        return $accountId !== null
            && Account::where('user_id', Auth::id())->whereKey($accountId)->exists();
    }

    /** La frase del hallazgo, con su cifra. */
    private function sentence(array $f): string
    {
        return __("rules.finding.{$f['key']}", $this->replacements($f));
    }

    /** La regla que se propone, editable antes de adoptarla. */
    private function proposal(array $f): string
    {
        $key = "rules.finding.{$f['key']}_rule";

        // Cuando la franja mala parte el día en dos no hay ventana que guardar:
        // la regla se queda en recordatorio, y lo dice.
        if ($f['key'] === 'time_of_day' && ($f['config'] === [] || !isset($f['config']['from']))) {
            $key = 'rules.finding.time_of_day_rule_soft';
        }

        return __($key, $this->replacements($f));
    }

    /** @return array<string, string> */
    private function replacements(array $f): array
    {
        $p = $f['params'];

        return [
            'name' => (string) ($p['name'] ?? ''),
            'amount' => number_format((float) ($p['amount'] ?? 0), 2, ',', '.') . ' €',
            'count' => (string) ($p['count'] ?? 0),
            'position' => (string) ($p['position'] ?? ''),
            'limit' => (string) ($p['limit'] ?? ''),
            'from' => (string) ($p['from'] ?? ''),
            'to' => (string) ($p['to'] ?? ''),
            'slot' => isset($p['slot']) ? __('rules.slots.' . $p['slot']) : '',
            'weekday' => isset($p['weekday']) ? __('rules.weekdays.' . $p['weekday']) : '',
        ];
    }
}
