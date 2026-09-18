<?php

declare(strict_types=1);

namespace App\Actions\Rules;

use App\Models\Account;
use App\Models\TradingPlan;
use App\Models\TradingRule;
use Illuminate\Support\Facades\DB;

/**
 * Convierte un hallazgo en regla (Fase 5 · P7).
 *
 * La regla se guarda entera —texto, parámetros y de dónde salió— y, cuando el
 * semáforo de la sesión sabe vigilarla, se copia además al `trading_plan` de la
 * cuenta, que es lo que ese semáforo lee. Dos sitios, un solo gesto del usuario.
 *
 * El plan solo se toca cuando la regla es de una cuenta concreta: una regla
 * global no puede escribir un horario en cuatro planes distintos sin pisar lo que
 * el usuario haya puesto a mano en cada uno.
 */
class AdoptFinding
{
    /**
     * @param  array<string, mixed>  $finding  una entrada de DetectFindings
     */
    public function execute(int $userId, array $finding, ?int $accountId, string $text): TradingRule
    {
        return DB::transaction(function () use ($userId, $finding, $accountId, $text): TradingRule {
            $rule = TradingRule::updateOrCreate(
                [
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'source_key' => $finding['key'],
                ],
                [
                    'kind' => $finding['kind'],
                    'text' => $text,
                    'config' => $finding['config'] ?? [],
                    'source_summary' => $text,
                    'source_sample' => (int) ($finding['sample'] ?? 0),
                    'is_active' => true,
                ]
            );

            if ($accountId !== null) {
                $this->syncPlan($userId, $accountId, $rule);
            }

            return $rule;
        });
    }

    /** Baja la regla al plan de la cuenta para que el semáforo la vigile. */
    private function syncPlan(int $userId, int $accountId, TradingRule $rule): void
    {
        if (!$rule->isEnforceable()) {
            return;
        }

        $account = Account::where('user_id', $userId)->find($accountId);

        if (!$account) {
            return;
        }

        $cambios = match ($rule->kind) {
            TradingRule::KIND_MAX_TRADES => ['max_daily_trades' => (int) ($rule->config['limit'] ?? 0)],
            TradingRule::KIND_TIME_WINDOW => [
                'start_time' => $rule->config['from'] ?? null,
                'end_time' => $rule->config['to'] ?? null,
            ],
            // El día de la semana no cabe en el plan actual: vive solo en la regla
            // y se comprueba desde ahí.
            default => [],
        };

        $cambios = array_filter($cambios, fn ($v): bool => $v !== null && $v !== 0 && $v !== '');

        if ($cambios === []) {
            return;
        }

        TradingPlan::updateOrCreate(
            ['account_id' => $accountId],
            $cambios + ['is_active' => true]
        );
    }
}
