<?php

use App\Livewire\MistakeSelector;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\User;
use Database\Seeders\MistakesSeeder;
use Livewire\Livewire;

/**
 * El catálogo de errores era una lista fija sembrada con nombres mezclados en ES/EN
 * y sin explicación. Ahora los errores globales llevan `slug` y se traducen desde
 * lang/{locale}/mistakes.php, y el usuario puede mantener los suyos propios — que
 * sólo él debe poder editar o borrar.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->otro = User::factory()->create();

    $cuenta = Account::factory()->create(['user_id' => $this->jordi->id]);
    $this->trade = Trade::factory()->create(['account_id' => $cuenta->id]);

    // El catálogo global lo deja sembrado la propia migración.
    $this->global = Mistake::whereNull('user_id')->where('slug', 'revenge_trading')->firstOrFail();

    $this->actingAs($this->jordi);
});

// ---------------------------------------------------------------------------
// TRADUCCIÓN DEL CATÁLOGO GLOBAL
// ---------------------------------------------------------------------------

it('traduce el catálogo global al idioma activo', function () {
    app()->setLocale('es');
    expect($this->global->fresh()->display_name)->toBe('Trading de venganza');

    app()->setLocale('en');
    expect($this->global->fresh()->display_name)->toBe('Revenge trading');
});

it('deja el catálogo listo con sólo migrar y sin duplicados al resembrar', function () {
    $globales = fn () => Mistake::whereNull('user_id');

    expect($globales()->count())->toBe(15)
        ->and($globales()->whereNull('slug')->count())->toBe(0);

    // El seeder es idempotente: DatabaseSeeder puede volver a pasarlo sin duplicar.
    $this->seed(MistakesSeeder::class);

    expect($globales()->count())->toBe(15)
        ->and($globales()->distinct()->count('slug'))->toBe(15);
});

it('describe cada error del catálogo en los dos idiomas', function () {
    foreach (['es', 'en'] as $locale) {
        app()->setLocale($locale);

        foreach (Mistake::whereNull('user_id')->get() as $mistake) {
            expect($mistake->display_name)->not->toBe($mistake->slug)
                ->and($mistake->display_description)->not->toBeEmpty();
        }
    }
});

it('usa el nombre literal en los errores del usuario', function () {
    $propio = Mistake::create([
        'user_id' => $this->jordi->id,
        'name' => 'Operar sin marcar zonas',
        'description' => 'Entré sin haber marcado los niveles clave.',
        'color' => 'teal',
        'weight' => 2,
    ]);

    app()->setLocale('en');

    expect($propio->display_name)->toBe('Operar sin marcar zonas')
        ->and($propio->display_description)->toBe('Entré sin haber marcado los niveles clave.')
        ->and($propio->color_hex)->toBe('#14B8A6');
});

// ---------------------------------------------------------------------------
// ERRORES PERSONALIZADOS
// ---------------------------------------------------------------------------

it('crea un error propio del usuario', function () {
    Livewire::test(MistakeSelector::class, ['trade' => $this->trade])
        ->call('openCreateForm')
        ->set('formName', 'Operar sin marcar zonas')
        ->set('formDescription', 'Entré sin haber marcado los niveles clave.')
        ->set('formColor', 'teal')
        ->set('formWeight', 2)
        ->call('saveMistake')
        ->assertHasNoErrors();

    $creado = Mistake::where('name', 'Operar sin marcar zonas')->first();

    expect($creado->user_id)->toBe($this->jordi->id)
        ->and($creado->slug)->toBeNull()
        ->and($creado->weight)->toBe(2);
});

it('rechaza dos errores propios con el mismo nombre', function () {
    Mistake::create(['user_id' => $this->jordi->id, 'name' => 'Duplicado', 'color' => 'rose', 'weight' => 1]);

    Livewire::test(MistakeSelector::class, ['trade' => $this->trade])
        ->call('openCreateForm')
        ->set('formName', 'Duplicado')
        ->call('saveMistake')
        ->assertHasErrors(['formName' => 'unique']);
});

it('rechaza colores fuera de la paleta', function () {
    Livewire::test(MistakeSelector::class, ['trade' => $this->trade])
        ->call('openCreateForm')
        ->set('formName', 'Color raro')
        ->set('formColor', 'javascript:alert(1)')
        ->call('saveMistake')
        ->assertHasErrors(['formColor']);
});

it('borra un error propio y lo desmarca de los trades', function () {
    $propio = Mistake::create(['user_id' => $this->jordi->id, 'name' => 'Temporal', 'color' => 'rose', 'weight' => 1]);
    $this->trade->mistakes()->attach($propio->id);

    Livewire::test(MistakeSelector::class, ['trade' => $this->trade])
        ->call('deleteMistake', $propio->id);

    expect(Mistake::find($propio->id))->toBeNull()
        ->and($this->trade->fresh()->mistakes)->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// AUTORIZACIÓN
// ---------------------------------------------------------------------------

it('no deja editar los errores del catálogo global', function () {
    Livewire::test(MistakeSelector::class, ['trade' => $this->trade])
        ->call('openEditForm', $this->global->id)
        ->assertSet('showForm', false)
        ->assertSet('editingId', null);
});

it('no deja borrar errores de otro usuario', function () {
    $ajeno = Mistake::create(['user_id' => $this->otro->id, 'name' => 'Ajeno', 'color' => 'rose', 'weight' => 1]);

    Livewire::test(MistakeSelector::class, ['trade' => $this->trade])
        ->call('deleteMistake', $ajeno->id);

    expect(Mistake::find($ajeno->id))->not->toBeNull();
});

it('no deja marcar un trade con un error de otro usuario', function () {
    $ajeno = Mistake::create(['user_id' => $this->otro->id, 'name' => 'Ajeno', 'color' => 'rose', 'weight' => 1]);

    Livewire::test(MistakeSelector::class, ['trade' => $this->trade])
        ->call('toggleMistake', $ajeno->id);

    expect($this->trade->fresh()->mistakes)->toHaveCount(0);
});
