<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Retention\BuildWeeklySummary;
use App\Mail\WeeklySummaryMail;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envía el resumen semanal (R1).
 *
 * Se programa **cada hora**, no una vez a la semana: la hora acordada con el
 * usuario es la suya, así que en cada pasada solo escribe a quienes en ese
 * momento están en el día y la hora configurados. `weekly_summary_sent_at`
 * evita que un reintento o un cambio de huso provoquen un segundo correo.
 *
 *   php artisan resumen:semanal                → tanda programada
 *   php artisan resumen:semanal --user=3 --force --dry-run
 */
class SendWeeklySummaries extends Command
{
    protected $signature = 'resumen:semanal
        {--user= : ID o correo de un único destinatario}
        {--force : Ignora la ventana horaria y la marca de enviado}
        {--dry-run : No envía nada; solo dice a quién escribiría}';

    protected $description = 'Envía el resumen semanal por correo a quien lo tenga activado';

    public function handle(BuildWeeklySummary $builder): int
    {
        if (!config('retention.weekly_summary.enabled') && !$this->option('force')) {
            $this->components->warn('El resumen semanal está desactivado (retention.weekly_summary.enabled).');

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        $this->recipients()->chunkById(
            (int) config('retention.weekly_summary.chunk'),
            function ($users) use ($builder, &$sent, &$skipped): void {
                foreach ($users as $user) {
                    $this->process($user, $builder, $sent, $skipped);
                }
            }
        );

        $this->components->info("Resumen semanal: {$sent} enviados, {$skipped} omitidos.");

        return self::SUCCESS;
    }

    /** Candidatos: nadie más que quien lo ha pedido y tiene correo verificado. */
    private function recipients()
    {
        $query = User::query()
            ->whereNotNull('email_verified_at')
            // El usuario de la demo es de todos y de nadie: no se le escribe.
            ->where('email', '!=', (string) config('demo.email'));

        if ($user = $this->option('user')) {
            // Postgres no compara un bigint con texto: el id solo se busca si de
            // verdad lo es, y en otro caso se trata como correo.
            return is_numeric($user)
                ? $query->whereKey((int) $user)
                : $query->where('email', $user);
        }

        return $query->where('weekly_summary', true);
    }

    private function process(User $user, BuildWeeklySummary $builder, int &$sent, int &$skipped): void
    {
        $force = (bool) $this->option('force');

        if (!$user->weekly_summary && !$force) {
            $skipped++;

            return;
        }

        $now = $user->nowInTimezone();

        if (!$force && !$this->isDeliveryMoment($now)) {
            $skipped++;

            return;
        }

        // La semana que se resume es la que acaba de terminar: si hoy es domingo,
        // el lunes de esta misma semana natural.
        $weekStart = $now->startOfWeek();

        if (!$force && $user->weekly_summary_sent_at
            && $user->weekly_summary_sent_at->greaterThanOrEqualTo($weekStart->startOfDay())) {
            $skipped++;

            return;
        }

        $summary = $builder->execute($user, $weekStart);

        // Un correo que dice «0 operaciones, 0 días de diario» no aporta nada y
        // enseña a ignorar los siguientes.
        if (!$summary['has_activity']) {
            $skipped++;

            return;
        }

        if ($this->option('dry-run')) {
            $this->line("  [simulado] {$user->email} · {$summary['trades']} operaciones · " . number_format($summary['pnl'], 2) . ' $');
            $sent++;

            return;
        }

        try {
            Mail::to($user)->send(new WeeklySummaryMail($user, $summary));
        } catch (Throwable $e) {
            $this->components->error("No se pudo encolar el resumen de {$user->email}: {$e->getMessage()}");
            $skipped++;

            return;
        }

        $user->forceFill(['weekly_summary_sent_at' => now()])->saveQuietly();
        $sent++;
    }

    /** ¿Es ahora mismo el día y la hora acordados en la zona del usuario? */
    private function isDeliveryMoment(CarbonImmutable $now): bool
    {
        return $now->dayOfWeek === (int) config('retention.weekly_summary.day')
            && $now->hour === (int) config('retention.weekly_summary.hour');
    }
}
