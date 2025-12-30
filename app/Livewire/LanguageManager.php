<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class LanguageManager extends Component
{
    #[On('change_lang')]
    public function changeLocale($locale)
    {
        session()->put('locale', $locale);
        app()->setLocale($locale);
        return redirect(request()->header('Referer'));
    }
    public function render()
    {
        return view('livewire.language-manager');
    }
}
