<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\LogActions;
use App\Support\Demo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Idioma, huso horario y resumen semanal.
 *
 * Los tres van juntos porque los tres existen por el mismo motivo: el correo del
 * domingo se escribe fuera de una petición HTTP, donde no hay sesión de la que
 * deducir ni la lengua ni la hora del usuario.
 *
 * El resumen viene activado de fábrica —es el único mecanismo de retorno que
 * tiene el producto— y se apaga desde aquí o desde el enlace del propio correo.
 */
class NotificationPreferences extends Component
{
    use LogActions;

    public bool $weeklySummary = true;

    public string $timezone = '';

    public string $locale = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->weeklySummary = (bool) $user->weekly_summary;
        $this->timezone = $user->preferredTimezone();
        $this->locale = $user->preferredLocale();
    }

    /** ¿Todavía no ha elegido zona? Entonces la propone el navegador. */
    public function needsTimezoneDetection(): bool
    {
        return Auth::user()->timezone === null;
    }

    /** @return array<string, array<string, string>> husos agrupados por región */
    public function timezones(): array
    {
        $grouped = [];

        foreach (timezone_identifiers_list() as $identifier) {
            $region = str_contains($identifier, '/') ? explode('/', $identifier)[0] : 'UTC';
            $grouped[$region][$identifier] = str_replace(['_', '/'], [' ', ' · '], $identifier);
        }

        ksort($grouped);

        return $grouped;
    }

    public function save(): void
    {
        if (Demo::active()) {
            $this->dispatch('show-alert', type: 'error', message: __('landing.demo.blocked'));

            return;
        }

        $this->validate([
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'locale' => ['required', 'string', Rule::in(config('app.supported_locales'))],
            'weeklySummary' => ['boolean'],
        ]);

        Auth::user()->forceFill([
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'weekly_summary' => $this->weeklySummary,
        ])->save();

        // El idioma de la sesión manda mientras se navega; si no se sincroniza
        // aquí, el usuario cambiaría el idioma y seguiría viendo el anterior.
        session()->put('locale', $this->locale);
        app()->setLocale($this->locale);

        $this->insertLog(action: 'save', form: 'NotificationPreferences', description: 'Preferencias de correo actualizadas', type: 'info');
        $this->dispatch('show-alert', type: 'success', message: __('weekly.settings.saved'));
    }

    public function render()
    {
        return view('livewire.settings.notification-preferences');
    }
}
