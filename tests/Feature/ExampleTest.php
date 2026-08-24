<?php

// La raíz nunca devuelve 200: redirige al dashboard si hay sesión y al login si no.
it('redirects guests to the login screen', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('redirects authenticated users to the dashboard', function () {
    $this->actingAs(App\Models\User::factory()->create());

    $this->get('/')->assertRedirect(route('dashboard'));
});
