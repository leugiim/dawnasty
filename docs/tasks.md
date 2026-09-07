# Dawnasty — Backlog de tareas (provisional)

Derivado de [`game-design.md`](game-design.md) en su estado actual. Se
reordena y amplía a medida que cerremos más piezas de diseño — esto no es
un plan fijo, es el backlog de trabajo mientras seguimos definiendo el
juego en paralelo.

Cómo leer esto:
- Backend en `api/`, siguiendo [`api/docs/architecture.md`](../api/docs/architecture.md)
  (módulos, CQRS, eventos vía Messenger, `ApiResult`, ids UUID v7 generados
  en frontend).
- Frontend en `app/` — apenas se ha hablado de él todavía, así que la
  primera tanda es solo lo mínimo para poder probar el backend de verdad.
- Donde falta un número de balance (coste, tasa, fórmula), la tarea se
  implementa igual con un **valor placeholder** — no bloquea la
  implementación, solo el pulido final.

## Backend (`api/`)

### Módulo `Village` — el núcleo del juego

- [x] Aggregate `Village`: capacidad de vivienda, población (total y
      desempleados), stock de recursos, edificios construidos
      (tipo + nivel), timestamp del último cálculo de producción.
      Primera vuelta: solo el Edificio Principal se construye al fundar;
      `housingCapacity()` da una capacidad base placeholder (10) sin sumar
      nada por Casas hasta que existan como edificio construible — módulo
      `Building` más abajo.
- [x] Comando `FoundVillage` (id v7 generado en frontend) → evento
      `VillageFounded`.
- [ ] Cálculo de **progreso offline** al leer una Village: producción
      acumulada desde `lastCalculatedAt` hasta ahora, aplicando el
      multiplicador por escasez de comida (game-design.md 4.2). Es el
      corazón técnico del idle.
- [ ] Lógica de **escasez de comida**: déficit + curva exponencial
      (techo 100%, suelo 20%, k=5 provisional), exime a edificios
      productores de comida.
- [ ] Lógica de **llegada de aldeanos**: gate por vivienda libre + comida
      sin déficit; tasa según nivel del Edificio Principal (valor
      placeholder hasta que se balancee).
- [ ] Comando `AssignVillagerToJob` (mover un aldeano desempleado a un
      oficio, o de vuelta a desempleado).
- [ ] Comando `UpgradeBuilding` (consume recursos según el catálogo, sube
      de nivel un edificio construido).
- [x] Query `GetVillage` → snapshot completo (recursos, población,
      edificios, nivel/nombre del Edificio Principal, etapa derivada de su
      nivel).

### Módulo `Building` — catálogo de edificios

- [ ] Definición de tipos de edificio + tabla de niveles (coste/efecto por
      nivel), incluida la categoría "productor de comida" (game-design.md
      4.3). Placeholder inicial: solo 2-3 edificios (Casa, Granja, Edificio
      Principal) para tener el loop completo funcionando; el resto del
      catálogo se amplía después.
- [ ] Query de catálogo (para que el frontend sepa qué se puede construir y
      a qué coste antes de intentarlo).

### Módulo `Prestige`

- [ ] Aggregate `Prestige`/`Dynasty`: Puntos de Dinastía totales +
      asignación actual en el árbol de nodos.
- [ ] Catálogo del árbol de nodos (prerequisitos, coste creciente por
      punto) — placeholder con pocos nodos para probar el mecanismo, no la
      lista final.
- [ ] Comando `PrestigeVillage`: resetea la Village, calcula los puntos
      ganados (fórmula placeholder), dispara el evento `VillagePrestiged`.
- [ ] `EventHandler` en `Prestige` escuchando `VillagePrestiged` → suma los
      puntos ganados al total.
- [ ] Comando `AllocatePrestigeNode` (y su reasignación libre / respec).
- [ ] Aplicar la asignación de nodos de `Prestige` a una `Village` recién
      fundada (bonificaciones activas desde el minuto 0).

### Transversal / a decidir

- [ ] Relación Village ↔ Player: ¿reusar el módulo `Player` ya creado como
      cuenta del jugador, o esperar a diseñar autenticación de verdad? No
      bloquea el MVP si de momento asumimos que no hay login real.
- [ ] Confirmar en la práctica que "compute-on-read" basta para el offline
      progress (ya decidido en el diseño — sin cron/worker en el
      servidor —, solo falta implementarlo y verlo funcionar).

## Frontend (`app/`) — apenas hablado, mínimo para probar el backend

- [ ] Cliente API: wrapper de `fetch`, manejo del envelope estándar
      (`{data}` / `{message, errors}`), generación de **UUID v7** al crear
      entidades (librería `uuid`/`uuidv7` — NO `crypto.randomUUID()`, que
      solo da v4).
- [ ] Flujo de creación de aldea para un jugador nuevo (genera el id v7,
      llama a `FoundVillage`).
- [ ] Dashboard de la aldea: recursos, población (total/desempleados),
      Edificio Principal (nivel + nombre según etapa), lista de edificios
      construidos.
- [ ] Indicador de escasez de comida (déficit actual, multiplicador de
      producción aplicado) — feedback visible del sistema de 4.2.
- [ ] Acción: mejorar un edificio (`UpgradeBuilding`).
- [ ] Acción: asignar/reasignar un aldeano a un oficio
      (`AssignVillagerToJob`).
- [ ] Pantalla de prestigio: puntos totales, árbol de nodos, asignar y
      reasignar (respec gratis).

## Orden sugerido para la primera vuelta

1. `Village` (aggregate + `FoundVillage` + `GetVillage`) con 2-3 edificios
   placeholder, sin escasez de comida todavía — solo tener aldea + recursos
   + edificios funcionando de punta a punta.
2. Progreso offline (cálculo por tiempo transcurrido).
3. Escasez de comida (multiplicador).
4. Llegada de aldeanos + asignación a oficios.
5. Catálogo de edificios más completo (`UpgradeBuilding` real, con más de
   2-3 edificios).
6. Frontend mínimo enchufado a todo lo anterior — probablemente en
   paralelo desde el paso 1, para poder ver resultados cuanto antes en vez
   de esperar a tener todo el backend cerrado.
7. `Prestige` (todo el módulo), al final porque depende de que exista una
   `Village` completa que resetear.

Backlog provisional: se reescribe según cerremos número de etapas, listas
de edificios/recursos definitivas, y el resto de números de balance
pendientes en `game-design.md`.
