<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;
use Livewire\Attributes\On;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */

    public function render(): View
    {

        $locale = session()->get('locale', config('app.locale'));

        // Definir la ruta del archivo de traducción
        $path = base_path("lang/{$locale}.json");

        $translations = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        $user = Auth::user();
        return view(
            'layouts.app',
            [
                'translations' => $translations,
                // 'is_admin' => $user->is_admin
            ]
        );
    }
}
