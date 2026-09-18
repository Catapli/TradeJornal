<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Resultado de una importación, pensado para enseñárselo al usuario tal cual.
 *
 * Se distinguen tres desenlaces por fila porque significan cosas distintas:
 * importada, **omitida** por estar ya en la cuenta (lo normal al volver a subir
 * el mismo histórico) y **fallida** por un dato que no se pudo interpretar.
 */
class ImportReport
{
    public int $imported = 0;

    public int $skipped = 0;

    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $failed = [];

    /** @var array<int, string> */
    public array $newSymbols = [];

    public function fail(int $rowNumber, array $errors): void
    {
        // Un fichero mal mapeado generaría un error por fila; con 25 se entiende
        // el problema y el informe sigue siendo legible.
        if (count($this->failed) < 25) {
            $this->failed[] = ['row' => $rowNumber, 'errors' => $errors];
        }

        $this->failedCount++;
    }

    public int $failedCount = 0;

    public function total(): int
    {
        return $this->imported + $this->skipped + $this->failedCount;
    }

    public function isEmpty(): bool
    {
        return $this->total() === 0;
    }
}
