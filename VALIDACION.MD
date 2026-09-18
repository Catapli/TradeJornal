# Cuaderno de validación

> Lo que **tú** tienes que comprobar a mano, porque ningún test lo ve: que la
> pantalla se entienda, que el botón haga lo que promete y que el número que sale
> sea el que esperabas.
>
> Marca la casilla cuando lo hayas probado. Lo que falle, dímelo con el caso
> concreto (qué cuenta, qué operación, qué cifra) y lo miro.
>
> **Antes de nada, siempre:** `php artisan migrate` si te aviso de migración, y
> **Ctrl+F5** si te aviso de `npm run build` — el navegador se queda con el JS viejo.

---

## ⚠️ Con qué cuenta validar

Tu usuario (`jordicatalina2001@gmail.com`) tiene **28 operaciones en una sola
cuenta** (id 2, «10k NYS»), todas de EURUSD. Las cifras que aparecen abajo entre
paréntesis salen de **`demo@tradeforge.app`**, que tiene 319 operaciones del
seeder en tres cuentas.

**Cambió el 2026-09-04, contado en la base:** ya **no te queda ninguna perdedora
sin repasar**. Las 21 están repasadas, 18 con error marcado, **25 marcas** en
total y 75 % de cobertura. Eso tiene dos consecuencias directas:

- El **Mentor ya no está vacío**: pasa de 12 marcas mínimas y tiene 25, así que
  el perfil sale con datos tuyos de verdad.
- La **cola de `/repaso` está a cero**, así que la pantalla arranca en su estado
  de «cola vaciada». Más abajo tienes cómo devolver tres operaciones a la cola
  sin perder ni una marca.

**Ojo, cambió el 2026-08-28:** al regenerar la demo con velas, el usuario de demo
se recreó y **ya no es el id 5**. Búscalo siempre por correo, no por id.

**Y una cosa a tu favor:** 27 de tus 28 operaciones **ya tienen velas**. Estaban
atascadas en la cola porque nunca había corrido un worker; se vaciaron a mano dos
veces. El reproductor y el repaso sí los puedes probar con datos tuyos.

**Queda 1 trabajo pendiente en `jobs` ahora mismo** (0 fallidos): es la operación
sin velas. Lánzalo con `php artisan queue:work --stop-when-empty` y quedan las 28.

Así que, para cada bloque:

- **Lo que se lee** (que el número salga, que la frase se entienda, que el idioma
  y el modo oscuro estén bien) → míralo en **`/demo`**, que va lleno.
- **Lo que se pulsa** (adoptar una regla, repasar una perdedora, fijar el objetivo
  del mes) → tiene que ser con **tu usuario**, porque la demo bloquea toda
  escritura a propósito. Con 27 operaciones verás algunos estados vacíos, que
  también hay que comprobar que se entienden.

Si quieres validarlo de verdad de punta a punta, lo que hace falta es **importar
tu histórico** en tu propia cuenta.

---

## Índice de lo pendiente

**123 comprobaciones en 8 bloques.** Ninguna marcada todavía.

| # | Bloque | Entregado | Migración | Ctrl+F5 |
|---|---|---|---|---|
| 1 | Fase 5 · P2 — Coste de los errores | 27-ago | ✅ aplicada | sí |
| 2 | Fase 5 · P7 — De hallazgo a regla | 27-ago | ✅ aplicada | sí |
| 3 | Fase 5 · P10 — Exportar | 27-ago | ✅ aplicada | sí |
| 4 | Fase 6 · P8 — Reproductor de la operación | 28-ago | no lleva | **sí, obligatorio** |
| 5 | Fase 6 · P5 — Mentor con memoria | 28-ago | ✅ aplicada | sí |
| 6 | Fase 6 · Repaso de errores (usabilidad) | 28-ago | no lleva | **sí, obligatorio** |
| 7 | Correcciones sueltas del 27-ago | 27-ago | no lleva | sí |
| 8 | Archivar cuentas · selector · tarjetas plegables | 18-sep | **no lleva** | **sí, obligatorio** |

> **P3 · Centro de mando ya no está aquí.** Se construyó y se retiró el mismo día:
> con 7 días operados la simulación se callaba y lo que quedaba en pantalla ya lo
> enseñaba `/cuentas`. El motivo completo está en `ROADMAP.MD`. No hay nada que
> validar; si te aparece `/challenge` en el navegador, es la caché del navegador.

**Por dónde empezar:** `/mentor`. Es lo único donde ahora mismo hay algo tuyo
esperándote sin preparar nada: el perfil sale con datos reales y **hay un objetivo
del mes propuesto y sin fijar**.

**El circuito, con las cifras que salen hoy de tu base:**

1. **`/mentor`** → el perfil sale: «Trading de venganza» 14 veces y **609 € de
   coste**, más «Sobreoperar» (4 · 177 €), «Entrada sin setup» (2 · 90 €), «Ida y
   vuelta» (3 · 72 €), «Fuera de horario» (1 · 54 €) y «Contra tendencia»
   (1 · 45 €). Cobertura 75 %.
2. **Fija el objetivo del mes**: propone bajar «Trading de venganza» **de 14 a 7
   en septiembre**. Confirma, recarga, y comprueba que no hay forma de editarlo.
3. **`/dashboard`** → pon el rango de fechas sobre **agosto** (el coste sale de la
   misma consulta que pinta el panel): la tarjeta tiene que sumar **~1.047 €** y
   cobertura 75 %. Con el rango por defecto sobre septiembre sale 0 porque no has
   operado: comprueba que ahí dice algo con sentido y no un cero pelado.
4. **`/reports`** → el mismo desglose y la misma cobertura, cifra a cifra.
5. **`/repaso`** → sale la pantalla de cola vaciada. Para probarla de verdad,
   devuelve tres operaciones a la cola con el comando de abajo.

**Ojo con la tendencia:** las seis salen como «a mejor» porque llevas **0
operaciones en septiembre**. Es correcto, pero no significa nada todavía.

---

## Pendiente de validar

### Fase 5 · P2 — Lo que te han costado tus errores
*(entregado 2026-08-27 · migración ✅ aplicada · build ✅ hecho · cifras de la demo)*

- [ ] **Portada.** `/dashboard`, debajo de rachas: sale la tarjeta con el titular
      (~6.774,68 €), los tres errores más caros y la línea de cobertura
      («99 de 334 operaciones repasadas»).
- [ ] **La cobertura nunca va sola.** El número grande y la cobertura se leen
      juntos, sin tener que buscarla.
- [ ] **El filtro de fechas mueve el número.** Cambia el rango en la cabecera del
      panel: el coste tiene que cambiar con él.
- [ ] **Laboratorio.** `/reports`, baja hasta la sección: el desglose y la
      cobertura coinciden **exactamente** con los de portada.
- [ ] **Por franja horaria.** Tarde (13-17) ≈ 4.151 €, Mañana (08-12) ≈ 2.623 €.
- [ ] **Escenario.** Marca `held_loser` en «Quitar operaciones con estos errores»
      y pulsa Aplicar: la curva simulada sube ~1.537 € respecto a la real.
- [ ] **Cola de repaso.** Etiqueta una perdedora y dale a *Guardar y siguiente*:
      desaparece de la lista y el contador baja.
- [ ] **Botón «Sin errores».** En otra fila: también desaparece, pero **sin**
      quedarle ningún error marcado.
- [ ] **La cobertura sube.** Vuelve a `/dashboard`: ya no pone 29,6 %.
- [ ] **Ver más.** Con 116 pendientes, el botón de abajo carga 10 más cada vez.
- [ ] **Inglés.** Cambia el idioma: ninguna cadena se queda en español.
- [ ] **Modo oscuro.** Tarjeta, desglose y cola legibles en los dos temas.

### Fase 5 · P7 — De hallazgo a regla
*(entregado 2026-08-27 · migración ✅ aplicada · build ✅ hecho)*

- [ ] **El panel abre el Laboratorio.** `/reports`: arriba del todo, «Lo que tus
      datos te están diciendo», con las frases y su cifra.
- [ ] **Cada frase dice su muestra.** Debajo de cada hallazgo: «sobre N
      operaciones». Si no llega al mínimo (12), el hallazgo no sale.
- [ ] **Estado vacío.** Con tu usuario (10 operaciones) tiene que salir el aviso
      de que hacen falta al menos 12, y entenderse sin más explicación.
- [ ] **Filtro de cuenta.** Cambia la cuenta arriba del Laboratorio: los hallazgos
      se recalculan sobre esa cuenta.
- [ ] **Adoptar.** Pulsa «Convertir en regla»: se abre el cuadro con el texto ya
      propuesto y **editable**. Cámbialo y adopta.
- [ ] **Ámbito.** Prueba las dos: «Una cuenta concreta» y «A todas mis cuentas».
- [ ] **Baja al plan.** Adopta una regla de horario o de tope de operaciones
      **para una cuenta**: entra en el plan de esa cuenta y comprueba que el
      horario o el tope se han escrito solos.
- [ ] **La global no toca planes.** Adopta una «a todas mis cuentas»: ningún plan
      de cuenta debe haber cambiado.
- [ ] **Mis reglas.** Debajo: la regla con su etiqueta de ámbito, si la vigila el
      semáforo o es solo de checklist, y **de qué hallazgo salió con qué cifra**.
- [ ] **Desactivar y volver a activar.** La regla se apaga sin perder la procedencia.
- [ ] **Borrar.** Desaparece de la lista y el hallazgo vuelve a ofrecer «Convertir
      en regla».
- [ ] **Checklist de la sesión.** Abre una sesión en vivo con esa cuenta: tu regla
      aparece en el checklist junto a las de la estrategia.
- [ ] **Semáforo.** Con una regla de tope de operaciones adoptada, al llegar al
      tope el botón de operar tiene que bloquearse.
- [ ] **Regla de día de la semana.** Adopta una y ábrela ese mismo día: el botón
      debe bloquearse con «Fuera de horario».
- [ ] **Inglés y modo oscuro** en las dos tarjetas.

### Fase 5 · P10 — Exportar
*(entregado 2026-08-27 · **sin migración** · build ✅ hecho — necesita **Ctrl+F5**)*

**CSV — en `/trades`, junto al botón de Filtros**

- [ ] **Baja el fichero.** Pulsa «Exportar CSV»: se descarga
      `tradeforge-operaciones-AAAA-MM-DD.csv` sin recargar la página.
- [ ] **Se abre bien en Excel.** Doble clic: cada dato en su columna, y los
      acentos de las notas correctos (no «Ã³»). Si te sale todo en una columna,
      dímelo con la versión de Excel.
- [ ] **Respeta los filtros.** Filtra por cuenta, por resultado «Perdedoras» y por
      un error concreto, y exporta: **el número de filas del fichero tiene que ser
      el mismo que el de la tabla** (sin contar la cabecera).
- [ ] **Búsqueda incluida.** Escribe un ticket en el buscador y exporta: solo esa.
- [ ] **Columna de errores.** Una operación con dos errores marcados los trae
      separados por ` | `.
- [ ] **Notas de varias líneas.** Exporta una operación cuya nota tenga saltos de
      línea: la fila no se parte en dos.
- [ ] **Sin resultados.** Pon filtros que no devuelvan nada y pulsa exportar: sale
      un aviso y **no** se descarga un fichero con solo la cabecera.
- [ ] **Es gratuito.** Con un usuario sin PRO el botón funciona igual.
- [ ] **En la demo.** `/demo` → `/trades` → exportar: la **primera línea** del
      fichero avisa de que son datos de ejemplo.

**Informe mensual en PDF — al final de `/reports` (Laboratorio)**

- [ ] **La tarjeta está abajo del todo**, con el desplegable de mes, la cuenta
      seleccionada arriba y el botón de descarga.
- [ ] **El desplegable solo ofrece meses con operaciones**, del más reciente al más
      antiguo. Cambia la cuenta arriba: la lista de meses se recalcula.
- [ ] **Se descarga.** `tradeforge-informe-<cuenta>-AAAA-MM.pdf`. Ábrelo.
- [ ] **Cuadra con la pantalla.** El P&L y el acierto del informe tienen que ser
      los mismos que ves en `/dashboard` filtrando ese mismo mes y esa cuenta.
      *(Si no cuadran, dime el mes, la cuenta y las dos cifras.)*
- [ ] **Comparativa.** El bloque «Contra <mes anterior>» compara con el **mes
      natural anterior**, no con los últimos 31 días. Si no operaste el mes
      anterior, el bloque no sale.
- [ ] **Curva de capital.** Se dibuja la línea. Con un mes de un solo día de
      operaciones sale el aviso de que no hay curva, no un marco vacío.
- [ ] **Calendario.** Cada día en su casilla y con su color; los días sin operar,
      en blanco. Comprueba un día concreto contra el calendario del dashboard.
- [ ] **Coste de los errores.** Sale el número **y la línea de cobertura debajo**.
      Nunca uno sin la otra.
- [ ] **Reglas.** Las adoptadas en P7 salen con las veces que las rompiste. Una
      regla que la máquina no puede comprobar sola sale como «La compruebas tú»,
      **sin porcentaje**.
- [ ] **Acentos y símbolo de divisa** correctos en todo el PDF (es la fuente
      DejaVu; si ves cuadrados, avísame).
- [ ] **Logotipo** en la cabecera de todas las páginas, y pie con la fecha de
      generación.
- [ ] **Mes vacío.** Elige un mes sin operaciones (si el desplegable te deja) o
      cambia de cuenta: tiene que avisar en lugar de descargar un PDF de ceros.
- [ ] **Muro PRO.** Con un usuario sin PRO el Laboratorio sigue difuminado y el
      botón no descarga nada.
- [ ] **Cuenta quemada.** Elige una cuenta quemada arriba del Laboratorio: el
      informe **sí** trae sus operaciones (con «todas las cuentas» no salen).
- [ ] **En la demo.** El PDF sale con el sello **DEMO** en la cabecera y el aviso
      en el pie.
- [ ] **Inglés y modo oscuro** en la tarjeta de `/reports` y en el botón de
      `/trades`. El PDF es siempre claro, eso es correcto: se imprime.

### Fase 6 · P8 — Reproductor de la operación
*(entregado 2026-08-28 · sin migración · build ✅ hecho · **Ctrl+F5 obligatorio**)*

- [ ] **Abre una operación tuya** desde `/trades`. Como ya tienes velas, la
      pestaña «Gráfico» debe salir seleccionada sola.
- [ ] **Barra del reproductor abajo del gráfico.** Pulsa «Reproducir»: aparecen
      atrás/play/adelante, la barra de progreso, el contador `n / total` y las
      velocidades 1× a 8×.
- [ ] **Le das al play y el gráfico avanza barra a barra.** Prueba a cambiar la
      velocidad a media reproducción: tiene que acelerar sin saltar.
- [ ] **Arrastra la barra de progreso**: el gráfico salta a esa vela y la
      reproducción se pausa.
- [ ] **Lo importante: las líneas NO se adelantan.** Al empezar no debe verse ni
      ENTRY, ni EXIT, ni MAE, ni MFE. Cada una aparece en la barra que la tocó.
      Si al entrar en el reproductor ves las cuatro líneas ya pintadas, está mal.
- [ ] **Cambia de marco temporal (1m → 15m → 1h) durante la reproducción**: el
      contador se resincroniza y no se queda diciendo «120 / 30».
- [ ] **Sal del reproductor**: vuelve el gráfico completo con las cuatro líneas y
      el zoom ajustado.
- [ ] **Pasa a la operación siguiente con las flechas**: el reproductor tiene que
      salirse solo y empezar de cero con la operación nueva.
- [ ] **En `/demo`**, abre una de las 40 operaciones más recientes: también tienen
      velas (inventadas, pero coherentes con su entrada, salida, MAE y MFE).
      Las más antiguas no las tienen y ahí **no debe salir la barra** del
      reproductor.

### Fase 6 · P5 — Mentor con memoria
*(entregado 2026-08-28 · migración ✅ aplicada · build ✅ hecho · módulo PRO)*

- [ ] **Entra en `/mentor`** desde el carril lateral (bloque «Método», icono de
      graduado).
- [ ] **Con tu usuario ya sale el perfil** (25 marcas, mínimo 12). El estado
      vacío ya no lo puedes ver aquí: para comprobarlo, entra con un usuario
      recién registrado y mira que el botón lleve a `/repaso`, no a `/trades`.
- [ ] **En `/demo` sale el perfil**: hasta cinco errores con las veces, el coste,
      cuántas van este mes y la etiqueta de tendencia (a mejor / a peor / igual).
      La línea de cobertura tiene que estar arriba a la derecha.
- [ ] **La tendencia se compara por operaciones, no por número suelto.** Si un mes
      tiene la mitad de operaciones y la mitad de marcas, la etiqueta debe decir
      «Igual», no «A mejor».
- [ ] **Objetivo del mes.** Con tu usuario **sí hay propuesta**: «Trading de
      venganza, de 14 a 7 en septiembre», con agosto de mes de referencia y 28
      operaciones de muestra. Comprueba que esas cifras son las que ves.
- [ ] **Fijar el objetivo pide confirmación** y avisa de que no se puede cambiar
      ni borrar. Fíjalo **con tu usuario** (en demo está bloqueado, y debe salir
      el aviso de demo si lo intentas ahí).
- [ ] **Una vez fijado, no hay forma de editarlo ni borrarlo.** Es a propósito.
      Recarga: sigue ahí, con su barra de progreso y los días que quedan de mes.
- [ ] **Memoria en la auditoría de IA.** Audita una operación desde el modal: el
      veredicto debería relacionarla con tus errores recurrentes en una frase.
      *(Si tu perfil no llega a las 12 marcas, esto no aparecerá: es correcto.)*
- [ ] **Modo oscuro y `?lang=en`** en toda la pantalla.

### Fase 6 · Repaso de errores (pasada de usabilidad)
*(entregado 2026-08-28 · sin migración · build ✅ hecho · **Ctrl+F5** · NO es PRO)*

- [ ] **Entra en `/repaso`** desde el carril lateral (bloque «Método», icono de
      portapapeles). Fíjate en que el icono lleva **un número rojo** con las
      pendientes; con el carril cerrado también se ve.
- [ ] **La pantalla NO hace scroll.** Es lo primero que hay que comprobar: todo
      —gráfico, ficha, errores y botones— tiene que caber de una vez. Si aparece
      barra de desplazamiento en tu monitor, dímelo con la resolución que usas.
- [ ] **El reparto de la pantalla.** Barra fina arriba (título,
      contador y progreso en una línea), gráfico a la izquierda (3/5 del ancho) y
      a la derecha, en este orden, **«Cuándo fue» → auditoría de errores →
      botones**. Los chips entran de tres en tres.
- [ ] **La fecha se lee de un vistazo**, arriba del carril y en grande («Jueves,
      21 de agosto»), con el año, el tramo horario y la duración debajo. Era lo que
      no se veía: si sigue costando situar la operación, dímelo.
- [ ] **Los botones están siempre a la vista**, sin tener que buscarlos.
- [ ] **El selector de errores nace abierto y sin plegable**, con las sugerencias
      convertidas en pastillas ámbar en su cabecera (pasa el ratón por encima
      para ver el motivo). En el detalle de la operación sigue plegado y con su
      caja de sugerencias de siempre: comprueba que ahí no ha cambiado nada.
- [ ] **El gráfico ocupa todo el alto que sobra**, con sus marcos temporales,
      volumen, EMA y el reproductor barra a barra. Es el mismo componente que el
      del detalle de la operación.
- [ ] **Marca un error**: se guarda solo, sin pulsar nada más. Luego **«Guardar y
      siguiente»** pasa a la operación 2 de 3.
- [ ] **El contador no debe bajar a mitad de repaso.** Tiene que seguir diciendo
      «de 3» aunque vayas cerrando. Si pasa a «de 2», dímelo.
- [ ] **El gráfico cambia al avanzar.** Si se queda congelado en la primera
      operación, es un fallo: avísame.
- [ ] **«Sin errores»** la saca de la cola sin marcar nada. **«Saltar»** la deja
      como está para otro día.
- [ ] **Flechas del teclado:** → guarda y avanza, ← vuelve atrás. Prueba a
      escribir dentro del formulario de crear un error propio: las flechas **no**
      deben robarte el teclado ahí.
- [ ] **Al terminar** sale la pantalla de «cola vaciada» con cuántas repasaste,
      un botón al Mentor y otro para buscar más pendientes.
- [ ] **Vuelve al Mentor.** Las marcas que añadas aquí tienen que verse allí:
      apunta el total antes de empezar y compruébalo al terminar.
- [ ] **El Laboratorio ya no tiene la lista con chips**: en su sitio hay una
      tarjeta con las pendientes y un botón. Comprueba que el número coincide con
      el del carril lateral.
- [ ] **La portada**: en la tarjeta de coste de los errores, debajo del aviso de
      cobertura, sale el enlace «Repasar N pendientes».
- [ ] **La revisión semanal** (`/revision-semanal`) también lo enlaza.
- [ ] **En `/demo`** la pantalla se ve pero no deja guardar: al pulsar «Sin
      errores» debe salir el aviso de demo.
- [ ] **Con el detalle de una operación abierto** (`/trades` → una operación),
      pasa a la siguiente con las flechas del modal y comprueba que **el MAE y el
      MFE se siguen dibujando**. Antes se perdían: había dos escuchas del mismo
      evento y una pisaba a la otra.
- [ ] **Modo oscuro y `?lang=en`** en toda la pantalla.

> **Tu cola está vacía: cómo devolver tres operaciones sin perder marcas.**
> Las tres que cerraste con «Sin errores» no tienen ningún error asociado, así
> que se pueden devolver a la cola borrando solo la fecha de repaso. Las 25
> marcas y el perfil del Mentor no se tocan.
>
> ```
> php artisan tinker
> >>> App\Models\Trade::whereHas('account', fn ($a) => $a->where('user_id', 1))
> ...     ->where('pnl', '<', 0)
> ...     ->whereNotNull('mistakes_reviewed_at')
> ...     ->whereDoesntHave('mistakes')
> ...     ->update(['mistakes_reviewed_at' => null]);
> ```
>
> El contador del carril lateral tarda hasta **5 minutos** en moverse
> (`CountPendingReview` cachea). Para verlo ya: `php artisan cache:clear`.

### Archivar cuentas en vez de borrarlas
*(entregado 2026-09-18 · **sin migración**: la columna `deleted_at` ya existía desde
enero · necesita **Ctrl+F5**, cambia el JS)*

> **Qué cambia.** El botón de `/cuentas` ya no dice «Eliminar cuenta» sino
> **«Archivar cuenta»**, y archiva de verdad: la cuenta sale de todas las
> pantallas pero **no se borra ni una operación**. El borrado real existe, pero
> hay que pedirlo aparte desde la lista de archivadas.

- [ ] **El botón cambió.** En `/cuentas`, con una cuenta seleccionada, el tercer
      botón es «Archivar cuenta» con icono de caja, no la papelera roja.
- [ ] **El aviso dice cuántas operaciones se apartan.** Al pulsarlo, el modal
      tiene que decir el número («se archiva con sus 28 operaciones»), no una
      frase genérica. Si ves «:count» literal, avísame.
- [ ] **Archiva de verdad.** Confirma: la cuenta desaparece de `/cuentas`, del
      selector del panel y de `/trades`. **Apunta antes el total de operaciones
      de `/trades`** y comprueba que baja en las de esa cuenta.
- [ ] **Aparece la sección «Cuentas archivadas»** al final de `/cuentas`, con el
      número de cuentas en la cabecera. Ábrela: cada una dice **cuántas
      operaciones guarda** y en qué fecha se archivó.
- [ ] **La sección no existe si no has archivado nada.** Con la cola vacía no
      debe salir ni el desplegable.
- [ ] **Restaurar la devuelve entera.** Pulsa «Restaurar»: vuelve a `/cuentas`,
      vuelve al selector del panel y **el total de `/trades` vuelve a ser el de
      antes**, operación por operación.
- [ ] **No te come una plaza del plan.** Con el plan gratuito y 3 cuentas, archiva
      una: tienes que poder crear una nueva. Al restaurarla, si eso te deja en 4,
      no pasa nada — el límite solo frena las altas.
- [ ] **El borrado definitivo pide un segundo paso.** Solo está dentro de la
      sección de archivadas, y su aviso dice el número de operaciones que se
      pierden y que no hay vuelta atrás. **Pruébalo con una cuenta de prueba, no
      con la tuya.**
- [ ] **El login MT5 de una archivada da un mensaje con sentido.** Archiva una
      cuenta con `mt5_login` y crea otra con el mismo número: debe decirte que
      pertenece a una cuenta archivada, no un error genérico ni un 500.
- [ ] **El `.exe` avisa bien.** Si sincronizas una cuenta archivada, la respuesta
      debe decir «está archivada: restáurala», no «no existe».
- [ ] **Modo oscuro y `?lang=en`** en la sección de archivadas y en los dos
      modales de confirmación.

**El selector de cuentas del panel** (mismo día, va con esto):

- [ ] **`/dashboard` arranca igual que siempre.** Con «Todas» seleccionado, las
      cifras son las de tus cuentas vivas: ni quemadas ni archivadas. Si al abrir
      el panel te cambian los números, es un fallo.
- [ ] **El desplegable ahora las ofrece todas.** Ábrelo: salen también las
      quemadas y las archivadas, al final de la lista y con su etiqueta gris
      («Quemada» / «Archivada»).
- [ ] **Marcar una archivada trae su historial.** Selecciona solo esa cuenta y
      pulsa Aplicar: KPIs, curva, calendario y operaciones recientes se llenan
      con sus datos.
- [ ] **La fila trae el nombre de la cuenta**, no un hueco. Es lo que se rompía
      si la relación llegaba vacía.
- [ ] **Se pueden mezclar.** Marca una viva y una archivada: el P&L tiene que ser
      la suma de las dos.
- [ ] **El semáforo del plan desaparece** si solo eliges cuentas muertas. Es
      correcto: el plan del día no aplica a una cuenta que ya no opera. Lo que no
      vale es que reviente o que enseñe el de otra cuenta.
- [ ] **Modo oscuro y `?lang=en`** en el desplegable, etiquetas incluidas.

> **Lo que NO cambia todavía:** el Mentor, los hallazgos del Laboratorio y el
> coste de los errores siguen mirando solo a las cuentas vivas, y `/cuentas` y
> `/reports` tienen sus propios selectores sin tocar. Eso es la opción A.

**Tarjetas de puesta en marcha plegables y aviso al aplicar el filtro**
*(mismo día · sin migración · **Ctrl+F5** · esto lo resuelve Alpine, así que los
tests no lo ven: hay que mirarlo)*

- [ ] **Las tres tarjetas tienen un botón de plegar** (galón, arriba a la
      derecha): «Primeros pasos», «Ritual pre-mercado» y «¿Todavía sin
      operaciones?». Con un usuario recién registrado salen las tres a la vez,
      que es el caso que se queja.
- [ ] **Plegada, la tarjeta es una línea.** La guía se queda con su título, la
      barra de progreso y el enlace de descartar; el ritual con su título y el
      botón de empezar; la de ejemplo con su título.
- [ ] **El ritual conserva «Empezar»** aunque esté plegado. Si lo esconde,
      plegar sería cerrar y eso ya lo hace «Hoy no».
- [ ] **Se recuerda al recargar.** Pliega las tres, pulsa F5: siguen plegadas.
- [ ] **Es por navegador, no por cuenta.** Ábrelo en otro navegador o en una
      ventana privada: salen desplegadas. Es a propósito —es una preferencia de
      vista, no un dato tuyo—, pero conviene que lo sepas.
- [ ] **Descartar sigue funcionando.** «Ya lo tengo» en la guía la quita del todo
      y no vuelve al recargar.
- [ ] **El galón gira** al plegar y desplegar, y el `title` cambia entre
      «Minimizar» y «Desplegar».
- [ ] **Al aplicar el filtro de cuentas se nota.** `/dashboard` → abre el
      selector, cambia la selección y pulsa **Aplicar**: el botón pasa a
      «Aplicando…» con su ruedecita y el propio selector dice «Actualizando
      datos…» hasta que llegan los números.
- [ ] **Cerrar pulsando fuera también aplica y también avisa.**
- [ ] **Una sola petición.** Antes se mandaban dos (el botón y el vigilante del
      desplegable). Si abres la pestaña de red del navegador, al pulsar Aplicar
      tiene que salir **una**.
- [ ] **Modo oscuro y `?lang=en`** en las tres tarjetas plegadas y en el aviso
      del selector.

### Correcciones sueltas del 2026-08-27
*(build ✅ hecho — necesitan **Ctrl+F5**)*

- [ ] **Porcentajes en las tablas del panel.** `/dashboard` → conmutador `%`. Una
      ganadora de 142,50 € debe salir **+1,43 %**, no +0,00 %. Mira la tabla de
      operaciones recientes y también el detalle de un día del calendario.
      *(Si sigues viendo negativos donde esperas positivos, dime el importe en €
      de una fila y lo que te sale en %.)*
- [ ] **Total del día.** Abre un día del calendario: el total en % del modal tiene
      que coincidir con el de la casilla del calendario de ese mismo día.
- [ ] **Reset de cuenta con operaciones manuales.** Crea una operación a mano en
      una cuenta sincronizada y lanza el reset desde el `.exe`: ya no debe reventar.
- [ ] **Sistema R retirado.** El navbar solo tiene `$` y `%`. No queda ni rastro de
      R en `/trades`, `/reports` ni `/cuentas`.

---

## Validado

*(Nada todavía. Según vayas confirmando bloques, los muevo aquí con la fecha.)*

---

## Lo que tienes que hacer tú en el servidor

No es validación, es puesta en marcha. **Sin esto, buena parte de lo construido
en las fases 4 a 6 está escrito pero no se ejecuta nunca.** Son 18 cosas, en
cuatro grupos y en este orden.

### A · Los dos procesos que faltan (lo que más bloquea)

- [ ] **Cron del servidor**, una línea, cada minuto. Sin esto no se ejecuta nada
      programado: ni el resumen semanal ni el `demo:refresh` nocturno.
      `* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1`
- [ ] **Worker de cola permanente** (`queue:work` bajo systemd o supervisor).
      **Ya no es opcional.** Sin él, `StoreTradeChartJob` se acumula y ninguna
      operación llega a tener velas: sin velas no hay reproductor, no hay
      estructura para la IA y el repaso pierde su gráfico. Pasó dos veces el mismo
      día — 59 trabajos atascados desde el 25 de agosto, y otros 5 mientras
      trabajábamos el 28.
- [ ] **`php artisan queue:restart` en cada despliegue**, o el worker seguirá
      ejecutando el código viejo.
- [ ] **Vigilar `failed_jobs`** la primera semana. El panel de administración ya
      muestra el estado de la cola.

### B · Correo (el resumen del domingo no sale sin esto)

- [ ] **Mailer de verdad**: `MAIL_MAILER=resend` + `RESEND_KEY` +
      `MAIL_FROM_ADDRESS` con dominio propio. Hoy el `.env` está en `log` y el
      correo solo se escribe en `storage/logs/laravel.log`.
- [ ] **Dominio autenticado** en el proveedor: SPF, DKIM y DMARC. Sin esto llega
      a spam, que para el caso es no llegar.
- [ ] **Verificación de correo funcionando.** El comando solo escribe a quien
      tiene `email_verified_at`; si el registro no verifica de verdad, no sale ni
      un envío.
- [ ] **Prueba de humo tras desplegar**, en este orden:
      `php artisan resumen:semanal --user=<correo> --force --dry-run` y después
      sin `--dry-run`, para ver el correo real antes de que le llegue a nadie.

### C · Configuración del entorno

- [ ] **`composer install`.** Entró `barryvdh/laravel-dompdf` en la Fase 5: sin
      esto el informe mensual en PDF revienta.
- [ ] **`APP_URL` correcta y con HTTPS.** Los dos enlaces del correo se generan
      con ella; si apunta a otro sitio, el de baja **falla la comprobación de
      firma** y el usuario no puede darse de baja.
- [ ] **`APP_KEY` estable.** La firma del enlace de baja depende de ella: si se
      regenera, todos los enlaces ya enviados dejan de valer.
- [ ] **HTTPS obligatorio para la PWA.** El service worker solo se registra bajo
      TLS (o en localhost): sin certificado no hay instalación ni pantalla de sin
      conexión.
- [ ] **`APP_TIMEZONE` correcta.** Es la hora que se usa para todo usuario que
      aún no haya elegido la suya en su perfil.
- [ ] **`php artisan config:cache` después de tocar el `.env`**, y comprobar que
      ningún `env()` quedó fuera de un fichero de config (ya pasó una vez en
      `Settings\Subscription`).
- [ ] **`DEMO_ENABLED`** decide si la demo pública existe. Su refresco nocturno
      cuelga del mismo cron del grupo A.
- [ ] **Credenciales de R2 en producción** (`R2_*` y `FILESYSTEM_DISK=r2`). Ahí
      viven las velas y las capturas: sin ellas el gráfico de cada operación se
      queda vacío.

### D · Lo que falta para poder enseñarlo fuera

- [ ] **Páginas legales** (Términos y Privacidad). Hoy
      `Features::termsAndPrivacyPolicy()` sigue comentada en
      `config/jetstream.php` y el pie de la landing no las pinta.
- [ ] **Capturas reales de la landing.** Los mockups actuales son ilustraciones
      en HTML/CSS marcadas como tales; van a `public/img/landing/` cuando existan.
