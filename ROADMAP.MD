# TradeForge — Roadmap de Producto

> Derivado del análisis de producto del **2026-08-26**
> ([artefacto](https://claude.ai/code/artifact/8cec3540-1e46-4c77-bb8d-0bb7537fda0d)).
> `plan.MD` cubre la deuda técnica; **este documento cubre el producto**: captación,
> activación, retención, monetización y diferenciación.
>
> **Método de trabajo:** antes de abrir cada fase se afinan sus decisiones con Jordi.
> Nada se implementa sin que el bloque de "Decisiones" de esa fase esté cerrado.

---

## Índice de fases

| # | Fase | Ideas | Estado |
|---|---|---|---|
| 1 | Abrir la puerta | C1 · C2 · M2 | ✅ **Completa** (2026-08-26) |
| 2 | Dejar entrar los datos | A1 · A2 · A3 · A4 · A5 · A6 | ✅ **Completa** (2026-08-26) |
| 3 | Cuadrar la oferta | M1 · M3 · M5 · M6 | ✅ **Completa** (2026-08-26) |
| 4 | Dar motivo para volver | R1 · R2 · R3 · R5 · (R4 parcial) | ✅ **Completa** (2026-08-27) |
| 5 | Hablar en R | **P2 ✅** · **P7 ✅** · **P10 ✅** | ✅ **Completa** (2026-08-27) — con P1 revertido y aparcado |
| 6 | Clavar la diferencia | **P8 ✅** · **P5 ✅** | ✅ **Completa** (2026-08-28) — con P3 retirado, y P4 y P11 aparcados |
| 7 | Captación de largo recorrido | C3 · C4 · C5 · C6 · P12 | ⚪ Pendiente |
| 8 | Móvil | F7 · R6 | ⚪ Pendiente (decisión previa) |

Regla de oro del orden: **no se pule lo que nadie puede ver todavía.** Cada fase hace
que la siguiente rinda más.

---

## FASE 1 — Abrir la puerta ✅

> **Cerrada el 2026-08-26.** Decisiones tomadas por Jordi: reescribir
> `welcome.blade.php`, demo con usuario real de solo lectura, mantener Free + PRO
> (el plan Prop se queda para la Fase 3) y muro PRO con la pantalla real difuminada.
>
> Entregado, con 27 tests nuevos y la suite completa en verde (217 pasan, 4 se saltan):
>
> - **Landing** — `welcome.blade.php` reescrito (héroe, problema, 6 módulos, prop
>   firms, auditor IA, 15 errores, demo, precios, FAQ, cierre), bilingüe desde
>   `lang/{es,en}/landing.php`, con tema claro/oscuro. `/` ya no redirige a login.
> - **Layout público** — `layouts/public.blade.php` + `App\View\Components\PublicLayout`,
>   con Open Graph, canonical, selector de idioma y `<x-theme-toggle>` (extraído del
>   navigation-menu para poder reutilizarlo).
> - **Precios públicos** — `/pricing` fuera del grupo `auth`, con `<x-pricing-table>`
>   compartida por landing, `/pricing` y el bloque de suscripción de la app. Fuente
>   única de la matriz de planes en `landing.pricing.features`.
> - **Demo** — `/demo` y `/demo/salir`, `DemoGuard` (cancela toda escritura de
>   Eloquent y bloquea las subidas de fichero), `DemoSeeder` determinista con 3
>   cuentas y 324 operaciones, `php artisan demo:refresh`, banner fijo en el layout
>   de la app. La IA y el checkout de Stripe están cortados en demo.
> - **Muro PRO visible** — `<x-pro-gate>` en los 6 módulos de pago: pantalla real
>   difuminada + tarjeta de venta con enlace a la demo. Sidebar con candado en vez
>   de esconder los ítems.
>
> **Correcciones colaterales:** `Settings\Subscription` usaba `env()` en `mount()`
> (con la config cacheada en producción el checkout se creaba con un *price* vacío)
> y no validaba que hubiera *price id*.
>
> **Las dos cosas que quedaron anotadas aquí se resolvieron en la Fase 3:** el tope
> de cuentas ya depende del plan (`User::accountLimit()`) y los módulos PRO están
> cortados en servidor con el trait `RequiresProAccess`, no solo escondidos.

### Lo previsto originalmente

> **Problema:** F3. Hoy solo se entra por el `.exe` de MT5 (además PRO) o tecleando a mano.
> **Métrica de éxito:** un trader de cualquier plataforma tiene sus 400 operaciones dentro
> en menos de cinco minutos.

- **A1 · Importador CSV universal.** Subida → detección de cabeceras → mapeo de columnas
  con vista previa → deduplicación por ticket/posición → informe de importación.
  Presets: informe HTML de MT4/MT5, cTrader, DXtrade, Match-Trader, TradingView, Binance,
  Bybit, IBKR. Perfil de mapeo guardado por usuario.
- **A2 · Datos de ejemplo.** Reutiliza `DemoSeeder` de la Fase 1 sobre la cuenta del
  usuario, con aviso persistente y borrado en un clic.
- **A3 · Onboarding de 3 pasos.** Crear cuenta → importar/conectar → primer objetivo.
  Barra de progreso persistente y evento por paso para medir abandono.
- **A4 · Estados vacíos** con explicación, miniatura y CTA en todas las tablas.
- **A5 · Alta rápida en una línea** + atajos `N`, `/`, `D`.
- **A6 · Página de conexión MT5** con estado en vivo, versión del `.exe`, última
  sincronización y botón de prueba con error legible.

**Decisiones a cerrar:** ¿el importador es Free o PRO? ¿qué formatos entran en la primera
tanda? ¿el importador crea la cuenta si no existe?

---

## FASE 3 — Cuadrar la oferta ✅

> **Cerrada el 2026-08-26.** Decisiones de Jordi: **solo dos planes** (Free y PRO
> 9,99 €, M4 descartado), prueba de **14 días sin tarjeta**, **Free 3 · PRO
> ilimitadas**, al degradar **no se toca nada** y contador de IA visible con los
> mismos límites.
>
> Entregado, con 33 tests nuevos (321 pasan, 4 se saltan) y `npm run build` OK:
>
> - **`User::hasProAccess()` como única puerta.** La condición estaba repetida en
>   dieciséis sitios como `subscribed('default')`; olvidarse de uno al añadir la
>   prueba habría dejado al usuario con medio producto cerrado. Ahora dentro caben
>   la suscripción y la prueba, y fuera nadie distingue.
> - **M1 · Prueba de 14 días sin tarjeta.** Se apoya en `users.trial_ends_at`, que
>   ya traía Cashier, y no toca Stripe. Se marca en `CreateNewUser`, se anuncia en
>   la pricing y se ve en la barra con los días restantes, en ámbar en los tres
>   últimos. `TRIAL_DAYS=0` la desactiva.
> - **M3 · Límite de cuentas por plan.** `config/billing.php`: Free 3, PRO sin
>   tope. Las quemadas no cuentan. Al degradar no se borra ni se oculta nada: el
>   límite solo frena las cuentas nuevas. La pricing pasa a decir «Ilimitadas» en
>   PRO, que por fin es verdad.
> - **Gating PRO en servidor** (arrastrado de la Fase 1). El trait
>   `RequiresProAccess` corta en el `boot()` de los seis componentes de pago,
>   distinguiendo el render inicial —la vista previa difuminada, permitida— de
>   cualquier acción posterior, que devuelve 403.
> - **M5 · Contador de IA visible** en la barra, con el total del plan en el
>   tooltip. El cálculo se mueve a `User` para que el contador y los componentes
>   no puedan dar cifras distintas.
> - **M6 · Autopsia de la cuenta quemada.** Al quemar una cuenta se enseña qué
>   error costó más **dinero** (no cuál se repitió más) y se ofrece PRO. Una sola
>   vez por cuenta y solo durante el mes siguiente. El descuento solo se anuncia
>   si hay `RECOVERY_COUPON` configurado: nunca se promete una rebaja que el
>   checkout no vaya a aplicar.
>
> **Anotado, no hecho:** los usuarios ya existentes no reciben prueba
> (`trial_ends_at` sigue a null). Si se quiere dársela, es un comando de una línea.

### Lo previsto originalmente

> **Problema:** F4 (la pricing promete lo que el código no da) y la ausencia de prueba.

- **M3 · Arreglar el límite de cuentas.** Hoy `AccountPage::insertAccount()` corta en 3 sin
  mirar la suscripción, mientras la pricing anuncia "Cuentas Ilimitadas". Límite por plan
  en configuración, y el tope convertido en momento de venta ("3 de 3 — amplía con PRO").
- **M1 · Prueba PRO de 14 días** al registrarse, aviso a los 3 días del final y
  degradación suave conservando datos.
- ~~**M4 · Tercer plan.**~~ **DESCARTADO (2026-08-26, decisión de Jordi):** la oferta
  se queda en **dos planes, Free y PRO 9,99 €**. Al plantearlo salió además que hoy
  un plan Prop no tendría con qué diferenciarse: el informe PDF es de la Fase 5 (P10)
  y la comparativa entre cuentas de la Fase 6 (P9).
- **M5 · Créditos de IA entendibles:** contador visible, qué consume cada análisis,
  recarga por invitación, paquete suelto.
- **M6 · Descuento "segundo intento"** al marcar una cuenta como quemada, junto al informe
  de qué salió mal.

**Decisiones a cerrar:** precios definitivos y qué entra exactamente en cada plan; prueba
con o sin tarjeta; qué pasa con las cuentas por encima del límite al degradar.

---

## FASE 4 — Dar motivo para volver ✅

> **Cerrada el 2026-08-27.** Decisiones tomadas por Jordi: entran **R1, R2, R3 y R5**
> más la **base de PWA**; **Firebase se elimina** y el push queda para cuando haya
> volumen; el correo sale **el domingo a las 18:00 en la hora del usuario, activado
> por defecto** y con baja en un clic; el **ritual convive** con el panel en vez de
> secuestrar la entrada; el **email es gratuito y la revisión guiada es PRO**; las
> **tres rachas** van en el navbar con su detalle en el panel; y del scheduler se
> entrega **código y documentación**, sin tocar configuración de servidor.
>
> Entregado, con 75 tests nuevos (396 pasan, 4 se saltan) y `npm run build` OK:
>
> - **Scheduler montado.** `routes/console.php` pasa de tener solo `inspire` a
>   programar `resumen:semanal` cada hora y `demo:refresh --force` cada noche. El
>   README documenta la línea de cron y la unidad de systemd del worker de cola;
>   la instalación en el servidor la hace Jordi.
> - **R1 · Resumen semanal** (`App\Actions\Retention\BuildWeeklySummary`,
>   `WeeklySummaryMail`, comando `resumen:semanal`): resultado y acierto de la
>   semana, comparación con la anterior, mejor y peor operación, errores repetidos
>   con lo que costaron, reglas incumplidas, mejor franja horaria y enlace a la
>   revisión. El comando corre **cada hora** y escribe solo a quien en ese momento
>   tiene su domingo a las 18:00 locales; `weekly_summary_sent_at` impide repetir.
>   No se envía si la semana no tuvo ni operaciones ni diario. Baja desde el propio
>   correo con enlace firmado, sin necesidad de sesión.
> - **R2 · Revisión semanal guiada** (`/revision-semanal`, PRO): tres mejores y tres
>   peores operaciones, tres preguntas fijas por cada una, conclusión de la semana y
>   archivo comparable. Al cerrarla se **congelan** las métricas de esa semana, así
>   que reclasificar una operación meses después no reescribe la historia.
> - **R3 · Rachas** (`CalculateStreaks`): diario escrito, días sin error grave y
>   semanas con el plan cumplido. Tres chips en el navbar y una tarjeta con el
>   calendario del mes en el panel. Cada definición está en su texto de ayuda y hay
>   un test por regla; las cuentas de ejemplo quedan fuera para no regalar rachas.
> - **R5 · Ritual pre-mercado**: tarjeta descartable en el panel y modal de tres
>   pasos (cómo llegas · reglas de hoy · qué vigilas) que escribe en la entrada de
>   diario del día, los mismos campos que usa la pantalla de diario.
> - **R4 · PWA sin push**: manifiesto traducido servido por ruta, iconos generados
>   (incluido el *maskable*), service worker propio y pantalla `/sin-conexion`. El
>   service worker **no cachea HTML ni JSON** de la aplicación a propósito: en un
>   ordenador compartido eso dejaría las operaciones de un usuario al siguiente.
> - **Firebase fuera.** Eliminados `kreait/laravel-firebase`, `config/firebase.php`,
>   el bloque de `config/services.php`, la variable de entorno y la columna
>   `users.fcm_token`. No tenía ni un solo consumidor.
> - **Columnas nuevas en `users`:** `timezone`, `locale`, `weekly_summary` y
>   `weekly_summary_sent_at`. El idioma solo vivía en la sesión, y el correo se
>   compone desde la cola, donde no hay sesión ninguna. Se guardan desde una tarjeta
>   nueva del perfil (que además propone el huso que detecta el navegador) y el
>   selector de idioma también los deja escritos.
>
> **Anotado, no hecho:**
> 1. **R6 · Diario rápido móvil** se va a la Fase 8, donde ya estaba duplicado: sin
>    la vista móvil real de esa fase, un diario rápido en el móvil se queda a medias.
> 2. **Push.** La PWA es instalable, pero no notifica. Cuando toque, Web Push nativo
>    con VAPID; Firebase ya no está de por medio.
> 3. **El resumen no distingue divisa:** suma el P&L de todas las cuentas en dólares.
>    Con cuentas en euros y en dólares a la vez, la cifra mezcla. Sigue pendiente:
>    el sistema R, que era la salida prevista, se descartó.

### Lo previsto originalmente

> **Problema:** F5. Hoy `routes/console.php` no programa nada: cero mecanismos de retorno.

- **R1 · Resumen semanal por email** (domingo): operaciones, R, errores repetidos, reglas
  rotas, mejor franja horaria, enlace a la revisión guiada. Requiere scheduler + cola.
- **R2 · Revisión semanal guiada:** la app elige 3 mejores y 3 peores y hace 3 preguntas
  por operación. Resultado archivado y comparable semana a semana.
- **R3 · Rachas y disciplina en cabecera:** días con diario escrito, días sin error grave,
  semanas cumpliendo el plan. Se apoya en los pesos de `mistakes` que ya existen.
- **R4 · PWA + push** (Firebase ya está a medio poner: queda `users.fcm_token` sin
  consumidor): apertura de sesión, aviso tras dos pérdidas seguidas, recordatorio de diario.
- **R5 · Ritual pre-mercado de 60 s** como pantalla de entrada matinal.
- **R6 · Diario rápido móvil:** emoción + nota + foto, asociado a la última operación.

**Decisiones a cerrar:** proveedor de email y frecuencia; ¿el ritual pre-mercado sustituye
al dashboard como home o convive?; ¿se retoma Firebase o se va a Web Push nativo?

---

## FASE 5 — Hablar en R ✅ (con P1 aparcado)

> **P1 revertido el 2026-08-27, decisión de Jordi.** Se implementó entero y se
> retiró entero el mismo día. El motivo no fue el código sino el dato: el agente
> de MetaTrader manda **el nivel que disparó el cierre**, no el que había al
> abrir (`sl_source` llegaba siempre como `triggered`). Con eso, una operación
> cerrada en objetivo nunca trae stop, así que solo las perdedoras podían tener
> R: 11 de 338 operaciones, un 3% de cobertura y con el peor sesgo posible.
> El conmutador del navbar vuelve a ser `$ / %`.
>
> Retirado: la migración de riesgo (`stop_loss`, `take_profit`, `risk_amount`,
> `reward_amount`, `risk_source`, `open_reason`, `close_reason`, `sl_source`,
> `tp_source`, `accounts.default_risk_percent`), `App\Actions\Risk\*`, el panel
> del Laboratorio, el asistente de relleno de cuentas, el modo R del conmutador,
> los campos del formulario, los alias del importador y sus 36 tests.
>
> **Conservado**, porque no era R: el endpoint acepta `notes` del agente y lo
> copia **solo al crear**, para que una resincronización no machaque lo que el
> usuario haya escrito en la operación.
>
> **Si alguna vez se retoma**, el orden correcto es el inverso al que se siguió:
> primero conseguir que el agente mande el SL **de la orden de entrada** para
> toda posición cerrada, comprobar la cobertura real sobre el histórico, y solo
> entonces construir encima. Sin eso, cualquier métrica en R mide una muestra
> compuesta únicamente por operaciones perdedoras.

> **P2 cerrado el 2026-08-27.** Decisiones tomadas por Jordi: el coste es
> **contrafactual** (lo que habrías ganado sin esas operaciones), vive en
> **portada y Laboratorio**, y la falta de etiquetado se ataca con una **cola de
> repaso** además de declarar la cobertura.
>
> Entregado, con 22 tests nuevos (428 pasan, 4 se saltan):
>
> - **`App\Actions\Mistakes\CalculateMistakeCost`** — un solo sitio calcula el
>   coste, y las dos pantallas lo llaman. Devuelve el coste total, el desglose por
>   error y por franja horaria, y siempre la cobertura.
> - **El número es una resta que se puede comprobar**: P&L real frente a P&L sin
>   las marcadas. Una operación marcada que salió ganadora **resta** al coste,
>   porque el error que a veces sale bien es el que más cuesta dejar.
> - **Tarjeta en portada**, bajo las rachas, con los tres errores más caros y la
>   cobertura pegada al número.
> - **Sección en el Laboratorio**: desglose completo con media por operación,
>   reparto por franja horaria, y el escenario **«quitar las operaciones con estos
>   errores»** integrado en el simulador que ya existía (`exclude_mistakes`).
> - **Cola de repaso** (`MistakeReviewQueue`): las perdedoras sin repasar en lista,
>   con los errores a un clic. Incluye **«sin errores»** a propósito: si la única
>   salida fuera etiquetar, el usuario se inventaría un error y el número mediría
>   ruido. Nueva columna `trades.mistakes_reviewed_at`.
>
> **Advertido en la interfaz, no escondido:** una operación con dos errores cuenta
> entera en los dos, así que los desgloses no suman el total. Repartir el coste
> entre ellos sería inventarse una proporción.
>
> **Corregido de paso:** el `select()` del Laboratorio no traía la columna nueva,
> así que portada y Laboratorio daban coberturas distintas sobre las mismas
> operaciones. Hay un test que compara las dos pantallas.

> **P7 cerrado el 2026-08-27.** Decisiones tomadas por Jordi: las reglas viven en
> **tabla propia con procedencia**, hacen **checklist y aviso en vivo**, salen de
> los **cuatro hallazgos que los datos aguantan**, y el **ámbito se elige** al
> adoptarlas.
>
> Entregado, con 20 tests nuevos (451 pasan, 4 se saltan):
>
> - **`DetectFindings`** convierte los datos en frases que se pueden cumplir:
>   error más caro, franja horaria que da dinero en contra, operación del día a
>   partir de la cual todo se tuerce, y día de la semana caro. **Muestra mínima de
>   12 operaciones** y cada frase declara sobre cuántas se calcula — es la lección
>   de P1 escrita en el código, no en un comentario.
> - **`trading_rules`**: texto, ámbito (cuenta o global), tipo, activa, y **de qué
>   hallazgo salió con qué cifra y qué muestra**. Sin eso, dentro de un mes «no
>   operar después de las 13:00» es una manía.
> - **`AdoptFinding`** baja la regla al `trading_plan` de la cuenta cuando el
>   semáforo sabe vigilarla (tope de operaciones, ventana horaria). Una regla
>   global **no** toca ningún plan: escribiría un horario en cuatro cuentas a la
>   vez pisando lo que el usuario puso a mano.
> - **Panel en el Laboratorio** con los hallazgos, el diálogo de adopción con el
>   texto editable, y «Mis reglas» con la procedencia a la vista.
> - **Sesión en vivo**: las reglas se suman al checklist de la estrategia, y el
>   semáforo bloquea el botón de operar cuando se rompen. El día de la semana no
>   cabe en el plan actual, así que se comprueba desde la propia regla.
>
> **Deliberadamente fuera:** marcar el error automáticamente en la operación que
> rompió la regla. La máquina no sabe si has entrado por FOMO; eso lo dice el
> checklist y lo decides tú.

> **P10 cerrado el 2026-08-27.** Decisiones tomadas por Jordi: el CSV baja **las
> operaciones que hay en pantalla con sus filtros**, el PDF lo genera **dompdf
> dentro de la petición**, el informe **compone las acciones que ya existen**, el
> **CSV es gratuito y el PDF es PRO**, y **la demo exporta** con sello.
>
> Entregado, con 37 tests nuevos (488 pasan, 4 se saltan):
>
> - **`ExportTradesCsv`** reutiliza la misma query que pinta `/trades`. No hay una
>   segunda lista de filtros que mantener, así que el fichero y la tabla no pueden
>   discrepar. Va por lotes y en streaming: un histórico de prop firm son miles de
>   filas y construirlas en memoria era convertir la descarga en un error 500.
> - **Separador y decimales por idioma** (`;` y coma en español, `,` y punto en
>   inglés) más BOM UTF-8. Excel usa el separador de lista del sistema: con coma,
>   un CSV español se abre entero dentro de la primera columna.
> - **`BuildMonthlyReport`** no calcula nada propio: orquesta
>   `CalculateDashboardMetrics`, `CalculateMistakeCost` y el nuevo
>   `CheckRulesCompliance`. Hay un test que compara sus cifras con las del
>   dashboard para el mismo mes.
> - **La comparativa es contra el mes natural anterior**, no contra los últimos 31
>   días, que es lo que devuelve la comparativa del dashboard. En un informe
>   mensual «el mes pasado» tiene que ser el mes pasado.
> - **`CheckRulesCompliance`** cuenta cuántas veces se rompió cada regla adoptada:
>   el tope de operaciones por días, la ventana horaria y el día evitado por
>   operaciones. Un recordatorio que la máquina no puede comprobar sale **sin
>   número**, no con un 100 % inventado.
> - **PDF con marca** (`exports.monthly-report`): portada con cuenta y fase,
>   métricas con comparativa, curva de capital en SVG, calendario del mes, coste
>   de los errores con su cobertura y tabla de reglas. Logotipo incrustado en
>   base64 para no depender de rutas ni de dónde esté servida la aplicación.
> - **Una cuenta quemada se informa si la eliges**, aunque el dashboard la deje
>   fuera: es justo la que hay que repasar. `DashboardTradeQuery` gana un
>   `includeBurned` que por defecto no cambia nada.
> - **La demo exporta**: solo lee, no escribe ni envía. El CSV lleva un aviso en la
>   primera línea y el PDF va sellado como DEMO.
>
> **Por qué en la petición y no en cola:** hoy no hay ningún worker garantizado en
> producción (sigue en *Pendiente para producción*). Un informe que se encola y no
> llega nunca es peor que un botón que tarda tres segundos.
>
> **Deliberadamente fuera:** exportar el diario y las sesiones. El CSV de
> operaciones es lo que pide una prop firm y lo que come cualquier hoja de
> cálculo; meter tres ficheros en un ZIP triplicaba la superficie a cambio de un
> caso de uso que nadie ha pedido todavía.

### Lo previsto originalmente

> **Problema:** F6. Sin stop planificado ni riesgo no hay múltiplo R, y sin R las métricas
> no se comparan entre cuentas ni con el resto del sector.

- **P1 · Sistema R.** Migración: `stop_loss`, `take_profit`, `risk_amount` (o `risk_percent`)
  en `trades`. Derivados: R real, esperanza en R, distribución de R, R:R planificado vs
  conseguido, coste en R de mover el stop. Retro-relleno opcional desde MAE/MFE para el
  histórico. Selector € / R global en todos los informes.
- **P2 · "Tus errores te han costado X €".** Un número en portada por periodo: el P&L sin
  las operaciones marcadas con cada error, desglosado por error y franja. Extiende los
  escenarios del Laboratorio con "sin trades con el error X".
- **P7 · De hallazgo a regla:** botón "convertir en regla" en cada conclusión del
  Laboratorio → pasa al `TradingPlan` de la cuenta y al checklist de la sesión en vivo.
- **P10 · Exportar:** CSV completo e informe mensual en PDF con marca.

**Decisiones a cerrar:** ¿riesgo en importe o en porcentaje de la cuenta? ¿qué hacemos con
las miles de operaciones históricas sin stop? ¿R sustituye al euro o convive con conmutador?

---

## FASE 6 — Clavar la diferencia ✅ (con P3 retirado; P4 y P11 aparcados)

> **Cerrada el 2026-08-28.** Decisiones tomadas por Jordi antes de abrirla:
> entraban **P3 → P8 → P5** y en ese orden —P3 se retiró el mismo día que se
> entregó, ver abajo—; **P4 se aparca** porque no hay una sola
> imagen que analizar; se **vacía la cola de velas y se siembra la demo** para que
> el reproductor se vea en el escaparate; **P11 se aparca con criterio escrito** y
> **P12 se mueve a la Fase 7**.
>
> Entregado, con 74 tests nuevos (**562 pasan, 4 se saltan**).

### El conteo que cambió el plan

Antes de recomendar nada se contaron las filas, y el resultado movió la fase:

| dato | Jordi (id 1) | demo (seeder) |
|---|---|---|
| operaciones | 22 | 324 |
| `chart_data_path` | **0** | **0** |
| `screenshot` | **0** | **0** |
| activos distintos | 1 (EURUSD) | 6 |
| estrategias asignadas | 0 | 3 |
| marcas de error | 7 (1 tipo) | 99 (12 tipos) |

**El cero de `chart_data_path` era un falso cero.** Había **59
`StoreTradeChartJob` atascados** en `jobs` desde el 2026-08-25, con 0 fallidos:
nunca había corrido un worker. Al decodificar los payloads, apuntaban a 49
operaciones de las que **22 seguían vivas — exactamente las de Jordi**, cada una
con cinco marcos temporales (1m: 126 velas · 5m: 97 · 15m: 96 · 1h: 84 · 4h: 49)
y marcadores de entrada y salida. Un `queue:work` las llevó de 0 a **22 de 22**.

El cero de `screenshot` sí era real y absoluto: nunca se ha subido una imagen.

Otros dos hallazgos del conteo:

- **`account_daily_metrics` está vacía y sin nadie que la escriba** (solo existen
  el modelo y la migración, de enero). P3 la necesitaba para el drawdown diario,
  así que la fase se construyó sobre el resultado **cerrado por día** derivado de
  `trades`, y lo dice en pantalla en vez de aparentar que vigila el flotante.
- Los marcadores de las velas traen **solo IN y OUT, ni SL ni TP**. Confirma que
  P1 sigue aparcado con razón: el `.exe` no manda el stop de la orden de entrada.

---

### P3 · Centro de mando del challenge — RETIRADO EL MISMO DÍA

> **Construido y retirado el 2026-08-28, decisión de Jordi.** Es el segundo caso
> después de P1, y por un motivo parecido: no fallaba el cálculo, fallaba lo que
> se podía enseñar con el dato que hay.
>
> **Lo que se vio al abrirlo con una cuenta real.** La cuenta de Jordi tiene 7
> días operados de los 20 que exige la muestra mínima, así que la simulación
> —lo único que aportaba la pantalla— se callaba. Lo que quedaba en pantalla
> eran las cuatro reglas de la fase… que **`/cuentas` ya enseña** desde siempre
> vía `Account::getObjectivesProgressAttribute()`:
>
> | Centro de mando | `/cuentas` |
> |---|---|
> | Falta para el objetivo: 800 | profit_target 0 / 800 |
> | Días rentables: 3 de 3 | min_trading_days 3 / 3 |
> | Margen hasta romper: 919,86 | max_total_loss 80,14 / 1000 |
> | Límite diario: 500 | max_daily_loss 0 / 500 |
>
> Las mismas cuatro reglas dichas al revés, más un aviso de «vuelve en trece
> días». Una pantalla entera para repetir la de al lado.
>
> **Retirado:** la ruta `/challenge`, `App\Livewire\ChallengePage`, sus dos
> vistas, `lang/{es,en}/challenge.php`, las entradas de menú, la entrada del
> carril lateral y sus 9 tests de pantalla.
>
> **Conservado:** `SimulateChallenge` y `BuildDailyResults` con sus **17 tests**,
> porque el cálculo está bien y verificado — sobre la demo, con 75 días operados,
> daba 92,7 % de pasar, mediana de 10 días y fecha estimada. Los dos llevan en su
> cabecera el aviso de que hoy no los llama nadie.
>
> **Si se retoma, que sea como una tarjeta dentro de `/cuentas`** que aparezca
> solo cuando haya muestra suficiente, nunca como pantalla propia. Y sin bajar el
> mínimo de 20 días: con siete, la probabilidad es un número inventado con
> aspecto de estimación, que es exactamente lo que costó P1.
>
> **La lección, escrita para no repetirla una tercera vez:** antes de darle
> pantalla propia a algo, comprobar **qué se ve en ella con los datos que hay
> hoy**, no con los que habrá. Conté los 6 días al empezar la fase y aun así le
> puse casa; el conteo no sirve de nada si no se aplica a la decisión siguiente.

Lo que se construyó, para quien lo retome:

- **`BuildDailyResults`** — el resultado cerrado por día de una cuenta, agrupado
  por `exit_time` igual que el semáforo de la cuenta y los días rentables. Si
  agrupara por entrada, las dos pantallas dirían cosas distintas del mismo día.
- **`SimulateChallenge`** — Monte Carlo por *bootstrap* sobre los propios días de
  la cuenta: 2.000 recorridos que terminan en pasar, romper el límite diario,
  romper la pérdida total o agotar el horizonte. Devuelve probabilidad de cada
  desenlace, mediana de días operados hasta pasar, ritmo semanal real y fecha
  estimada.
- **Muestra mínima de 20 días operados**, declarada. Por debajo no sale ninguna
  probabilidad: es la lección de P1 aplicada al número con el que alguien decide
  cuánto arriesga mañana. Lo que sí se sigue enseñando siempre es lo exacto
  —cuánto falta para el objetivo, días rentables, margen hasta romper, límite
  diario—, porque son restas, no estimaciones.
- **La semilla es el id de la cuenta**: dos cargas seguidas dan el mismo número.
  Sin eso, recargar la pantalla cambiaría la probabilidad y nadie sabría a cuál
  hacer caso.
- **Cuenta fondeada** (sin objetivo de beneficio): se mide **supervivencia**, no
  fecha de paso. Dar una fecha ahí sería inventarse un hito que la fase no tiene.

**Advertido en pantalla, no en una nota al pie:** esto mide el día ya cerrado y
**no vigila el flotante** —se puede romper el límite diario en una posición
abierta sin que aparezca—, y la pérdida total se mide contra el balance inicial,
así que para un programa con drawdown móvil la estimación es optimista.

**No se hizo, a propósito:** ninguna cuenta atrás de fase. `accounts.end_date`
significa «cuándo terminó la cuenta», no «hasta cuándo tienes»; el esquema no
guarda ninguna fecha límite y fabricarla habría sido prometer un plazo inventado.

### P8 · Reproductor de la operación ✅

Dentro del modal de la operación, sobre el visor que ya existía.

- **`TradeChartController` reescrito alrededor de un único `paint(upTo)`**: la
  vista completa es ese método con todas las velas y el reproductor es el mismo
  método con un cursor. No hay dos caminos capaces de pintar cosas distintas.
- **Controles**: entrar y salir, atrás/adelante barra a barra, reproducir y
  pausar, barra de progreso arrastrable y velocidades 1× a 8×. Cambiar de marco
  temporal a media reproducción resincroniza el cursor.
- **MAE y MFE dibujados en la barra que los tocó**, no desde el principio: se
  busca la primera vela que alcanza cada nivel (mínimo o máximo según dirección)
  y la línea aparece ahí. Enseñarlas desde el minuto uno sería contar el final de
  la película al empezar.
- El evento `trade-selected` viaja ahora con `mae` y `mfe`. Una operación sin
  excursión guardada (las importadas por CSV) se abre igual, solo que sin esas
  dos líneas.

**Velas en la demo.** `SampleChartGenerator` genera el payload en el mismo
formato que manda el agente y el `DemoSeeder` lo cuelga de las **40 operaciones
más recientes**. Lo que se sube **no es mercado**: es un camino inventado que pasa
por la entrada, la salida, el MAE y el MFE que ya guarda la operación. Solo se usa
con el usuario de la demo. De paso, `demo:refresh` ahora borra los ficheros del
usuario de demo anterior en R2, que hasta hoy se quedaban huérfanos.

**Fuera de alcance, declarado:** la exportación a GIF. Alimenta C3, que es Fase 7,
y arrastra una dependencia de codificación de vídeo que no toca resolver aquí.

### P5 · Mentor con memoria ✅

Pantalla nueva en `/mentor` (PRO) y memoria dentro del auditor.

- **`BuildTraderProfile`** — seis meses de historial: qué error se repite, cuánto
  ha costado, cuántas veces va este mes y si la tendencia es a mejor o a peor.
  La tendencia se compara **por frecuencia cada cien operaciones**, no por número
  suelto: un mes con la mitad de operaciones tiene menos marcas sin que nadie haya
  mejorado nada. **Muestra mínima de 12 marcas** y la **cobertura viaja siempre**.
- **`ProposeMonthlyGoal`** — **una sola cosa al mes**, sacada del mes anterior
  completo (el número que el usuario reconoce, no la media del semestre): bajar de
  N veces a la mitad. Si el mes de referencia tiene menos de 10 operaciones o el
  error no apareció en él, **no se propone nada** y se explica por qué.
- **El objetivo se elige como recurrente, no como caro**: un error de una sola vez
  que costó 900 € no se corrige con un hábito, y el objetivo del mes es un hábito.
- **`improvement_goals`** guarda el objetivo **congelado**: la frase tal cual se
  enseñó, de cuántas veces se venía, a cuántas se apuntó y sobre qué muestra. Uno
  por usuario y mes, con índice único.
- **`TrackMonthlyGoal`** cuenta lo que va del mes y **avisa en cuanto se rompe**,
  sin esperar al día 30. Al entrar en la pantalla se cierran los meses vencidos
  como cumplido o no cumplido, y el cierre **no se deshace**: un objetivo que se
  puede reabrir para que salga bien no significa nada.
- **Memoria en el auditor**: `BuildTradeAuditContext` añade un bloque de perfil
  con los tres errores recurrentes y el objetivo del mes, y el prompt pide
  relacionar la operación con ellos en una frase. Es lo que convierte N auditorías
  sueltas en un mentor. Una operación sin cuenta detrás dice «sin historial
  suficiente» en vez de fabricar un perfil vacío.

**No se hizo, a propósito:** tarjeta del objetivo en la portada. El mentor tiene
su entrada en el carril lateral; meterlo también en el panel es ruido hasta que
haya varios meses cerrados que enseñar.

---

### Pasada de usabilidad del repaso de errores ✅

> **2026-08-28, a raíz de probar el Mentor.** El aviso de «faltan marcas» mandaba
> a `/trades`, donde no se puede etiquetar. Y al mirarlo salió el problema de
> fondo: el repaso semanal era impracticable.

El diagnóstico, con el código delante:

- La fila de la cola traía día, símbolo, dirección, PnL, horas, precios, lotes y
  cuenta — pero **no el gráfico**, que es lo único con lo que se reconoce una
  operación de hace cinco días. Verlo obligaba a abrir un modal, mirarlo,
  cerrarlo y volver a los chips: un viaje de ida y vuelta por operación, con 71
  pendientes en la demo.
- Había **dos formas distintas de etiquetar lo mismo**: los chips de la cola y el
  `MistakeSelector` del detalle, que además es el bueno porque trae sugerencias
  sacadas de los datos.
- El modal ya recibía la cola como contexto, así que las flechas navegaban por
  ella, pero dentro no había «sin errores» ni «guardar y siguiente»: no se podía
  vaciar la cola desde ahí.

Lo entregado, con las decisiones de Jordi:

- **`/repaso`** (`ReviewPage`): una operación por pantalla, el gráfico arriba
  ocupando el ancho, la ficha en una línea, el selector debajo y cuatro botones
  —atrás, saltar, sin errores, guardar y siguiente—. Contador «3 de 9», barra de
  progreso y flechas del teclado, con guarda para no robar las teclas mientras se
  escribe.
- **La cola se congela al entrar.** Etiquetar saca la operación de la consulta de
  pendientes; si la lista se recalculara en cada paso, «3 de 9» pasaría a «3 de 8»
  a mitad de repaso.
- **Una sola forma de etiquetar en toda la aplicación.** Los chips desaparecen:
  se retiran `MistakeReviewQueue` y su vista, y el Laboratorio se queda con
  `<x-review-cta>`, una tarjeta que dice cuántas faltan y lleva al repaso. La
  cobertura de seguridad de la cola se porta a `ReviewPageTest`.
- **`<x-trade-chart>`**: el visor sale del modal a un componente que ahora usan
  las dos pantallas. Fue un movimiento del marcado, no una reescritura. El modal
  le pasa el panel de la captura por el `$slot`.
- **`CountPendingReview`**: una sola definición de «pendiente», con caché corta,
  porque el número lo piden el carril lateral (en cada carga de página), el
  Laboratorio y la propia pantalla.
- **Puntos de entrada**: entrada propia en el carril con el contador en rojo sobre
  el icono, más enlaces desde el Mentor, la tarjeta de coste de la portada y la
  revisión semanal.

**Decisión de producto:** `/repaso` **no es PRO**, aunque la cola viviera dentro
del Laboratorio, que sí lo es. Etiquetar es entrada de datos; cerrarla dejaría al
usuario gratuito con el histórico sin marcar justo el día que se suscribe, que es
cuando el Laboratorio y el Mentor tendrían que enseñarle algo. Mismo criterio que
con el importador en la Fase 2.

**Corregido de paso:** el modal tenía **dos escuchas del mismo evento**
`trade-selected` —la de `chartViewer.init()`, con MAE y MFE, y una en la
plantilla, sin ellos—. La segunda pisaba a la primera, así que al pasar de
operación con las flechas las dos líneas de excursión desaparecían.

---

### P4 · IA con visión — APARCADO

**No hay una sola imagen en la base**: `trades.screenshot` está a cero en todos
los usuarios, y nunca se ha subido ninguna. Además, las velas ya viajan al prompt
**como texto** (`BuildTradeAuditContext`), así que la visión no aportaría nada
sobre lo que ya se analiza.

La regla del prompt que prohíbe lamentar la ausencia de imágenes (`lang/es/ai.php`)
**se queda**: no es una disculpa, es lo que impide que el modelo la escriba.

**Para retomarlo** hace falta, en este orden: (1) que exista la subida de capturas
a la operación —el campo y la tubería a R2 ya están, falta la pantalla—, (2) medir
cuántas operaciones acaban teniendo imagen, y (3) solo entonces decidir proveedor.
Construir la visión antes de tener imágenes es repetir exactamente lo de P1.

### P6 · Descubrimiento automático de setups — APARCADO

Con **un solo activo real y cero estrategias asignadas** en los datos de Jordi, lo
único que se podría agrupar es la demo, que es sintética: encontraría los patrones
que le puso el propio seeder y los presentaría como hallazgos. Se retoma cuando
haya variedad real de activos y estrategias etiquetadas.

### P9 · Comparativa entre cuentas — APLAZADO

Jordi tiene **una sola cuenta**. La comparativa se ve solo en la demo, que tiene
tres. Se retoma cuando haya más de una cuenta real que comparar; la detección de
«desahogo» (perder en una cuenta y descargar en otra) necesita justamente eso.

### P11 · Referencia anónima entre pares — APARCADO CON CRITERIO

Hoy hay **2 usuarios en la base**. Una mediana calculada sobre dos personas no es
anónima ni es una referencia.

**Criterio de reapertura, escrito para no volver a discutirlo:** al menos **50
usuarios activos dentro del mismo programa de prop firm**, y ninguna cifra que se
publique puede salir de menos de 20 traders en ese corte.

### P12 · Más orígenes de datos — MOVIDO A LA FASE 7

cTrader Open API, DXtrade, Match-Trader y las claves de solo lectura de Binance y
Bybit no dependen de ningún dato que ya tengamos: es trabajo de integración, y
rinde donde está la captación. Va a la Fase 7.

---

## Continuidad de los datos del trader

> **Abierto el 2026-09-18 por Jordi:** «me organizo por cuentas, y el día que
> quemo o borro una cuenta de fondeo pierdo la data; sigo con la misma estrategia
> pero con menos historial».

**El diagnóstico, con el código delante, son dos casos distintos:**

- **Quemar no borra nada.** Las operaciones siguen en la base. Lo que hace
  `burned` es sacarlas de las pantallas que construyen la narrativa del trader,
  vía el scope `forUserActiveAccounts`: panel, Mentor, objetivo del mes,
  hallazgos del Laboratorio, cola de repaso y consejo diario. Siguen contando en
  `/trades`, el diario, el detalle, la consulta grande de `/reports`, las rachas
  y el informe PDF si eliges esa cuenta. **Es decir, ya había una incoherencia:**
  el Laboratorio cuenta las quemadas en sus métricas pero no en sus hallazgos.
- **Borrar sí destruía todo.** `trades.account_id` es `onDelete('cascade')` y
  `Account` **no usaba** los soft deletes… **aunque la columna `deleted_at` estaba
  en la migración original de enero**, con el comentario *«nunca borres cuentas de
  trading, solo ocúltalas»*. La intención estaba escrita desde el principio; solo
  faltaba el trait.

**El problema de fondo:** la cuenta hace dos trabajos a la vez. Es el contenedor
de dinero y reglas (balance, drawdown, fase, objetivos), que muere con ella, y es
la unidad de análisis (qué errores repites, si mejoras), que no muere nunca
porque eres tú.

### C · Archivar en vez de borrar ✅ (2026-09-18)

Entregado, con 11 tests nuevos y **sin migración** (la columna ya existía):

- **`SoftDeletes` en `Account`.** El scope global también filtra el
  `whereHas('account')` de `Trade`, así que archivar aparta el histórico de todas
  las pantallas de una vez, sin repetir condiciones por el proyecto.
- **«Eliminar cuenta» pasa a «Archivar cuenta»**, y el aviso dice **cuántas
  operaciones se apartan**. El texto se compone en Blade porque el helper de
  Alpine (`$l`) no sustituye parámetros.
- **Sección «Cuentas archivadas»** al final de `/cuentas`, solo si hay alguna,
  con las operaciones que guarda cada una y su fecha. Restaurar la devuelve
  entera.
- **El borrado real existe y es un segundo paso deliberado**: solo sobre una
  cuenta ya archivada, y la confirmación dice el número que se pierde. Nadie
  destruye un histórico de un clic.
- **Las archivadas no consumen plaza del plan** (el scope global las saca de
  `canCreateAccount`), igual que las quemadas.
- **Dos efectos colaterales del scope global, atados:** (1) `mt5_login` es único
  en toda la tabla y el índice no entiende de archivadas, así que la validación
  de alta mira `withTrashed()` y da un mensaje propio en vez de reventar contra
  la base; (2) las rachas y el resumen semanal hacen `join` con `DB::table`, que
  **no** pasa por el scope global, así que llevan `whereNull('accounts.deleted_at')`
  explícito — si no, una cuenta archivada seguiría alimentando el correo del
  domingo. `GetOverviewKpis` ya lo tenía.
- **El endpoint del `.exe`** distingue «archivada, restáurala» de «no existe»:
  con el mensaje genérico, un usuario que acaba de archivar se buscaría el
  problema donde no está.

### El selector del panel, reaprovechado ✅ (2026-09-18)

Decisión de Jordi el mismo día: **no hace falta pantalla nueva, el selector de
cuentas ya está hecho**. Solo tenía que ofrecer lo que escondía.

- **«Todas» sigue significando las vivas.** Abrir el panel enseña exactamente lo
  de siempre; el valor por defecto no se toca. Lo que cambia es que la lista
  ofrece **también las quemadas y las archivadas**, al final y con etiqueta.
- **La regla nueva, en una frase:** *elegir cuentas a mano es pedirlas.* Una
  selección explícita filtra por esos ids y solo mantiene el filtro de seguridad
  por usuario — sin excluir quemadas ni archivadas. Generaliza lo que
  `BuildMonthlyReport` ya hacía a mano con `includeBurned: $account !== null`.
- **Las dos cargas ansiosas del panel llevan `withTrashed()`**: sin eso, la
  relación `account` de una operación archivada llega a `null` y la fila sale sin
  nombre de cuenta.
- **El semáforo del plan devuelve null** si solo se eligen cuentas muertas, que
  es lo correcto: el plan del día no aplica a una cuenta que ya no opera.

8 tests nuevos en `tests/Feature/Dashboard/AccountSelectorScopeTest.php`,
incluido el que fija que la selección explícita **no** abre la puerta a cuentas
de otro usuario.

### El panel del principiante ✅ (2026-09-18)

Decisión de Jordi: las tres tarjetas de puesta en marcha **ocupan demasiado**
cuando aún no hay datos, pero cerrarlas no es la respuesta —la guía de primeros
pasos todavía tiene trabajo pendiente—. Se pueden **plegar**.

- **`tfStartupBlock`**, un `Alpine.data` que envuelve las tres tarjetas
  (ritual, primeros pasos y datos de ejemplo) y las pliega **juntas**, dejando
  una sola línea con el título y cuántas hay.
- **Se intentó primero una a una y no servía** (corregido el mismo día, a la
  segunda vuelta de Jordi): tres tarjetas plegadas siguen ocupando tres barras
  encima de los números, que es justo lo que molestaba.
- **El bloque desaparece con la última tarjeta.** Cada componente Livewire deja
  su `<div>` raíz aunque no pinte nada, así que el recuento mira el
  `textContent` —no `innerText`, que devuelve vacío con el bloque plegado— y un
  `MutationObserver` lo rehace tras cada morph, sin depender de los internos de
  Livewire.
- **El estado va en `localStorage`, no en la base**: es una preferencia de vista
  de ese navegador, no un dato del usuario, así que no cuesta ni una consulta ni
  una migración. Todos los accesos van en `try/catch`: en modo privado o con el
  almacenamiento bloqueado el getter lanza.
- **La autopsia de la cuenta quemada se queda fuera del bloque**: no es puesta
  en marcha, aparece una sola vez y se cierra sola.
- **Aviso al aplicar el filtro de cuentas.** Pulsar «Aplicar» iba al servidor sin
  que nada lo dijera. Ahora el botón pasa a «Aplicando…» y el selector a
  «Actualizando datos…» durante exactamente lo que dura la petición (`$refresh()`
  devuelve promesa).
- **Corregida de paso una petición duplicada:** el botón hacía `$refresh()` y el
  `$watch('open')` mandaba otro al cerrarse el desplegable. Cerrar pulsando fuera
  ahora pasa por el mismo camino, con su aviso.

3 tests sobre la página del panel, que solo pueden fijar que **el enganche está
en el marcado** y que el bloque no se pinta sin tarjetas: el plegado lo resuelve
Alpine, así que el comportamiento va a la lista manual.

**Y la sección de cuentas archivadas cambia de sitio** (segunda vuelta de Jordi):
estaba al pie de `/cuentas`, que es donde no la ve nadie. Pasa a un botón con
contador **junto al enlace de conexión con MetaTrader**, en la cabecera, y abre
un modal. Los dos son destinos, no contenido.

### A · El análisis mira al trader, no a la cuenta viva ✅ (2026-09-18)

**La regla, en una frase: quemada no es lo mismo que archivada.** Una cuenta
quemada guarda el historial más instructivo que tiene un trader —es el que
explica por qué se quemó— y quien la pierde sigue operando igual al día
siguiente. Una archivada la aparta el usuario a propósito.

`Trade::forUser()` ya hacía exactamente eso (quemadas dentro, archivadas fuera
por el scope global), así que A es elegir bien el scope en cada sitio:

| Pasa a `forUser` (habla del trader) | Se queda en `forUserActiveAccounts` (habla de dinero vivo) |
|---|---|
| `BuildTraderProfile` · `ProposeMonthlyGoal` · `TrackMonthlyGoal` | El valor por defecto del panel (`DashboardTradeQuery`) |
| `CountPendingReview` · `ReviewPage` | `AiDailyTip` — una cuenta muerta no opera hoy |
| `FindingsPanel` y su selector de cuenta | |

- **Los tres del Mentor van juntos a propósito.** Si la propuesta contara las
  quemadas y el seguimiento no, el objetivo del mes se propondría sobre una base
  y se mediría sobre otra.
- **Los hallazgos eran la incoherencia declarada en el diagnóstico:** la consulta
  grande del Laboratorio (`ReportsPage`) ya usaba `forUser`, así que sobre las
  mismas operaciones convivían dos criterios.
- **La cola de repaso puede crecer de golpe** en cuanto haya una cuenta quemada
  con perdedoras sin marcar. Es lo correcto: son justo las que hay que repasar.
- **Se declara la mezcla, no se normaliza.** Con las quemadas dentro el perfil
  puede juntar cuentas de tamaños distintos, así que lleva `accounts` y la
  pantalla avisa cuando son más de una. Normalizar por balance sigue descartado
  mientras haya una sola cuenta real: sería la matemática inventada que costó P1.

8 tests nuevos en `tests/Feature/Mentor/BurnedAccountScopeTest.php`. No había
ninguno que fijara la exclusión anterior: era una decisión implícita.

### Lo que sigue abierto

- ~~**A (resto) · El selector de `/cuentas`.**~~ **Hecho el 2026-09-18**: la
  lista ofrece también las quemadas, al final y marcadas en el propio texto de la
  opción —un `<select>` no admite más—, así que la selección por defecto sigue
  cayendo en una cuenta viva. Con esto se llega al histórico, a las reglas y al
  botón de archivar de una cuenta reventada, que antes no tenían puerta. Las tres
  pantallas con selector (panel, Laboratorio y cuentas) usan ya el mismo criterio.
- **El % agregado sigue descartado, y conviene recordar por qué.** El conmutador
  `$ / %` del navbar es un `Alpine.store` que solo elige entre dos números que el
  servidor ya manda (`format(amount, percent)`), y solo lo consumen 4 vistas: no
  llega al Mentor ni al coste de errores. Con **una sola cuenta real**,
  normalizar por balance es matemática para datos que no existen — la lección de
  P1 y P3. Por eso A declara la mezcla en vez de normalizarla.
- **B · Linaje de cuentas** (`previous_account_id`): «esta cuenta continúa
  aquella», para ver varios challenges como una sola trayectoria. Solo si de
  verdad se van a encadenar.
- **D · Congelar la lección al quemar**: resumen inmutable por cuenta cerrada,
  mismo patrón que `weekly_reviews`. Cuando haya varias cuentas cerradas que
  comparar.

---

## FASE 7 — Captación de largo recorrido

- **C3 · Tarjeta compartible** del resumen mensual (imagen con marca y enlace, con opción
  de ocultar cifras absolutas).
- **C4 · Calculadoras públicas** indexables: tamaño de posición, drawdown por firma,
  "¿pasarías el challenge?".
- **C5 · Acuerdos con prop firms:** código de descuento a cambio de aparecer en su
  onboarding; panel de firma con métricas agregadas anónimas.
- **C6 · Enlace de solo lectura para mentores** (reaprovecha el trabajo de `DemoMode`).

---

## FASE 8 — Móvil (decisión previa)

Antes de tocar nada hay que decidir: o se asume que TradeForge es una herramienta de
escritorio **y se dice**, o se invierte en una vista móvil real. Lo que no funciona es un
escritorio que en el móvil se ve mal.

Si se invierte: sidebar colapsable en `md:`, tablas a tarjetas, gráficos con altura
adaptativa, y el diario rápido de R6 como puerta de entrada móvil.

---

## Pendiente para producción

> Lo construido en las fases 1 a 6 solo produce efecto con una URL pública detrás.
> El resumen semanal, en particular, **no se envía solo**: necesita dos procesos en
> el servidor que hoy no existen en ningún sitio.
>
> **La lista con casillas para ir marcando está en `VALIDACION.MD` §** *Lo que
> tienes que hacer tú en el servidor*, agrupada por orden de bloqueo (18 puntos).
> Lo de aquí abajo es el porqué de cada una; si algo cambia, cambia en los dos.

### Para que salga el correo del domingo

- [ ] **Cron del servidor**, una línea, cada minuto. Sin esto no se ejecuta nada:
      `* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1`
- [ ] **Worker de la cola permanente** (`queue:work` bajo systemd o supervisor). Los
      correos se encolan en la tabla `jobs` y sin worker se quedan ahí para siempre.
      Y `php artisan queue:restart` en cada despliegue, o el worker seguirá con el
      código viejo.
- [ ] **Mailer de verdad**: `MAIL_MAILER=resend` + `RESEND_KEY` + `MAIL_FROM_ADDRESS`
      con dominio propio. Hoy `.env` está en `log` y el correo solo se escribe en
      `storage/logs/laravel.log`.
- [ ] **Dominio autenticado en el proveedor** (SPF, DKIM y DMARC). Sin esto el correo
      llega a spam, que para el caso es no llegar.
- [ ] **`APP_URL` correcta y con HTTPS.** Los dos enlaces del correo se generan con
      ella; si apunta a otro sitio, el de baja **falla la comprobación de firma** y el
      usuario no puede darse de baja.
- [ ] **`APP_KEY` estable.** La firma del enlace de baja depende de ella: si se
      regenera, todos los enlaces ya enviados dejan de valer.
- [ ] **`php artisan config:cache` después de tocar el `.env`**, y comprobar que
      ningún `env()` quedó fuera de un fichero de config (ya pasó una vez en
      `Settings\Subscription`).
- [ ] **`APP_TIMEZONE` correcta.** Es la hora que se usa para todo usuario que aún no
      haya elegido la suya en su perfil.
- [ ] **Verificación de correo funcionando.** El comando solo escribe a quien tiene
      `email_verified_at`; si el registro no verifica de verdad, no sale ni un envío.
- [ ] **Vigilar `failed_jobs`** después del primer domingo (el panel de administración
      ya muestra el estado de la cola).
- [ ] **Prueba de humo tras desplegar:**
      `php artisan resumen:semanal --user=<correo> --force --dry-run` y luego sin
      `--dry-run`, para ver el correo real antes de que le llegue a nadie.

### Para el resto

- [ ] **HTTPS obligatorio para la PWA.** El service worker solo se registra bajo TLS
      (o en localhost), así que sin certificado no hay instalación ni pantalla de sin
      conexión.
- [ ] **`DEMO_ENABLED`** decide si la demo pública existe; el `demo:refresh` nocturno
      cuelga del mismo cron.
- [ ] **Páginas legales** (Términos y Privacidad): hoy `Features::termsAndPrivacyPolicy()`
      sigue comentada en `config/jetstream.php` y el pie de la landing no las pinta.
- [ ] **Capturas reales de la landing.** Los mockups son ilustraciones en HTML/CSS
      marcadas como tales; van a `public/img/landing/` cuando existan.

---

## Registro de progreso

| Fecha | Fase | Qué se hizo |
|---|---|---|
| 2026-08-26 | — | Análisis de producto y creación de este roadmap. |
| 2026-08-26 | 1 | Landing pública, precios abiertos, demo de solo lectura y muro PRO visible. 27 tests nuevos. |
| 2026-08-26 | 2 | Importador de historiales, cuenta de ejemplo, onboarding, estados vacíos, atajos y página de conexión. 60 tests nuevos. |
| 2026-08-26 | 3 | Prueba de 14 días, límite de cuentas por plan, gating PRO en servidor, contador de IA y autopsia de cuenta quemada. 33 tests nuevos. |
| 2026-08-27 | 4 | Scheduler, resumen semanal por correo, revisión semanal guiada, rachas, ritual pre-mercado, PWA instalable y retirada de Firebase. 75 tests nuevos. |
| 2026-08-27 | — | Carril lateral de 12 a 8 entradas, desplegable con etiquetas; los cuatro destinos retirados pasan a botones dentro de su pantalla. 7 tests nuevos. |
| 2026-08-27 | 5 | P7 · De hallazgo a regla: cuatro generadores con muestra mínima declarada, tabla `trading_rules` con procedencia, adopción con ámbito elegible que baja al plan de la cuenta, y las reglas en el checklist y el semáforo de la sesión en vivo. 20 tests nuevos. |
| 2026-08-27 | 5 | P2 · Coste de los errores: cálculo contrafactual con cobertura declarada, tarjeta en portada, desglose y escenario «sin el error X» en el Laboratorio, y cola de repaso de perdedoras. 22 tests nuevos. |
| 2026-08-27 | 5 | P10 · Exportar: CSV de operaciones con los filtros de la pantalla, e informe mensual en PDF con marca (dompdf) que compone las acciones ya existentes y añade el cumplimiento de reglas. CSV gratuito, PDF PRO, ambos operativos en la demo. 37 tests nuevos. |
| 2026-08-27 | 5 | P1 · Sistema R implementado y **revertido el mismo día**: el agente manda el nivel que disparó el cierre, no el de apertura, así que solo las operaciones perdedoras podían tener R (11 de 338). Se retira entero; el conmutador vuelve a `$ / %`. |
| 2026-08-28 | 6 | **Conteo previo:** 59 `StoreTradeChartJob` atascados desde el 25-ago (nunca había corrido un worker). Vaciada la cola: las 22 operaciones de Jordi pasan de 0 a 22 con velas. `screenshot` sigue a 0 en toda la base y `account_daily_metrics` está vacía y sin escritor. |
| 2026-08-28 | 6 | P3 · Centro de mando del challenge **construido y retirado el mismo día**: con 7 días operados de los 20 de muestra mínima, la simulación se callaba y lo que quedaba en pantalla ya lo enseñaba `/cuentas`. Se retiran pantalla, ruta, vistas, idiomas y sus 9 tests; se conservan `SimulateChallenge` y `BuildDailyResults` con 17 tests y el motivo escrito en su cabecera. |
| 2026-08-28 | 6 | P8 · Reproductor de la operación: el visor reescrito alrededor de un único `paint(upTo)`, controles barra a barra con velocidades, y MAE/MFE revelados en la vela que los tocó. Velas de ejemplo para la demo con el formato del agente. 16 tests nuevos. |
| 2026-08-28 | 6 | P5 · Mentor con memoria: perfil acumulado de seis meses con tendencia por frecuencia y cobertura declarada, un objetivo de mejora al mes congelado e irreversible con cierre automático, y el perfil inyectado en el contexto del auditor de IA. 30 tests nuevos. |
| 2026-08-28 | 6 | Aparcados con motivo escrito: P4 (cero imágenes en la base), P6 (un activo real), P9 (una sola cuenta) y P11 (2 usuarios, con umbral de reapertura). P12 movido a la Fase 7. |
| 2026-08-28 | 6 | Usabilidad del repaso de errores: pantalla `/repaso` con una operación por vez y el gráfico delante, visor extraído a `<x-trade-chart>`, chips duplicados retirados y una sola forma de etiquetar. Fuera del muro PRO por ser entrada de datos. 14 tests nuevos. |
| 2026-09-18 | — | Continuidad de los datos: `Account` pasa a soft deletes y «Eliminar» archiva. El botón dice cuántas operaciones aparta, hay sección de archivadas con restaurar, y el borrado real es un segundo paso sobre una cuenta ya archivada. Atados los dos agujeros del scope global (`mt5_login` único y los `join` de rachas y resumen semanal). Sin migración: `deleted_at` existía desde enero sin usar. 11 tests nuevos. |
| 2026-09-18 | — | El selector de cuentas del panel pasa a ofrecer también las quemadas y las archivadas, con etiqueta. «Todas» sigue siendo solo las vivas: la regla es que elegir a mano es pedirlas. 8 tests nuevos. |
| 2026-09-18 | — | Panel del principiante: las tres tarjetas de puesta en marcha se pliegan (`tfMinimizable`, estado en localStorage) y aplicar el filtro de cuentas avisa mientras recalcula. Corregida una petición duplicada del multiselect. 4 tests nuevos. |
| 2026-09-18 | — | Opción A: el Mentor, la cola de repaso y los hallazgos pasan a mirar al trader (quemadas dentro, archivadas fuera); el panel y el consejo del día se quedan con las vivas. El perfil declara sobre cuántas cuentas se calcula. 8 tests nuevos. |
| 2026-09-18 | — | `/cuentas` ofrece también las cuentas quemadas, al final y marcadas: antes no había forma de llegar a una cuenta reventada desde su propia pantalla. Las tres pantallas con selector comparten ya el criterio. 3 tests nuevos. |
