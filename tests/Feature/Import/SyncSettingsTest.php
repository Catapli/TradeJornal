<?php

declare(strict_types=1);

use App\Livewire\SyncSettings;
use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

it('muestra el token enmascarado por defecto', function () {
    // El enmascarado es visual: el token completo viaja igualmente en el DOM
    // porque lo necesita el botón de copiar. Es la propia página del usuario
    // detrás de sesión, así que lo que se evita es enseñarlo a una cámara o a
    // quien mire la pantalla, no ocultárselo al navegador.
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SyncSettings::class)
        ->assertSet('tokenVisible', false)
        ->assertSee(mb_substr($user->sync_token, 0, 6) . str_repeat('•', 20) . mb_substr($user->sync_token, -4));
});

it('muestra el token entero al pedirlo', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SyncSettings::class)
        ->call('toggleToken')
        ->assertSee($user->sync_token);
});

it('regenera el token', function () {
    $user = User::factory()->create();
    $old = $user->sync_token;

    Livewire::actingAs($user)
        ->test(SyncSettings::class)
        ->call('regenerate')
        ->assertSet('tokenVisible', false);

    expect($user->fresh()->sync_token)->not->toBe($old);
});

it('resume el estado de sincronización de cada cuenta', function () {
    $user = User::factory()->create();

    Account::factory()->create([
        'user_id' => $user->id,
        'name' => 'Challenge activo',
        'sync' => true,
        'last_sync' => now()->subMinutes(5),
    ]);

    Account::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cuenta con fallo',
        'sync' => true,
        'sync_error' => true,
        'sync_error_message' => 'Login no encontrado en el terminal',
    ]);

    Livewire::actingAs($user)
        ->test(SyncSettings::class)
        ->assertSee('Challenge activo')
        ->assertSee(__('sync.accounts.active'))
        ->assertSee('Cuenta con fallo')
        ->assertSee(__('sync.accounts.error'))
        ->assertSee('Login no encontrado en el terminal');
});

it('deja fuera la cuenta de ejemplo, que no sincroniza nada', function () {
    $user = User::factory()->create();
    Account::factory()->create(['user_id' => $user->id, 'name' => 'De ejemplo', 'is_sample' => true]);

    Livewire::actingAs($user)
        ->test(SyncSettings::class)
        ->assertDontSee('De ejemplo')
        ->assertSee(__('sync.accounts.empty'));
});

it('sirve la página de conexión', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('sync.settings'))
        ->assertOk()
        ->assertSee(__('sync.title'));
});
