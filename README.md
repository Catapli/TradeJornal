# TradeForge

Diario de trading para traders de prop firms: registra operaciones, mide la consistencia
y vigila los objetivos y límites de drawdown de cada programa.

- **Stack:** Laravel 11 · Livewire 3 · Jetstream (Fortify) · Tailwind 3 · Vite 6 · PostgreSQL
- **PHP:** ^8.2
- **Gráficos:** ApexCharts (métricas) y lightweight-charts (velas)
- **Pagos:** Laravel Cashier (Stripe) — planes Free y PRO
- **IA:** Groq (`api.groq.com`), con límite diario por usuario según plan

---

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev          # o `npm run build` para producción
```

Claves de `.env` que hay que rellenar para tener todo operativo: `DB_*`, `GROQ_API_KEY`,
`STRIPE_*`, `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` (login social) y `AWS_*` +
`CLOUDFLARE_*` (R2, para las capturas del journal).

---

## Cómo entran los datos

Los trades llegan por **API**: cada terminal MT5 corre un `.exe` que empuja sus operaciones
contra `routes/api.php`. La autenticación es por `sync_token` (columna única de `users`,
rotable con `User::regenerateSyncToken()`), que se valida en `Mt5SyncController`.

| Endpoint | Qué hace |
|---|---|
| `POST /api/mt5-sync` | Alta/actualización de trades de una cuenta |
| `POST /api/mt5-reset` | Reinicia la sincronización de una cuenta |
| `POST /api/mt5-refresh-charts` | Pide refresco de datos de gráfico |
| `POST /api/mt5-update-chart` | Sube los datos de vela de un trade |

---

## Estructura

```
app/
├── Actions/             # Lógica de negocio extraída de los componentes Livewire
│   ├── Accounts/        #   CalculateAccountStatistics, GenerateBalanceChartData
│   ├── Admin/           #   KPIs del panel de administración
│   ├── Backtesting/     #   CalculateStrategyMetrics
│   ├── Strategy/        #   RecalculateStrategyStats
│   └── Fortify|Jetstream/  # Acciones del scaffolding de auth
├── Enums/               # Enums de dominio
├── Events|Listeners/    # Eventos de dominio
├── Http/
│   ├── Controllers/     # API de sincronización MT5, trades, gráficos, imágenes
│   └── Middleware/      # SetLocale, CheckSectionPermission, superadmin
├── Jobs/                # Trabajos en cola
├── Livewire/            # Una clase por página (Dashboard, Account, Session, ...)
│   ├── Admin/           #   Panel de administración (logs, prop firms)
│   ├── Forms/           #   Form Objects de Livewire
│   └── Settings/        #   Suscripción
├── Models/              # Account, Trade, Strategy, PropFirm, Program*, Journal*, ...
├── Observers/           # Invalidación de caché al mutar trades y cuentas
├── Policies/ Providers/ Services/ Notifications/
├── LogActions.php       # Trait para insertar en `logs` desde cualquier sitio
├── MoneyHelper.php
└── WithAiLimits.php     # Cuota diaria de IA por plan

resources/
├── css/app.css          # Tailwind + parches de librerías de terceros
├── js/
│   ├── core/            # theme.js (tema claro/oscuro), notify.js, trade-toast.js,
│   │                    # session-guard.js
│   ├── plugins/         # Helper de traducciones para Alpine
│   └── <pagina>/        # Un módulo Alpine por página
└── views/
    ├── components/      # Componentes Blade reutilizables (+ buttons/, modals/)
    ├── layouts/         # app (autenticado) y guest (público)
    ├── livewire/        # Vistas de los componentes Livewire
    └── <seccion>/       # Vistas contenedoras de cada sección

database/               # migrations, factories, seeders
lang/                   # es, en (+ JSON para el JS)
routes/                 # web.php (navegación) · api.php (sync MT5) · console.php
```

---

## Tema claro / oscuro

El tema vive en la clase `dark` del `<html>`:

- Un script inline en **ambos** layouts la aplica antes del primer render (evita el FOUC).
  La preferencia se guarda en `localStorage.theme`; sin preferencia, se sigue al sistema.
- El botón del `navigation-menu` la alterna y emite `theme:changed`.
- [`resources/js/core/theme.js`](resources/js/core/theme.js) es la fuente de verdad para el
  JS: expone `window.tjTheme` (`isDark()`, `mode()`, `colors()`, `onChange()`) y la fábrica
  **`window.tjChart(el, options)`**, que sustituye a `new ApexCharts(...)`.

> Al crear un gráfico nuevo usa **siempre** `window.tjChart()`. Aplica el tema actual y deja
> el gráfico suscrito, de modo que se repinta solo al cambiar de tema, sin recargar.

---

## Comandos útiles

```bash
php artisan test           # Suite de tests (ver aviso abajo)
./vendor/bin/pint          # Formateo PHP
npm run build              # Build de producción
php artisan view:clear     # Limpiar vistas compiladas
```

Los tests corren contra una base de datos Postgres **dedicada** (`tradejornal_test`),
configurada en `phpunit.xml`. Se eligió Postgres en vez de sqlite en memoria porque es el
motor real del proyecto: las migraciones usan `enum()` y columnas `json`. Créala una vez con
`CREATE DATABASE tradejornal_test;` — las migraciones las aplica `RefreshDatabase` sola.

El estado del proyecto, la deuda técnica pendiente y el plan de trabajo están en
[plan.MD](plan.MD).
