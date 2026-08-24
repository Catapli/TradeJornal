# TradeForge — Plan de Afinado

> Documento vivo. Auditoría inicial el 2026-08-21 sobre `main`. Última actualización:
> **2026-08-24** — árbol limpio, suite en verde con 92 tests.

---

## 1. Estado del Tema Oscuro

> **Sprint 1 ejecutado el 2026-08-21.** Cobertura: **35 → 100 de 131 ficheros Blade**.
> Auditoría automática de huecos reales (clase clara sin contrapartida `dark:` en el mismo
> atributo): **338 → 100**, y los 100 restantes están *todos* en código muerto o en chrome
> oscuro por diseño. **Huecos en páginas vivas: 0.**

### Infraestructura: ✅ COMPLETA

| Pieza | Estado | Ubicación |
|---|---|---|
| `darkMode: "class"` en Tailwind | ✅ | [tailwind.config.js:7](tailwind.config.js#L7) |
| Script anti-FOUC — layout de app | ✅ | [layouts/app.blade.php:13-22](resources/views/layouts/app.blade.php#L13-L22) |
| Script anti-FOUC — layout de invitado | ✅ **nuevo** | [layouts/guest.blade.php:11-21](resources/views/layouts/guest.blade.php#L11-L21) |
| Persistencia `localStorage.theme` + fallback SO | ✅ | ambos layouts |
| Botón toggle sol/luna + evento `theme:changed` | ✅ **nuevo** | [navigation-menu.blade.php:51-64](resources/views/navigation-menu.blade.php#L51-L64) |
| Helper JS `window.tjTheme` / `window.tjChart` | ✅ **nuevo** | [resources/js/core/theme.js](resources/js/core/theme.js) |

### ✅ HECHO

**Páginas principales (9)** — ya estaban, y se les han cerrado los huecos residuales
(+47 dashboard, +26 account, +23 session, +22 trade-detail-modal, +21 playbook,
+19 session-history, +18 reports, +8 trades, +5 journal):
`session-page`, `account-page`, `dashboard-page`, `playbook-page`, `trades-page`,
`reports-page`, `session-history`, `journal-page`, `trade-detail-modal`.

**FASE A — Backtesting** (9 ficheros, ~2.200 L, de 0 a 326 clases `dark:`):
`backtesting-page`, `_analytics`, `_trade-panel`, `_trade-detail-modal`, `_rules`,
`_strategy-modal`, `_trade-log`, `_list`, `_detail`.

**FASE B — Componentes compartidos:**
`modal`, `confirm-modal`, `confirmation-modal`, `dialog-modal`, `dropdown`, `dropdown-link`,
`banner`, `loader`, `loader-modal`, `stat-card`, `admin/kpi-card`, `form-section`,
`action-section`, `section-title`, `section-border`, `action-message`, `nav-link`,
`responsive-nav-link`, `label`, `input`, `checkbox`, `button`, `secondary-button`,
`danger-button`, `validation-errors`, `input-error`, `authentication-card`,
`authentication-card-logo`, `vendor/livewire/tradeforge-pagination`.

**FASE C — Admin:** `admin-panel`, `admin/prop-firms-manager`, `admin/logs`,
`users-page`, `rols-page`.

**FASE D — Auth / Perfil:** `layouts/guest` (**arreglado el bug del tema perdido al
hacer login/logout**), `login`, `register`, `forgot-password`, `reset-password`,
`confirm-password`, `verify-email`, `two-factor-challenge`, las 6 vistas de perfil,
`api-token-manager`, `components/welcome`.

**FASE E — Sueltos:** `ai-daily-tip`, `daily-journal`, `mistake-selector`,
`card-objectives`, `card-statistics`, `input-multiselect`, `modals/modal-account`,
`datepicker`, `single-date-picker`, `settings/subscription`.

**FASE F — JS / CSS:**
- `resources/js/core/theme.js`: fuente única de verdad del tema. Expone `tjTheme`
  (`isDark`, `mode`, `colors`, `onChange`, `registerChart`) y la fábrica `tjChart`.
- **Los 18 gráficos ApexCharts** de `dashboard`, `reports`, `playbook`, `accounts` y
  `backtesting-charts` pasan de `new ApexCharts(...)` a `window.tjChart(...)`: reciben
  `theme.mode`, `foreColor`, tooltip y rejilla del tema actual, y **se repintan solos**
  al pulsar el toggle (sin recargar). Los colores de las series no se tocan.
- Toasts SweetAlert (`notify.js`, `trade-toast.js`): fondo y texto según el tema.
- `app.css`: eliminados los parches `!important` de ejes y leyenda (ya los resuelve
  `theme.js`); añadidas reglas `.dark` para **Trix, flatpickr, mdtimepicker, DataTables
  y los radios `.spanRadio`**.

### ⛔ Lo que queda pendiente (y por qué)

| Fichero | Huecos | Motivo |
|---|---|---|
| `livewire/economic-calendar-page` | 45 | Módulo huérfano — §2.1, decidir antes de tematizar |
| `livewire/economic-calendar` | 10 | idem |
| `components/modals/modal-info` | 12 | **Sin uso en todo el proyecto** → borrar |
| `components/modals/modal-info-traffic` | 12 | **Sin uso** + resto de otro proyecto → borrar |
| `livewire/town` | 11 | Código muerto → borrar |
| `components/checkbox-list` | 4 | **Sin uso** (props de Town) → borrar |
| `welcome.blade.php` | 5 | Landing pública, decisión: se queda en claro |
| `sidebar-menu` | 1 | Oscuro fijo por diseño (chrome) |

**Decisiones aplicadas en este sprint** (revertibles):
1. Sidebar: se queda oscuro fijo en ambos temas.
2. Landing `welcome.blade.php`: solo tema claro.
3. Calendario económico y módulo Town: no se tematizan, pendientes de borrar/terminar.
4. Gráfico de velas (lightweight-charts, `dashboard.js`): se queda con la paleta oscura
   estilo TradingView en ambos temas — es una decisión de producto, no un hueco.

### 🔧 Deuda que queda del tema oscuro
- [ ] `select option` sigue con el gradiente verde `!important` en [app.css:19-23](resources/css/app.css#L19-L23)
      y su contra-parche `.dark`. Limpiarlo implica tocar el tema claro, así que se deja aparte.
- [ ] Tokens semánticos (`surface`, `text-muted`…) en `tailwind.config.js` para dejar de
      repetir `bg-white dark:bg-gray-800`. Sigue siendo la mejora estructural pendiente.
- [ ] Componentes Blade base (`<x-card>`, `<x-panel>`) por el mismo motivo.
- [ ] Revisar contraste AA en oscuro: `text-gray-400` sobre `gray-900` va justo.
- [ ] **Falta la comprobación visual en navegador** de las páginas migradas por script.

## 2. Lluvia de ideas: mejoras

### 2.1 Limpieza / código muerto — ✅ HECHO (2026-08-21)

Eliminada la sedimentación de la app de tráfico/cámaras: **28 ficheros borrados,
−3.673 líneas**. Verificado antes de borrar que nada de ello funcionaba ya: la tabla
`towns` no existe en ninguna migración, ni `User` ni `Log` tienen relación `town`, y
`logs` no tiene columna `town_id` — o sea, `/logs/data` y `/users/data` llevaban tiempo
lanzando `QueryException`.

**Borrado:**
- `app/Models/Town.php`, `livewire/town.blade.php`, `components/checkbox-list.blade.php`
- `components/modals/modal-info.blade.php` y `modal-info-traffic.blade.php` (sin uso)
- `components/loader-modal.blade.php` y `resources/js/utils/map-initializer.js`
  (solo los usaban los dos modales anteriores)
- `AuthController` y `NotificationController` (rutas comentadas; usaban `InterestList`,
  modelo inexistente)
- **Subsistema de usuarios/roles** (~1.800 L): `UsersPage`, `RolsPage`, `LogCreateForm`,
  `LogFilterForm`, `UserController`, `RolsController`, `LogController`, sus vistas
  (`users-page`, `rols-page`, `users/index`, `logs/index`) y su JS (`users.js`, `rols.js`).
  Estaba construido sobre el modelo de permisos **por municipio**, que en TradeForge no
  significa nada. El panel admin actual (`AdminPanel`, `Admin\Logs`,
  `Admin\PropFirmsManager`) ya cubre la gestión y no dependía de nada de esto.
- **jQuery + DataTables**: sin usuarios tras el borrado anterior. Fuera de `package.json`,
  fuera `public/datatable/`, fuera sus reglas de `app.css` y del `content` de Tailwind.
  **Desaparecen dos chunks del build: 87 kB (jQuery) + 97 kB (DataTables).**
- Imágenes `public/img/detrafic_*` y el branding "Cloud Detrafic" / "DETRAFIC" que
  quedaba en `welcome.blade.php` y `application-mark.blade.php`.
- **11 imports fantasma** (`use App\...` de clases inexistentes) en `DashboardPage`,
  `UsersPage`, `UserFactory` y `DatabaseSeeder` (Camera, Traffic, Zone, Lists,
  CollaborativeList, File, Reason, Team, InterestList, Town).
- **18 imports fantasma de controladores** en `routes/web.php` y `routes/api.php`.
- `lang/es/logging.php`: de 47 a 22 claves (fuera tráfico, municipios, cámaras, zonas,
  listas de interés y colaborativas). Verificado antes: ninguna de las borradas se usaba.
- `town_id` del `$fillable` de `User` (columna que no existe en la migración).

**Se conservan** (los usa el sidebar y las rutas de admin): `Role`, `Section`,
`RoleSectionPermission`, el middleware `CheckSectionPermission`, `isSuperAdmin()`,
`app/Models/Log.php` y `resources/js/logs/logs.js` (registra el `x-data="logs"` de la
página admin de logs, que sí funciona).

**Verificado tras el borrado:** `php -l` sobre todo `app/`, `routes/` y `database/` sin
errores · `php artisan route:list` → 69 rutas · `php artisan view:cache` compila las
128 vistas · `npm run build` OK.

### 2.1.b Limpieza que queda
- [x] ~~Calendario Económico~~ — **RETIRADO (2026-08-21)**. Ver §2.1.c.
- [x] ~~`app/Livewire/Profile/*.blade.php`~~ — **borrados**. Eran los stubs originales
      de Jetstream en inglés (`<x-action-section>`); las versiones vivas y traducidas están
      en `resources/views/profile/`. Al no estar bajo un view path, no podían renderizarse.
- [x] ~~README obsoleto~~ — **reescrito** sobre el proyecto real: stack, puesta en
      marcha, cómo entran los datos (API + `.exe` de MT5), estructura de directorios y
      cómo funciona el tema claro/oscuro.
- [x] ~~`TODO.md` desfasado~~ — **absorbido** en §2.5.c y eliminado.
- [x] ~~9 `console.log`~~ — **eliminados** de `accounts`, `dashboard`, `propfirms` y `session`.
- [x] ~~`safelist` de Tailwind~~ — **eliminado entero** (12 entradas). Verificado que
      no hay ni una sola clase construida por concatenación en el proyecto, así que el
      escáner de Tailwind ve todo lo que necesita. También se quitó `./public/datatable/**`
      del `content`.

### 2.1.b.2 Claves de configuración huérfanas — ✅ HECHO

- `config/services.php`: eliminado el bloque `jblanked` (API de noticias económicas del
  calendario retirado).
- `.env.example`: eliminadas `API_KEY_MAPS` (Google Maps, se fue con `map-initializer.js`)
  y `JBLANKED_API_KEY`.
- **Ojo con el nombre:** el proveedor de IA es **Groq** (`api.groq.com`), no Grok (xAI).
  El commit `fb67bd9` lo llama "Grok"; la configuración y el código usan Groq.

### 2.1.c Calendario Económico — retirado (2026-08-21)

Decisión de Jordi: retirarlo. Evidencia recogida antes de borrar:
- **Nada poblaba `economic_events`**: el comando `calendar:sync` ya no existía y sus tres
  tareas de `routes/console.php` estaban comentadas.
- **La tabla tenía 0 filas.**
- Ruta `/calendar` y entrada del sidebar, comentadas.

Consecuencia importante: el **widget de noticias de la sesión en vivo**
(`SessionPage::getUpcomingHighImpactEvents()` + la píldora roja de `session-page.blade.php`)
consultaba esa tabla vacía y se auto-ocultaba con `x-show="events.length > 0"`. Era
funcionalidad muerta en la práctica, no solo la página huérfana — por eso se retira también.

**Eliminado:** `EconomicCalendar`, `EconomicCalendarPage`, `EconomicEvent`, sus dos blades,
`calendar/index.blade.php`, `resources/js/economic/economic.js`, el widget de noticias y el
estado `events` de `session/session.js`, la ruta y la entrada de menú comentadas, los tres
`Schedule::command('calendar:sync')`, el `use Schedule` que quedaba sin usar en
`console.php`, el `<livewire:economic-calendar>` comentado del journal y las claves
`menu.calendar` de `lang/es` y `lang/en`.

**Tabla:** añadida `2026_08_21_120000_drop_economic_events_table.php` siguiendo el mismo
patrón que `drop_trade_violations_table` — con el `down()` completo por si hay que revertir.
**La migración no se ha ejecutado**: lánzala tú cuando quieras (`php artisan migrate`).

**Verificado:** `php -l` limpio · 69 rutas · vistas compilan · `npm run build` OK.

### 2.2 Testing — ✅ SUITE EN VERDE + PRIMEROS TESTS DE DOMINIO (2026-08-21)

**Antes:** 24 fallaban, 4 pasaban, 4 se saltaban. **Ahora: 92 pasan, 0 fallan, 4 se saltan.**
(53 tras el sprint del 21/08 · +26 de objetivos de prop firm y +13 de `TradeObserver` el 24/08, ver §2.2.b y §2.2.c.)

#### Cómo se desbloqueó
`phpunit.xml` tenía comentadas `DB_CONNECTION` y `DB_DATABASE`, así que los tests atacaban
la base de datos de desarrollo. Se apunta ahora a **`tradejornal_test`**, una BD Postgres
dedicada creada al efecto.

> Se descartó sqlite en memoria por dos motivos: `pdo_sqlite` está **desactivado** en el
> `php.ini` de Laragon (habría que tocar tu entorno), y las migraciones usan `enum()` y
> `json`, que sqlite trata distinto — testear sobre otro motor daría falsa confianza.

#### Tres bugs reales encontrados al poner la suite en marcha

1. **`CreateNewUser` hardcodeaba `role_id => 3`.** No era solo un problema de test: en un
   despliegue limpio, o si los roles se sembraran en otro orden, **cada registro de usuario
   reventaba** con una violación de clave foránea. Ahora busca el rol por nombre con
   `firstOrCreate`.
2. **`maxLoss` inventaba pérdidas.** `MIN(t.pnl)` sin filtrar por signo: en una cuenta **sin
   ninguna pérdida**, devolvía la ganancia más pequeña como "pérdida máxima". Una cuenta con
   +50/+200/+300 mostraba "pérdida máxima: 50 $".
3. **`maxWin` inventaba ganancias negativas.** El simétrico: `MAX(t.pnl)` en una cuenta solo
   con pérdidas mostraba "ganancia máxima: −50 $".

Los dos últimos se corrigieron con agregados condicionales (`MAX(CASE WHEN pnl > 0 ...)`),
el mismo patrón que el código ya usaba para `avg_win`. Los tres tienen test de regresión.

#### Infraestructura de test creada
- **Factories nuevas:** `Account`, `TradeAsset`, `PropFirm`, `Program`, `ProgramLevel`
  (la cadena completa que exige `accounts`, cuyas FK a nivel y fase son NOT NULL).
- **`TradeFactory` arreglada:** elegía `account_id` de `[1,2,3,4]` y `trade_asset_id` de
  `[1,2]`, así que solo funcionaba con la BD sembrada. Ahora crea las relaciones al vuelo y
  ofrece estados `win()`, `loss()` y `enteredAt()`.
- **`UserFactory` arreglada:** mismo problema (`role_id => 3`); además se le añadió el estado
  `superadmin()` y se retiró `withPersonalTeam()` (los teams de Jetstream están desactivados
  y el modelo `Team` no existe).
- **`HasFactory`** añadido a `Account`, `TradeAsset`, `PropFirm`, `Program`, `ProgramLevel`
  y `Role`, que no lo tenían.
- **`npm test`** ya existe (`php artisan test`), como pide `CLAUDE.md`.

#### Tests escritos
**24 tests de dominio, 106 aserciones en total.**

`tests/Feature/Accounts/CalculateAccountStatisticsTest.php` — **14 tests**:
cuenta vacía sin división por cero · winrate · breakeven cuenta como perdedor · profit factor
· medias de ganancia/pérdida y su ratio · aislamiento entre cuentas · días de trading como
fechas distintas · caché (TTL, `forceRefresh`, `clearCache`, clave por cuenta) · las tres
regresiones de arriba.

#### `exit_time`: filtro muerto retirado (decisión de Jordi)

`trades.exit_time` es **NOT NULL**: una posición abierta no se puede guardar. Los
`whereNotNull('exit_time')` repartidos por el código filtraban por algo imposible, así que
**se han eliminado los 7**:

| Fichero | Cuántos |
|---|---|
| `app/Actions/Accounts/CalculateAccountStatistics.php` | 2 |
| `app/Actions/Accounts/GenerateBalanceChartData.php` | 1 |
| `app/Actions/Strategy/RecalculateStrategyStats.php` | 2 |
| `app/Livewire/DashboardPage.php` | 2 |

Queda un test (`la base de datos solo admite operaciones cerradas`) que fija esa premisa: si
algún día se hace la columna nullable para soportar PnL flotante, fallará y avisará de que
hay que repasar todas esas consultas, que ahora dan por hecho que todo trade está cerrado.

> **Alcance de la verificación, para que conste:** la retirada está cubierta por tests en
> `CalculateAccountStatistics` (14) y `GenerateBalanceChartData` (10). En
> `RecalculateStrategyStats` y `DashboardPage` la seguridad es **por construcción** (no se
> puede filtrar por `NOT NULL` sobre una columna `NOT NULL`), no por test — esos dos
> ficheros siguen sin cobertura.

#### Siguientes tests (por orden)
- [x] ~~`GenerateBalanceChartData`~~ — **10 tests**: acumulación de PnL, agrupación por día, ventana de timeframes, aislamiento entre cuentas, longitud de series y caché.
- [x] ~~Objetivos de prop firm (`ProgramObjective`)~~ — **26 tests**. Ver §2.2.b.
- [x] ~~`TradeObserver` — invalidación de caché al crear/editar/borrar trade.~~ — **13 tests**. Ver §2.2.c.
- [ ] `CalculateStrategyMetrics` (backtesting).
- [ ] Smoke tests Livewire: cada página monta para usuario free y pro.
- [ ] CI en GitHub Actions con Pint + Pest.

### 2.2.b Objetivos de prop firm — ✅ CUBIERTOS (2026-08-24)

`tests/Feature/Accounts/ObjectivesProgressTest.php` — **25 tests, 69 aserciones** sobre
`Account::getObjectivesProgressAttribute()`, que es lo que decide si una cuenta va en verde
o está quemada. Cada test habilita una sola regla (los demás porcentajes a 0) para que el
fallo señale exactamente qué cálculo se ha roto.

| Regla | Qué se fija |
|---|---|
| **Profit target** | Se mide sobre el **balance cerrado**, no sobre equity (el flotante no cuenta) · progreso cortado en 0 si la cuenta va en pérdidas · el corte `>=` es inclusivo |
| **Max daily loss** | Drawdown = `today_starting_equity − equity actual` · **fallback** sin snapshot = `balance − PnL cerrado hoy`, y los trades de días anteriores quedan fuera · el límite es % del balance **inicial**, no del vivo · nunca negativo en día verde · la pérdida flotante sí cuenta |
| **Max total loss** | Drawdown = `balance inicial − equity` · la pérdida flotante puede quemar la cuenta con el balance intacto · `current_equity` null (cuenta sin sincronizar aún) cae al balance |
| **Min trading days** | Umbral del 0,3% del balance inicial · agrupa por fecha, no por trade · un día en rojo no cuenta |
| **Config / aislamiento** | Sin fase asignada → sin objetivos · solo se evalúan las reglas con porcentaje > 0 · los trades de otra cuenta no contaminan · la divisa se arrastra a cada regla |

#### Lo que ha salido a la luz al cubrirlo (nada roto, pero anotado)

1. **`loss_type` está en la BD y el código lo ignora** — **APARCADO por decisión de Jordi
   (2026-08-24): la columna no la lee nadie, se deja como está.** El enum admite
   `balance_based | equity_based | relative`, todos los seeders lo pueblan a `balance_based`
   y las tres reglas miden **siempre sobre equity**. Para las firmas cuyo drawdown es
   `relative` (trailing sobre el máximo alcanzado) el cálculo directamente no existe.
   Queda un test que **fija la premisa actual a propósito**: si algún día se respeta
   `loss_type`, falla y avisa de que hay que repasar las tres reglas.
   Retomarlo solo si se da de alta una prop firm con drawdown trailing.
2. [x] ~~**`getHardRulesAttribute()` es código muerto y además revienta**~~ — **BORRADO**.
   Nadie lo usaba y con una cuenta sin `currentObjective` lanzaba fatal.
3. [x] ~~**Cast huérfano `max_balance`**~~ — **BORRADO**. La columna no existe en ninguna
   migración.
4. [x] ~~**`min_trading_days` agrupaba por `entry_time`**~~ — **ARREGLADO**: ahora agrupa por
   `exit_time`, igual que el drawdown diario. El PnL se realiza al cerrar, así que un trade
   abierto el viernes y cerrado el lunes cuenta como lunes. Cubierto por el test
   `imputa el día operado a la fecha de cierre, no a la de apertura`, que distingue los dos
   criterios (dos trades de 200 que cierran el mismo día: 1 día válido por cierre, 0 por
   apertura).
5. [x] ~~**El profit target usaba un literal traducido como `type`**~~ — **ARREGLADO, y era
   un bug visible**: `card-objectives.blade.php` compara `$objective['type']` con la clave
   estable `'profit_target'` en un `match`, así que con `__('labels.profit_target')`
   ("Objetivo de Beneficio") **la tarjeta caía siempre en el `default` y salía con el icono
   de la balanza en vez del trofeo**, en los dos idiomas. Ahora `type` es la clave y el
   literal traducido se ha movido al `label`, que es donde se pinta.
6. [x] ~~**`objectives_progress` devolvía `[]` sin objetivo y `Collection` con él**~~ —
   **ARREGLADO**: devuelve siempre `Collection`, con tipo de retorno declarado.
7. Nota de tests: los helpers de Pest son **funciones globales**, así que colisionan entre
   ficheros (`closedTrade` ya estaba tomado por `GenerateBalanceChartDataTest`). Si crecen,
   moverlos a un `tests/Helpers.php`.

### 2.2.c `TradeObserver` — ✅ CUBIERTO Y ARREGLADO (2026-08-24)

`tests/Feature/Observers/TradeObserverTest.php` — **13 tests**. Al escribirlos salieron dos
fallos de verdad, ninguno de ellos de estilo:

1. **`TradeObserver` no invalidaba ninguna caché.** Solo encolaba el recálculo de estrategia.
   Las claves `account_stats_{id}` y `account_chart_{id}_{tf}` (TTL de 5 min) se limpiaban
   **únicamente a mano** en `AccountPage` al editar la cuenta, así que **un sync del `.exe`
   metía trades y el dashboard seguía enseñando hasta 5 minutos de cifras viejas**. Ahora el
   observer las invalida en `created` / `deleted`, y en `updated` solo si cambió una columna
   de la que dependen (`account_id`, `trade_asset_id`, `pnl`, `entry_time`, `exit_time`,
   `duration_minutes`) — editar la nota de un trade ya no tira la caché. Si el trade cambia
   de cuenta, se invalidan **las dos**. Entra dentro de la decisión de "optimizar la caché que
   ya hay": no se añade caché nueva, se arregla la invalidación de la existente.
2. **Mover un trade de estrategia recalculaba dos veces la vieja y nunca la nueva.**
   `updateStrategy()` leía `$trade->strategy`, y esa relación ya venía cargada en memoria
   (la toca el hook `created`), así que devolvía la estrategia anterior al cambio. Se resuelve
   por id con `Strategy::find($trade->strategy_id)`. **La estrategia de destino se quedaba con
   las estadísticas desfasadas.**

Efecto colateral en la suite: dos tests de caché (`CalculateAccountStatistics` y
`GenerateBalanceChartData`) daban por bueno que insertar un trade **no** invalidaba nada —
justo el bug. Ahora insertan con `Trade::withoutEvents()` para medir la caché en sí; la
invalidación la cubre `TradeObserverTest`.

**Además:** `AccountObserver::updated()` filtraba por `wasChanged([... 'login'])`, y la
columna es **`mt5_login`**. Cambiar el número de cuenta MT5 no invalidaba nada. Corregido.
También se ha quitado el `Log::info("Actualizando Estrategia")` que escribía en el log en
cada escritura de trade.

**Factory nueva:** `StrategyFactory` (`Strategy` tenía `HasFactory` pero no factory).

### 2.3 Rendimiento

> **Decisión de Jordi (2026-08-24) sobre la caché:** se **mantiene** la que hay y se
> optimiza si hace falta, pero **no se añaden usos nuevos por ahora**. Los 22 `Cache::`
> viven en 11 ficheros: `CalculateAccountStatistics`, `GenerateBalanceChartData`, los tres
> `Actions/Admin/*`, `AccountPage`, `Admin\PropFirmsManager`, `AiDailyTip`,
> `BacktestingPage`, `AccountObserver` y `AiService`.

- ✅ Ya bien: 22 usos de `Cache::`, 26 `#[Computed]`, invalidación por Observers.
- [ ] **`DashboardPage.php` (1.270 L)** + `dashboard-page.blade.php` (1.815 L) es el mayor foco de deuda. Extraer a Actions (`Dashboard/GetEquityCurve`, `GetPerformanceByHour`, `GetMistakeBreakdown`) siguiendo el patrón que ya usas en `app/Actions/`.
- [ ] Auditar N+1: solo 20 `with()` en 24 componentes Livewire. Activar `Model::preventLazyLoading()` en local y arreglar lo que salte.
- [ ] `#[Lazy]` + skeleton en las secciones pesadas (analytics de backtesting, heatmap del dashboard).
- [ ] Índices de BD: revisar `trades(account_id, closed_at)` y `trades(user_id, status)` — son los filtros de casi todas las consultas.
- [ ] El bundle carga **ApexCharts + lightweight-charts + Trix + flatpickr + mdtimepicker** en `app.js` global. Con DataTables ya lo resolviste bien (import dinámico); aplicar lo mismo a Trix (solo journal), lightweight-charts (solo trade detail) y ApexCharts (solo páginas con gráfico). Ganancia notable en TTI de login/landing.

### 2.4 Arquitectura
- [ ] Componentes Livewire gigantes: `DashboardPage` (1.270), `AccountPage` (797), `SessionPage` (695), `BacktestingPage` (617), `ReportsPage` (614). Regla propuesta: **máx. 250 líneas**; el resto a `Actions/` o traits.
- [ ] Blades gigantes: `dashboard-page` (1.815), `_analytics` (984), `account-page` (957). Partir en parciales — ya lo haces bien en `livewire/backtesting/`, replicar el patrón.
- [ ] Unificar los **4 componentes de fecha** (`Datepicker`, `DatePickerSingle`, `SingleDatePicker`, `TimePicker`): duplicación evidente.
- [ ] Unificar los **6+ componentes de modal** (`modal`, `modal-info`, `confirm-modal`, `confirmation-modal`, `dialog-modal`, `modal-confirmation`, `modals/*`) en un solo `<x-modal>` parametrizable.
- [ ] Form Objects de Livewire: `app/Livewire/Forms/` está infrautilizado; extender a los formularios grandes (trade, cuenta, estrategia).

### 2.5 UX / Producto
- [ ] **Estados vacíos** decentes en todas las tablas (hoy varias muestran tabla vacía sin CTA).
- [ ] **Skeletons de carga** en vez de spinner global.
- [ ] **Atajos de teclado**: `N` nuevo trade, `/` buscar, `D` dashboard.
- [ ] **Exportar CSV/PDF** de trades y del informe de prop firm (seguía pendiente del TODO original).
- [ ] Comparativa entre cuentas (dos cuentas lado a lado).
- [ ] Calendario mensual de P&L (heatmap tipo GitHub por día).
- [ ] Onboarding de 3 pasos para cuenta nueva (crear cuenta → importar trades → primer objetivo).
- [ ] Importador CSV como alternativa manual al `.exe` (cTrader, Bitget, brokers sin terminal MT5).
- [ ] Accesibilidad: contraste en oscuro (`text-gray-400` sobre `gray-900` roza el mínimo AA), `aria-label` en botones-icono del sidebar y del toggle de tema.
- [ ] Responsive: el sidebar de 80px es fijo `inset-y-0`; comprobar comportamiento en móvil real.

### 2.5.c Roadmap heredado del antiguo `TODO.md`

`TODO.md` estaba desfasado (hablaba de Chart.js cuando se usa ApexCharts, y de un sync MT5
que ya funciona de otra forma). Lo que seguía teniendo sentido, rescatado aquí:

**Ya entregado desde entonces:** modelos User/Account/Trade/Asset/Strategy · AccountPage con
métricas y gráfico · factories y seeders · gráfico de evolución de balance · tarjetas de
riesgo/drawdown · responsive · tabla de trades · sincronización MT5 (ahora por API + `.exe`).

**Sigue pendiente:**
- [ ] Estadísticas avanzadas: Sharpe (winrate y profit factor ya están).
- [ ] Notificaciones PWA en móvil. **Nota:** al borrar `NotificationController` en la
      limpieza, Firebase quedó **sin uso en el código** — solo quedan la dependencia
      `kreait/laravel-firebase`, el bloque `firebase` de `config/services.php` y la columna
      `users.fcm_token`. Se han **conservado a propósito** por si se retoma esto; si se
      descarta, son 3 cosas más que borrar.
- [ ] Informe PDF para prop firms (FTMO).
- [ ] APIs de otros brokers (Bitget, Degiro).
- [ ] Caché de balances en Redis.
- [x] ~~Traducciones `menu.labels`~~ — **verificado y arreglado**. Ver §2.5.d.

### 2.5.b Sincronización MT5 — cómo funciona hoy (aclarado por Jordi)

No va por `Mt5Gateway` (eso se retiró): **cada terminal MT5 sincroniza individualmente
contra la web mediante un `.exe`** que empuja a la API. Endpoints vivos en
[routes/api.php](routes/api.php): `/mt5-sync`, `/mt5-reset`, `/mt5-refresh-charts`,
`/mt5-update-chart` → `Mt5SyncController`.

Consecuencia: el reclamo *"Sincronización automática MT4/MT5"* de
[register.blade.php](resources/views/auth/register.blade.php) **es correcto**, no es
publicidad de una feature retirada.

**Autenticación: verificada y correcta.** Los 4 endpoints están fuera de `auth:sanctum`,
pero `Mt5SyncController` valida `sync_token` en cada método contra la columna única
`users.sync_token`, devuelve 401/403 si no cuadra o no hay plan PRO, y comprueba que la
cuenta (`mt5_login`) pertenece al usuario del token. `User::regenerateSyncToken()` permite
rotarlo.

Lo que sí conviene revisar de este flujo:
- [ ] **Rate limiting por token**: hoy no hay `throttle` en estas rutas. Un `.exe` en
      bucle o un token filtrado pueden martillear la API sin freno.
- [ ] El token viaja en el **body** en vez de en una cabecera: acaba en cualquier log de
      request que capture el payload. Considerar `Authorization: Bearer`.
- [ ] Versionado del `.exe` y comportamiento ante un payload de versión antigua.

### 2.5.d Traducciones — ✅ ARREGLADO (2026-08-21)

El bug del antiguo `TODO.md` ("las traducciones `menu.labels` no cargan") **era real**, pero
no donde parecía. Auditando clave a clave contra las vistas:

| Fichero | Antes | Después |
|---|---|---|
| `lang/en/labels.php` | **108 claves ausentes** respecto a `es` | 0 |
| `lang/en/menu.php` | faltaba `backtesting` | 0 |
| `lang/es/*` | 0 ausentes | 0 |

Casi todas las que faltaban eran del **módulo de Backtesting**, que se construyó traducido
solo al español. Un usuario con el idioma en inglés veía literalmente `labels.bt_subtitle`,
`labels.equity_curve_title`, etc., porque `__()` devuelve la clave cuando no la encuentra.

Traducidas las 108 y añadida `menu.backtesting`. **Comprobado con `php artisan tinker`** que
Laravel resuelve las claves en ambos locales, no solo que existan en el fichero.

> Nota metodológica: mi primera auditoría dio "5 claves ausentes en es y 6 en en" — era un
> falso positivo de mi propio regex, que solo capturaba claves con comilla simple cuando en
> estos ficheros se mezclan comilla simple y doble. Si repites la comprobación, usa
> `['\"]` en el patrón.

**Queda pendiente:** revisar si `lang/en/labels.php` tiene claves con traducciones
provisionales que un angloparlante nativo mejoraría; las 108 nuevas son traducción directa.

### 2.6 Seguridad / operación
- [ ] `clockwork` y `laravel-debugbar`: verificar que están desactivados en producción.
- [ ] Rate limiting en los endpoints de IA (`AiService`) además del límite por usuario de `WithAiLimits`.
- [ ] Revisar permisos `check.permission` en rutas admin (hoy solo middleware `superadmin`).
- [ ] Revisar `.env.example` vs `config/services.php` tras el cambio a Grok — que no falte ninguna clave documentada.
- [ ] Política de retención: `app/Models/Log.php` puede crecer sin límite; añadir purga programada.

### 2.7 DX
- [x] ~~Normalización CRLF~~ — **HECHO (2026-08-24)**, commit `4e3a2ae`. El índice ya
      estaba 100% en LF, pero el árbol de trabajo tenía **98 ficheros en CRLF/mixto** y
      `core.autocrlf=true` los reconvertía: de ahí el aviso en cada `git diff`. Ahora
      `core.autocrlf=false` + `core.eol=lf` (manda el `eol=lf` de `.gitattributes`), los 98
      ficheros reescritos desde el índice, y `.gitattributes` declara explícitamente los
      binarios (`exe/ico/png/webp/ttf`…), los lockfiles como `-diff merge=binary` y
      `*.bat|cmd|ps1` en CRLF. Coincide con el `end_of_line = lf` de `.editorconfig`.
- [x] ~~**Commitear el trabajo actual**~~ — los 206 ficheros entraron en `666c37d`.
- [ ] Mensajes de commit descriptivos: `666c37d "Cambios"` agrupa **206 ficheros
      (+4.023 / −8.475)** de tres trabajos distintos (tema oscuro, limpieza de código
      muerto, tests y bugfixes). Decisión de Jordi (24/08): **se deja como está**, no se
      trocea. A partir de aquí, un commit por tema.
- [ ] Añadir `pint.json` y pasar Pint a todo el proyecto. (Pint ya está instalado y con la
      config por defecto pasa limpio sobre los tests nuevos.)

---

## 3. Priorización sugerida

### 🔥 Sprint 1 — Cerrar el tema oscuro
1. `layouts/guest.blade.php` + auth (**bug real**: se pierde el tema al hacer login/logout).
2. Componentes compartidos (FASE B) — desbloquea el resto con poco esfuerzo.
3. Módulo Backtesting (FASE A).
4. Helper `tjTheme` + gráficos ApexCharts + toasts (FASE F) — permite quitar los `!important`.
5. Panel admin (FASE C).

### ⚡ Sprint 2 — Limpieza y estabilidad — ✅ COMPLETO
1. ~~Commitear y normalizar el estado actual del repo.~~ (`666c37d`, `4e3a2ae`)
2. ~~Borrar código muerto (Town, traffic, economic).~~
3. ~~Reescribir README + absorber `TODO.md` en este documento.~~
4. ~~Arreglar el script `npm test` y escribir los tests de dominio prioritarios.~~

### 🛡️ Sprint 2.5 — Reglas de prop firm (en curso)
1. ~~Tests de `ProgramObjective`: daily drawdown, max drawdown, profit target, días mínimos.~~ ✅ §2.2.b
2. ~~Implementar `loss_type`~~ — **aparcado** (24/08). Ver §2.2.b punto 1.
3. ~~Borrar `getHardRulesAttribute()` y el cast huérfano `max_balance`.~~ ✅
4. ~~Unificar `entry_time` / `exit_time` en el conteo de días operados.~~ ✅
5. ~~`TradeObserver`: tests de invalidación de caché.~~ ✅ §2.2.c — dos bugs reales arreglados.
6. **Siguiente:** verificación visual del tema oscuro en navegador.

### 🚀 Sprint 3 — Rendimiento y refactor
1. Trocear `DashboardPage` y su Blade.
2. Imports dinámicos de las librerías pesadas.
3. `preventLazyLoading()` + índices de BD.
4. Unificar modales y date pickers.

---

## 4. Decisiones pendientes
- **Sidebar**: ¿se aclara en tema claro o se queda oscuro fijo como "chrome"?
- **Calendario económico**: ¿se termina o se retira?
- **`welcome.blade.php`** (landing, 1.141 L): ¿entra en el tema oscuro o se queda en claro?
- **Módulo Town / tráfico**: ¿se puede borrar?
