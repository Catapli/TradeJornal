<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\Mt5Gateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateDailySnapshots extends Command
{
    // El nombre con el que llamarás al comando
    protected $signature = 'accounts:update-snapshots';

    protected $description = 'Guarda la equidad y balance inicial del día para calcular Drawdown diario';

    public function handle(Mt5Gateway $gateway)
    {
        $this->info('Iniciando Snapshot Diario...');

        // 1. Buscamos solo cuentas de Fondeo Activas
        $accounts = Account::where('type', 'prop_firm')
            ->where('status', 'active')
            ->get();

        foreach ($accounts as $account) {
            if (empty($account->mt5_login) || empty($account->mt5_password) || empty($account->mt5_server)) {
                $this->warn("⚠️ Saltando cuenta ID {$account->id}: Datos incompletos.");
                continue; // <--- Pasa a la siguiente cuenta y no llama a la API
            }
            try {
                $this->info("Procesando cuenta: {$account->name} ({$account->mt5_login})");

                // 2. Pedimos los datos rápidos
                $data = $gateway->getSnapshot($account);

                // 3. Actualizamos la BD con la "FOTO" de las 00:00
                $account->update([
                    // Guardamos la referencia para el cálculo de pérdidas de HOY
                    'today_starting_balance' => $data['balance'],
                    'today_starting_equity'  => $data['equity'],
                ]);

                \App\Models\AccountDailyMetric::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'date'       => now()->toDateString(), // Ej: "2026-01-13"
                    ],
                    [
                        'balance' => $data['balance'],
                        'equity'  => $data['equity']
                    ]
                );
            } catch (\Exception $e) {
                Log::error("Fallo Snapshot Cuenta {$account->id}: " . $e->getMessage());
                $this->error("Error en cuenta {$account->id}");
            }
        }

        $this->info('Snapshot completado.');
    }
}
