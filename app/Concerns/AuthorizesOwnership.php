<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Carga un modelo por id y comprueba su Policy en un solo paso.
 *
 * En Livewire los ids llegan del cliente (propiedades públicas, argumentos de
 * acciones), así que un `Model::findOrFail($id)` suelto es un IDOR: devuelve el
 * registro de cualquier usuario. El proyecto lo resolvía repitiendo a mano
 * `where('user_id', Auth::id())` en 81 sitios y `$this->authorize()` en 5 — es
 * decir, dependía de acordarse. Esto hace que el camino seguro sea el más corto.
 *
 *     $account = $this->findOwned(Account::class, $id, 'update');
 */
trait AuthorizesOwnership
{
    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  bool  $withTrashed  Para las acciones que solo tienen sentido
     *                             sobre un registro archivado (restaurar,
     *                             borrar definitivamente).
     * @return TModel
     */
    protected function findOwned(string $model, mixed $id, string $ability = 'view', bool $withTrashed = false): Model
    {
        // Un id manipulado no debe distinguirse de uno inexistente.
        abort_unless(is_numeric($id) && (int) $id > 0, 404);

        $query = $model::query();

        if ($withTrashed) {
            // Solo los modelos con SoftDeletes tienen withTrashed(), y PHPStan
            // ve aquí el Builder genérico. Quien pide $withTrashed sabe sobre
            // qué modelo trabaja; si no lo usa, salta un BadMethodCallException
            // claro en vez de devolver el registro equivocado.
            /** @phpstan-ignore method.notFound */
            $query->withTrashed();
        }

        $instance = $query->findOrFail((int) $id);

        $this->authorize($ability, $instance);

        return $instance;
    }
}
