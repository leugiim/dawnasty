# Dawnasty — Documento de diseño

Documento vivo. Se va afinando sistema a sistema; de cada sistema cerrado
salen las tareas que se implementan en `api/` y `app/`. Nada de lo marcado
como "propuesta" está cerrado hasta que lo confirmemos aquí.

## 1. Pitch

Dawnasty es un **incremental/idle de construcción de aldea**: empiezas con
un terreno vacío y, construyendo casas, atrayendo aldeanos y asignándolos a
oficios (granja, mina, aserradero, herrería...), haces crecer tu aldea hasta
convertirla en un reino. Al llegar al tope de una partida, puedes
**refundar la dinastía**: reinicias la aldea a cambio de bonus permanentes
que hacen la siguiente vuelta más rápida.

## 2. Género y pilares (decidido)

- **Género**: incremental / idle builder. Un jugador. Sin multiplayer en el
  MVP (ver sección 5).
- **Pilares**:
  1. **Progreso incluso sin estar jugando.** Vuelves tras horas/días y ves
     cuánto ha producido tu aldea sola. Técnicamente esto se resuelve
     calculando la producción por tiempo transcurrido al leer el estado
     (comparando timestamps), no con un proceso corriendo en el servidor
     todo el rato.
  2. **Bucle de prestigio.** Al llegar al tope, reinicias a cambio de un
     bonus permanente — le da rejugabilidad infinita al juego, si no
     tendría un final claro y se acabaría.
  3. **Cadena de recursos con profundidad creciente**, que se amplía según
     la etapa en la que está la aldea (aldea → villa → ciudad → reino).

## 3. Core loop por sesión (decidido a alto nivel)

1. Entras a la partida → se calcula lo producido desde tu última visita
   (offline progress).
2. Revisas recursos/población, decides qué construir, mejorar, o a qué
   oficio mover aldeanos.
3. Construyes/mejoras edificios, reasignas aldeanos.
4. Sales → la aldea sigue produciendo sola hasta la próxima visita.
5. (Más adelante, al llegar al tope de una partida) decides si prestigias.

## 4. Sistemas — borrador a validar

### 4.1 Población y vivienda (cerrado)

- Las **Casas** dan capacidad de vivienda.
- Los aldeanos nuevos los produce específicamente el **Edificio
  Principal** (ver 4.3): de forma pasiva, sin necesitar trabajador
  asignado, a un ritmo que depende de su nivel.
- Esa producción de aldeanos solo se concreta en llegadas reales **si hay
  vivienda libre y la comida alcanza** (ver 4.2): con déficit de comida no
  llegan aldeanos nuevos aunque sobre espacio en las viviendas. En cuanto
  la comida vuelve a cubrir el consumo, las llegadas se reanudan.
- Un aldeano sin asignar está **desempleado**: no produce nada, solo
  consume comida y ocupa una vivienda. Se asigna a un edificio de trabajo
  para que produzca su recurso.
- Los aldeanos nunca se van (no hay emigración/hambruna que reduzca
  población): el único mecanismo de "retroceso" de población es un
  prestigio voluntario (sección 4.5).
- **Pendiente de balance** (no de diseño): la tasa exacta de producción de
  aldeanos por nivel del Edificio Principal — ver 4.3.

### 4.2 Recursos y escasez de comida (cerrado el mecanismo, abierta la lista de recursos)

- **Crudos** (propuesta, sin cerrar): Madera, Piedra, Comida, Mineral.
- **Procesados** (propuesta, sin cerrar): Tablones (de Madera),
  Herramientas/Lingotes (de Mineral, vía Herrería).
- Cada edificio se clasifica como **productor de comida** o no. La Granja
  es el primero, pero no será el único — se prevén más edificios
  productores de comida más adelante (a definir cuáles).

**Mecanismo de escasez de comida** (aplica a todo edificio que NO sea
productor de comida):

1. Cada tick se calcula el **déficit de comida** como fracción:
   `d = max(0, (consumo − producción) / consumo)`, entre 0 (comida cubierta
   o sobrante) y 1 (no se produce comida en absoluto).
2. Ese déficit da un **multiplicador de producción** que se aplica a todos
   los edificios no-productores-de-comida (los productores de comida
   siempre producen al 100%, para que nunca dejen de ser la salida segura
   de un déficit):

   ```
   multiplicador = suelo + (techo − suelo) × e^(−k × d)
   techo  = 100%
   suelo  = 20%   (nunca baja de aquí, aunque el déficit sea total)
   k      = 5     (constante de forma; ajustable en balanceo/playtesting)
   ```

   Curva resultante (con k=5): 0% de déficit → 100%; ~10% → ~65-70%;
   ~25% → ~40-45%; ~50% → ~25-30%; 100% → ~20% (suelo). Cae fuerte al
   principio y se suaviza según se acerca al suelo — un descuido pequeño ya
   duele, pero nunca colapsa la aldea a cero producción, así que siempre
   puede recuperarse sola con tiempo.
3. Si `d > 0` (cualquier déficit, por pequeño que sea), no llegan aldeanos
   nuevos (ver 4.1) aunque haya vivienda libre.

**Nota técnica para cuando se implemente**: esto implica que cada edificio
necesita un atributo tipo "categoría de producción" (¿es productor de
comida o no?) en vez de una excepción hardcodeada a "la Granja", ya que se
prevén más edificios productores de comida en el futuro.

- **Abierto**: la lista definitiva de recursos y qué edificios además de la
  Granja producen comida.

### 4.3 Edificios

**Modelo general de edificio (cerrado):** todo edificio (incluido el
Principal) se define por una **tabla de niveles**: para cada nivel, el
coste en recursos para alcanzarlo y el efecto que tiene a ese nivel
(producción por hora, capacidad, etc. según el tipo de edificio). Los
números concretos de esa tabla se rellenan en pasadas de balance
posteriores, edificio a edificio — lo que queda cerrado aquí es la forma
del modelo, no los valores.

**Edificio Principal (cerrado el rol, sin nombre ni números aún):**

- Único desde el principio de la partida (nivel 1 = Aldea recién fundada).
- No requiere trabajador asignado — produce aldeanos de forma pasiva
  (sección 4.1).
- Subirlo de nivel cuesta recursos, igual que cualquier otro edificio.
- Cuanto más alto su nivel, más rápida la producción de aldeanos (curva
  exacta pendiente de balance).
- **Sus niveles son los que determinan la Etapa** de la partida (Aldea /
  Villa / Ciudad / Reino) — ver 4.4. Es, en la práctica, el "medidor de
  progreso" central del juego.
- **Nombre (cerrado): evoluciona con la etapa.** Es el mismo edificio
  (mismo nivel, misma función, mismo modelo de datos) pero el nombre/arte
  que se le muestra al jugador cambia según en qué rango de nivel esté —
  es solo texto de UI derivado de la etapa, no un cambio de modelo:

  | Etapa | Nombre del Edificio Principal |
  |---|---|
  | Aldea | Choza del Fundador |
  | Villa | Salón del Clan |
  | Ciudad | Casa Solariega |
  | Reino | Trono de la Dinastía |

**Resto de edificios (propuesta inicial, por etapa — sin cerrar):**

| Etapa | Edificios propuestos |
|---|---|
| Aldea | Casa (vivienda), Granja (comida), Aserradero (madera), Cantera (piedra) |
| Villa | Mina (mineral), Herrería (mineral → herramientas), Almacén (↑ capacidad de recursos) |
| Ciudad | Mercado (conversión/comercio de recursos) |
| Reino | Por definir — probablemente ligado al sistema de prestigio |

### 4.4 Etapas: Aldea → Villa → Ciudad → Reino (cerrado el mecanismo, abierto el número de etapas)

- Las etapas son, literalmente, **rangos de nivel del Edificio Principal**
  (p. ej. niveles 1-9 = Aldea, 10-24 = Villa... los cortes exactos son
  números de balance, pendientes). No es un concepto independiente que
  haya que trackear aparte.
- **Abierto: cuántas etapas hay.** Aldea/Villa/Ciudad/Reino (4) es la
  lista de trabajo usada en este documento para razonar sobre el resto de
  sistemas, pero el número final de etapas **no está cerrado** — podrían
  ser más (etapas intermedias) o menos. Cualquier mención a estas 4 etapas
  concretas en el resto del documento (tabla de edificios en 4.3, nombres
  del Edificio Principal en 4.3, pilares en 2, pitch en 1) es igual de
  provisional que esta lista, no una lista cerrada de 4.
- Cada etapa desbloquea los edificios/recursos/oficios de la tabla de 4.3
  asociados a ella.
- Si un edificio necesita una condición de desbloqueo más fina que "estar
  en la etapa X", se expresa como "nivel mínimo N del Edificio Principal"
  en vez de por nombre de etapa — la etapa es la etiqueta que se le enseña
  al jugador, el nivel del Edificio Principal es el dato real.

### 4.5 Prestigio: "Refundar la dinastía" (cerrado el mecanismo)

- Al llegar a Reino (o a un hito dentro de Reino, a definir), puedes
  reiniciar la aldea.
- Al reiniciar ganas **Puntos de Dinastía** (nombre propuesto) según tu
  progreso en esa run. Son una moneda **acumulativa y permanente**: cada
  prestigio suma puntos al total histórico, nunca se pierden.
- Ese total se gasta en un **árbol de nodos** de bonificaciones permanentes
  (más producción, más capacidad de vivienda, etc.) que aplican desde el
  minuto 0 de todas las runs siguientes:
  - El árbol tiene **prerequisitos y ramificación**: para desbloquear
    ciertos nodos hace falta tener puntos invertidos antes en el nodo
    anterior de esa rama (reglas exactas de prerequisito = contenido de
    balance, la forma "árbol con ramas" es lo que queda cerrado aquí).
  - Hay nodos de **un solo punto** (desbloqueo binario de algo) y nodos
    **multi-punto** (varios niveles de la misma bonificación). En los
    multi-punto, cada punto adicional invertido en el mismo nodo **cuesta
    más que el anterior** (curva creciente, fórmula pendiente de balance).
  - **Respec siempre gratis**: en cualquier momento puedes resetear tu
    asignación actual y repartir tu total acumulado de otra forma, para
    probar una run distinta. Resetear la asignación no hace perder puntos
    del total.
- **Qué se resetea al prestigiar** (todo lo de la run: población,
  edificios, recursos, nivel del Edificio Principal) **vs. qué persiste**
  (Puntos de Dinastía totales + lo que tengas asignado en el árbol de
  nodos, que vuelve a aplicarse automáticamente desde el inicio de la
  nueva run).
- **Abierto**: fórmula de cuántos Puntos de Dinastía se ganan según el
  progreso de la run, contenido concreto del árbol (qué nodos existen,
  costes, prerequisitos exactos), y el hito exacto para poder prestigiar.

## 5. Fuera de alcance para el MVP

- Multijugador: nada de ataques, mapa compartido ni comercio entre
  jugadores. (Podría añadirse más adelante como capa social ligera —
  leaderboard, comparar reinos — sin rehacer el diseño base.)
- Combate/ejércitos con lógica compleja.
- Economía de mercado dinámica entre jugadores.

## 6. Preguntas abiertas para próximas iteraciones

- Lista definitiva de recursos y qué edificios, además de la Granja,
  producen comida (4.2).
- Lista definitiva de edificios por etapa (4.3) — la tabla es un borrador
  para reaccionar, no una lista cerrada.
- **Número final de etapas** (4.4): se está trabajando con 4
  (Aldea/Villa/Ciudad/Reino) como lista provisional, pero no está cerrado
  cuántas habrá realmente. Afecta a la tabla de edificios (4.3) y a los
  nombres del Edificio Principal (4.3), que habrá que revisar si cambia.
- Cortes exactos de nivel del Edificio Principal para cada etapa (4.4).
- Fórmula de Puntos de Dinastía ganados por run, contenido del árbol de
  nodos (qué nodos, costes, prerequisitos) y hito exacto para poder
  prestigiar (4.5).
- Todas las tablas de niveles (coste/efecto por nivel) de cada edificio,
  incluida la tasa de producción de aldeanos del Edificio Principal — se
  definen edificio a edificio en pasadas de balance, no hace falta
  cerrarlo todo por adelantado.
- Nombres/tema visual definitivo de etapas y edificios, encajando con el
  nombre "Dawnasty".

## 7. De este documento a tareas

Cuando cerremos un sistema (población, recursos, edificios, prestigio...)
lo convertimos en tareas concretas:

- En `api/`, cada sistema es candidato a **módulo** propio siguiendo
  [`api/docs/architecture.md`](../api/docs/architecture.md) (p. ej.
  `Village`, `Building`, `Villager`, `Resource`...) — el tamaño exacto de
  cada módulo se decide con el criterio de la sección 1.1 de ese documento
  cuando toque implementarlo.
- En `app/`, la UI correspondiente para ese sistema.

El backlog vivo derivado de este documento está en
[`tasks.md`](tasks.md), separado en backend/frontend, con el orden
sugerido para la primera vuelta de implementación.
