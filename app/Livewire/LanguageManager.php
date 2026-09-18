<?php

namespace App\Livewire;

use App\Support\Demo;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class LanguageManager extends Component
{
    #[On('change_lang')]
    public function changeLocale($locale)
    {
        // Lista blanca: el idioma acaba guardado en la ficha del usuario y se usa
        // para escribirle correos, así que no puede llegar cualquier cosa.
        if (!in_array($locale, config('app.supported_locales'), true)) {
            return null;
        }

        session()->put('locale', $locale);
        app()->setLocale($locale);

        // La sesión solo vale mientras se navega. El resumen semanal se compone
        // desde la cola, sin sesión, y sin esta columna saldría siempre en el
        // idioma por defecto.
        if (Auth::check() && !Demo::active()) {
            Auth::user()->forceFill(['locale' => $locale])->saveQuietly();
        }

        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.language-manager');
    }
}
