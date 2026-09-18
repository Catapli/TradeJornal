<?php

declare(strict_types=1);

namespace App\Livewire;

use App\LogActions;
use App\Models\Account;
use App\Models\Trade;
use App\Support\Demo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Autopsia de la cuenta que se acaba de quemar.
 *
 * Es el momento de máxima motivación para cambiar de hábitos y la aplicación lo
 * dejaba pasar en silencio. Aquí se le dice, con sus propios números, qué error
 * le costó más dinero, y se le ofrece seguir con PRO.
 *
 * Se muestra una sola vez por cuenta y solo durante el mes siguiente: pasado ese
 * plazo ya no es un aviso útil, es un reproche.
 */
class BurnedAccountCard extends Component
{
    use LogActions;

    /** Ventana en la que la autopsia todavía es relevante. */
    private const RECENT_DAYS = 30;

    /** Errores que se listan en el resumen. */
    private const TOP_MISTAKES = 3;

    public ?int $accountId = null;

    public function mount(): void
    {
        $this->accountId = $this->findBurned()?->id;
    }

    #[Computed]
    public function account(): ?Account
    {
        return $this->accountId
            ? Account::where('user_id', Auth::id())->find($this->accountId)
            : null;
    }

    /**
     * Los errores más caros de esa cuenta, en dinero.
     *
     * No se ordena por número de veces sino por lo que costaron: repetir diez
     * veces una salida prematura duele menos que promediar pérdidas dos.
     *
     * @return array<int, array{name: string, count: int, cost: float}>
     */
    #[Computed]
    public function costliestMistakes(): array
    {
        $account = $this->account();

        if (!$account) {
            return [];
        }

        return DB::table('trade_mistake')
            ->join('trades', 'trades.id', '=', 'trade_mistake.trade_id')
            ->join('mistakes', 'mistakes.id', '=', 'trade_mistake.mistake_id')
            ->where('trades.account_id', $account->id)
            ->where('trades.pnl', '<', 0)
            ->groupBy('mistakes.slug', 'mistakes.name')
            ->select('mistakes.slug', 'mistakes.name', DB::raw('count(*) as times'), DB::raw('sum(trades.pnl) as cost'))
            ->orderBy('cost')
            ->limit(self::TOP_MISTAKES)
            ->get()
            ->map(fn ($row) => [
                // El catálogo global se traduce por slug; los errores propios del
                // usuario no están en lang y se muestran con su nombre literal.
                'name' => $row->slug ? __("mistakes.{$row->slug}.name") : $row->name,
                'count' => (int) $row->times,
                'cost' => round((float) $row->cost, 2),
            ])
            ->all();
    }

    /** Cuánto perdió la cuenta, en dinero y en porcentaje. */
    #[Computed]
    public function summary(): array
    {
        $account = $this->account();

        if (!$account) {
            return ['pnl' => 0.0, 'percent' => 0.0, 'trades' => 0];
        }

        $pnl = (float) Trade::where('account_id', $account->id)->sum('pnl');
        $initial = (float) $account->initial_balance ?: 1.0;

        return [
            'pnl' => round($pnl, 2),
            'percent' => round($pnl / $initial * 100, 2),
            'trades' => Trade::where('account_id', $account->id)->count(),
        ];
    }

    public function dismiss(): void
    {
        if (Demo::active()) {
            $this->accountId = null;

            return;
        }

        $account = $this->account();

        if ($account) {
            $account->forceFill(['recovery_dismissed_at' => now()])->save();
            $this->insertLog(action: 'Resumen de cuenta quemada cerrado', form: 'BurnedAccountCard', description: "Cuenta {$account->id}");
        }

        $this->accountId = null;
        unset($this->account);
    }

    /** Código promocional configurado, si lo hay. Nunca se promete uno inexistente. */
    public function coupon(): ?string
    {
        $coupon = config('billing.recovery_coupon');

        return is_string($coupon) && $coupon !== '' ? $coupon : null;
    }

    private function findBurned(): ?Account
    {
        return Account::where('user_id', Auth::id())
            ->where('is_sample', false)
            ->where('status', 'burned')
            ->whereNull('recovery_dismissed_at')
            ->where('updated_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('updated_at')
            ->first();
    }

    public function render()
    {
        return view('livewire.burned-account-card');
    }
}
