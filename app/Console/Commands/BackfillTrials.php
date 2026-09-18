<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Da la prueba de 14 días a los usuarios que ya existían.
 *
 * La prueba (M1, Fase 3) se marca en `CreateNewUser`, así que solo la reciben
 * los que se registran **después** de aquello: los que ya estaban se quedaron
 * con `trial_ends_at` a null y, por tanto, sin acceso a los módulos PRO que
 * nunca han podido probar. Quedó anotado en el roadmap como «un comando de una
 * línea» y aquí está.
 *
 * Se salta a quien ya tenga prueba —dársela otra vez sería regalar tiempo a
 * quien ya la gastó— y a quien esté suscrito, que no la necesita. También al
 * usuario de la demo pública, que no es una persona.
 *
 * Es de un solo uso, así que NO se programa: se lanza a mano y con `--dry-run`
 * delante.
 *
 *   php artisan prueba:retroactiva --dry-run
 *   php artisan prueba:retroactiva
 *   php artisan prueba:retroactiva --days=30 --user=jordi@ejemplo.com
 */
class BackfillTrials extends Command
{
    protected $signature = 'prueba:retroactiva
        {--days= : Días de prueba (por defecto, billing.trial_days)}
        {--user= : ID o correo de un único usuario}
        {--dry-run : No escribe nada; solo dice a quién se la daría}';

    protected $description = 'Da la prueba PRO a los usuarios que ya existían cuando se implantó';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('billing.trial_days'));

        if ($days <= 0) {
            $this->components->error("Días de prueba inválidos: {$days}. Revisa TRIAL_DAYS o pasa --days.");

            return self::FAILURE;
        }

        $candidates = $this->candidates()->get();

        if ($candidates->isEmpty()) {
            $this->components->info('No hay ningún usuario sin prueba que la necesite.');

            return self::SUCCESS;
        }

        $endsAt = now()->addDays($days);
        $dryRun = (bool) $this->option('dry-run');

        foreach ($candidates as $user) {
            $this->components->twoColumnDetail(
                $user->email,
                $dryRun ? 'se le daría' : 'hasta ' . $endsAt->format('d/m/Y')
            );

            if (!$dryRun) {
                $user->forceFill(['trial_ends_at' => $endsAt])->save();
            }
        }

        $total = $candidates->count();

        $this->components->info($dryRun
            ? "{$total} usuarios recibirían {$days} días de prueba. Repite sin --dry-run para aplicarlo."
            : "{$total} usuarios con {$days} días de prueba hasta el {$endsAt->format('d/m/Y')}.");

        return self::SUCCESS;
    }

    /**
     * Quién se la merece: sin prueba previa, sin suscripción y no es la demo.
     *
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    private function candidates()
    {
        $query = User::query()
            ->whereNull('trial_ends_at')
            ->where('email', '!=', (string) config('demo.email'))
            // `subscribed()` no se puede usar en la consulta, pero la relación sí:
            // quien tiene una suscripción viva no necesita que le regalen días.
            ->whereDoesntHave('subscriptions', function ($q): void {
                $q->whereIn('stripe_status', ['active', 'trialing', 'past_due']);
            });

        if ($user = $this->option('user')) {
            $query->where(is_numeric($user) ? 'id' : 'email', $user);
        }

        return $query;
    }
}
