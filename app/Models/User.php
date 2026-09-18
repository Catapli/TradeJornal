<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

/**
 * @method bool isSuperAdmin()
 * @method bool canDo(string $section, string $action)
 */
class User extends Authenticatable implements HasLocalePreference
{
    use Billable;
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    // <--- Añade esto

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasTeams;

    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'sync_token',
        'timezone',
        'locale',
        'weekly_summary',
    ];

    /**
     * Valores por defecto del modelo.
     *
     * La columna ya trae `default(true)`, pero eso solo lo sabe la base de datos:
     * sin esto, el recién registrado tiene `weekly_summary` a null en memoria y
     * cualquier comprobación en la misma petición lo daría por desactivado.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'weekly_summary' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_dismissed_at' => 'datetime',
            'weekly_summary' => 'boolean',
            'weekly_summary_sent_at' => 'datetime',
            'password' => 'hashed',
            // Cashier lee trial_ends_at, pero este casts() sobrescribe el suyo:
            // sin esta línea llega como string y onProTrial() reventaría.
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->sync_token = static::generateUniqueSyncToken();
        });
    }

    protected static function generateUniqueSyncToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('sync_token', $token)->exists());

        return $token;
    }

    public function regenerateSyncToken()
    {
        $this->sync_token = Str::random(40);
        $this->save();

        return $this->sync_token;
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function weeklyReviews(): HasMany
    {
        return $this->hasMany(WeeklyReview::class);
    }

    public function activeAccounts()
    {
        return $this->accounts()->where('status', 'active');
    }

    // Relación a través de cuentas hasta trades
    public function trades(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\Trade::class,   // Modelo final
            \App\Models\Account::class, // Modelo intermedio
            'user_id',                  // FK en accounts → users
            'account_id',               // FK en trades → accounts
            'id',                       // PK en users
            'id'                        // PK en accounts
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Idioma y huso horario
    // ─────────────────────────────────────────────────────────────

    /**
     * Idioma en el que hay que escribirle.
     *
     * Laravel llama a este método solo al encolar correos y notificaciones
     * (contrato HasLocalePreference), que es exactamente donde no hay sesión de
     * la que sacar el idioma.
     */
    public function preferredLocale(): string
    {
        return in_array($this->locale, config('app.supported_locales'), true)
            ? $this->locale
            : (string) config('app.locale');
    }

    /** Huso horario del usuario; el de la aplicación mientras no elija otro. */
    public function preferredTimezone(): string
    {
        return $this->timezone && in_array($this->timezone, timezone_identifiers_list(), true)
            ? $this->timezone
            : (string) config('app.timezone');
    }

    /** «Ahora» en la hora del usuario, que es la única que le dice algo. */
    public function nowInTimezone(): CarbonImmutable
    {
        return CarbonImmutable::now($this->preferredTimezone());
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_superadmin === true;
    }

    // ─────────────────────────────────────────────────────────────
    // Plan
    // ─────────────────────────────────────────────────────────────

    /**
     * Fuente única de verdad de «este usuario tiene PRO».
     *
     * Antes se comprobaba `subscribed('default')` en dieciséis sitios, así que al
     * añadir la prueba gratuita habría bastado con olvidarse de uno para que el
     * usuario en prueba se encontrara medio producto cerrado. Aquí dentro caben
     * la suscripción de pago y la prueba sin tarjeta; fuera, nadie tiene que
     * saber la diferencia.
     */
    public function hasProAccess(): bool
    {
        return $this->subscribed('default') || $this->onProTrial();
    }

    /** Prueba sin tarjeta en curso (users.trial_ends_at, sin nada en Stripe). */
    public function onProTrial(): bool
    {
        return !$this->subscribed('default')
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /** Días completos que le quedan de prueba. 0 si no está en prueba. */
    public function trialDaysLeft(): int
    {
        if (!$this->onProTrial()) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInDays($this->trial_ends_at, absolute: true)));
    }

    /** Cuántas cuentas activas permite su plan. `null` = sin límite. */
    public function accountLimit(): ?int
    {
        return $this->hasProAccess()
            ? config('billing.accounts.pro')
            : config('billing.accounts.free');
    }

    /**
     * ¿Puede crear una cuenta más?
     *
     * Las quemadas no cuentan: son historial. Y al degradar de PRO a gratuito no
     * se toca nada de lo que ya tenga — el límite solo frena las cuentas nuevas.
     */
    public function canCreateAccount(): bool
    {
        $limit = $this->accountLimit();

        if ($limit === null) {
            return true;
        }

        return $this->accounts()->where('status', '!=', 'burned')->count() < $limit;
    }

    // ─────────────────────────────────────────────────────────────
    // Créditos de IA
    // ─────────────────────────────────────────────────────────────

    /** Análisis con IA al día según el plan. */
    public function aiDailyLimit(): int
    {
        return (int) config($this->hasProAccess()
            ? 'services.groq.daily_limit_pro'
            : 'services.groq.daily_limit_free');
    }

    /** Análisis que le quedan hoy. */
    public function aiCreditsLeft(): int
    {
        $used = AiUsage::where('user_id', $this->id)
            ->where('date', today()->toDateString())
            ->value('count') ?? 0;

        return max(0, $this->aiDailyLimit() - (int) $used);
    }
}
