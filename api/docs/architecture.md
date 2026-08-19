# Arquitectura del backend (`api/`)

Este documento es la fuente de verdad de cómo se organiza y escribe código en
`api/`. Aplica a **todo** el código nuevo. Si algo existente no lo cumple, se
va migrando a medida que se toca.

## Resumen rápido (TL;DR)

- Arquitectura **hexagonal organizada por módulos**: cada módulo de negocio
  (`Player`, `Game`, ...) es una carpeta en `src/` con sus propias 3
  subcarpetas `Domain/`, `Application/`, `Infrastructure/`.
- Lo transversal a todos los módulos vive en el módulo especial `_Shared/`
  (mismo patrón de 3 carpetas por dentro).
- Un módulo agrupa una **capacidad de negocio cohesionada**, no una entidad
  suelta ni "todo el dominio". Puede tener varias entidades si están
  relacionadas. Ver sección 1.1 para el criterio de tamaño.
- `Application/` de cada módulo usa **CQRS**: casos de uso = `Command/`
  (escritura) o `Query/` (lectura), cada uno con su Handler.
- `Domain/` de cada módulo define **interfaces** de repositorio;
  `Infrastructure/Doctrine/` del mismo módulo las implementa.
- `Infrastructure/Symfony/` de cada módulo tiene los controllers y todo lo
  que sea "cableado" con el framework HTTP.
- Los **eventos de dominio** se publican y consumen con **Symfony
  Messenger** (bus dedicado `event.bus`), pudiendo cruzar de un módulo a
  otro (un módulo escucha eventos publicados por otro).
- Todos los **IDs son GUID en formato UUID v7** (ordenables/secuenciales
  por tiempo de creación) y se **generan siempre en el frontend**. El
  backend nunca genera un id nuevo, solo los valida (versión v7 incluida) y
  los usa.

---

## 1. Módulos en `src/`

```
src/
├── Player/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
│       ├── Doctrine/
│       └── Symfony/
├── Game/                    # ejemplo de otro módulo futuro
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
│       ├── Doctrine/
│       └── Symfony/
└── _Shared/                 # transversal a todos los módulos
    ├── Domain/
    ├── Application/
    └── Infrastructure/
        ├── Doctrine/
        └── Symfony/
```

`_Shared` lleva el guion bajo delante a propósito: así queda siempre primero
en el listado de carpetas y se distingue a simple vista de un módulo de
negocio real.

Regla de dependencias, tanto dentro de un módulo como entre módulos (de
fuera hacia dentro, nunca al revés):

```
Infrastructure  →  Application  →  Domain
```

- **`<Modulo>/Domain/`** no depende de nada de framework. Cero imports de
  Symfony, Doctrine, Messenger, HttpFoundation, etc. Es PHP puro +
  interfaces (puertos). Puede depender del `Domain/` de `_Shared` (value
  objects base, eventos base), nunca de la `Application/` o
  `Infrastructure/` de otro módulo.
- **`<Modulo>/Application/`** orquesta el dominio de su propio módulo. Puede
  depender de **Symfony Messenger** (los Command/Query/Event Handlers son
  "message handlers"), y puede depender del `Domain/` de otros módulos
  cuando necesite consultarlos (p. ej. vía su repositorio), pero no de la
  `Infrastructure/` de otro módulo.
- **`<Modulo>/Infrastructure/`** es donde vive el framework para ese módulo:
  Doctrine (persistencia) y Symfony (HTTP, controllers, listeners).

Todos los módulos siguen la misma forma interna que ya usábamos:

```
Player/
├── Domain/
│   ├── Player.php                     # Entidad / Aggregate Root
│   ├── PlayerId.php                   # Value Object del id
│   ├── PlayerRepositoryInterface.php  # Puerto (interfaz)
│   └── Event/
│       └── PlayerWasCreated.php       # Evento de dominio
├── Application/
│   ├── Command/
│   │   └── CreatePlayer/
│   │       ├── CreatePlayerCommand.php
│   │       └── CreatePlayerCommandHandler.php
│   ├── Query/
│   │   └── GetPlayer/
│   │       ├── GetPlayerQuery.php
│   │       ├── GetPlayerQueryHandler.php
│   │       └── PlayerView.php         # DTO de salida (read model)
│   └── EventHandler/
│       └── PlayerWasCreated/
│           └── SendWelcomeEmailHandler.php
└── Infrastructure/
    ├── Doctrine/
    │   ├── Repository/
    │   │   └── DoctrinePlayerRepository.php   # implements PlayerRepositoryInterface
    │   └── Mapping/
    │       └── Player.orm.xml                  # mapping ORM (no attributes en la entidad)
    └── Symfony/
        └── Controller/
            └── PlayerController.php
```

Y `_Shared/` para lo transversal, con la misma forma (aunque no siempre use
las 3 subcarpetas si no hace falta):

```
_Shared/
├── Domain/
│   ├── ValueObject/
│   │   └── Id.php                     # VO base para todos los *Id
│   └── Event/
│       ├── DomainEvent.php            # Interfaz marcadora
│       └── RecordsDomainEvents.php    # Trait para agregados
└── Infrastructure/
    └── Symfony/
        └── Controller/
            └── HealthController.php   # no pertenece a ningún módulo de negocio
```

### 1.1 Cómo decidir el tamaño de un módulo

Un módulo es una **capacidad de negocio cohesionada**, no una entidad ni una
tabla. La pregunta para agrupar o separar es: *¿estos conceptos cambian
juntos y solo tienen sentido el uno en relación al otro?*

- **Ni un módulo por entidad.** Si `Game` tiene las entidades `Game`,
  `GameRound` y `GameState` y siempre se manipulan juntas dentro del mismo
  caso de uso, van en el mismo módulo `Game/Domain/`. Crear
  `Game/`, `GameRound/`, `GameState/` como tres módulos separados solo
  añade indirección y repite el mismo cableado (buses, repos, mapping) tres
  veces sin ganar nada.
- **Ni un módulo gigante con todo.** Si un módulo empieza a mezclar
  conceptos que no dependen entre sí (p. ej. `Player` e `Inventory` no
  necesitan compartir código de dominio, cada uno se puede crear/leer/borrar
  sin tocar al otro), van en módulos separados aunque estén relacionados por
  una referencia (un `Inventory` que referencia un `PlayerId`).
- Como referencia orientativa (no una regla estricta): un módulo suele tener
  entre 1 y ~5 entidades/aggregates. Si se acerca o pasa de ahí, es señal de
  que probablemente hay dos capacidades de negocio mezcladas y conviene
  partirlo.
- Los módulos se relacionan entre sí de dos formas, nunca importando la
  `Infrastructure/` ajena directamente:
  1. Un módulo consulta el `Domain/` (interfaz de repositorio) de otro desde
     su propia `Application/`.
  2. Un módulo reacciona a un evento de dominio publicado por otro, con un
     `Application/EventHandler/` que escucha ese evento en el `event.bus`.

---

## 2. `<Modulo>/Domain/`

Contiene el modelo de negocio del módulo: entidades/aggregates, value
objects, interfaces de repositorio, eventos de dominio y excepciones de
dominio.

Reglas:

- **Cero dependencias de framework.** Nada de `#[ORM\...]`, nada de
  `Symfony\...`, nada de `Doctrine\...`.
- Las entidades se mapean a Doctrine mediante **mapping XML** en
  `<Modulo>/Infrastructure/Doctrine/Mapping/`, no con atributos en la clase.
  Así la entidad de dominio no sabe que existe Doctrine.
- Los repositorios se definen aquí como **interfaces**
  (`PlayerRepositoryInterface`). La implementación real vive en
  `<Modulo>/Infrastructure/Doctrine/Repository/`.
- Los agregados que necesiten publicar eventos usan el trait
  `_Shared\Domain\Event\RecordsDomainEvents` (guarda eventos en memoria con
  `record()` y los expone con `pullDomainEvents()`).
- Las excepciones de dominio (reglas de negocio violadas) viven en
  `<Modulo>/Domain/Exception/` y extienden una excepción de dominio base de
  `_Shared`.
- Los VOs de id de cada módulo (`PlayerId`, ...) extienden el VO base
  `_Shared\Domain\ValueObject\Id`.

---

## 3. `<Modulo>/Application/` — Casos de uso con CQRS

Cada caso de uso es un **Command** (cambia estado) o una **Query** (lee
estado), nunca ambas cosas. Un Command/Query = un Handler. No hay "services"
genéricos con múltiples métodos.

### Comandos (`Application/Command/<CasoDeUso>/`)

- `XxxCommand`: DTO inmutable con los datos de entrada. Sin lógica.
- `XxxCommandHandler`: orquesta el dominio del módulo — carga agregados vía
  el repositorio (interfaz de `Domain/`), invoca comportamiento del
  dominio, persiste, y **publica los eventos de dominio pendientes** (ver
  sección 5). No devuelve entidades de dominio; si necesita devolver algo,
  devuelve un id o un DTO simple.
- Se registra como message handler del bus de comandos:

  ```php
  #[AsMessageHandler(bus: 'command.bus')]
  final class CreatePlayerCommandHandler
  {
      public function __construct(
          private PlayerRepositoryInterface $players,
          private MessageBusInterface $eventBus, // named: 'event.bus'
      ) {}

      public function __invoke(CreatePlayerCommand $command): void
      {
          $player = Player::create(
              PlayerId::fromString($command->id), // id viene del cliente, nunca se genera aquí
              $command->name,
          );

          $this->players->save($player);

          foreach ($player->pullDomainEvents() as $event) {
              $this->eventBus->dispatch($event);
          }
      }
  }
  ```

### Queries (`Application/Query/<CasoDeUso>/`)

- `XxxQuery`: DTO con los criterios de búsqueda.
- `XxxQueryHandler`: lee (vía repositorio u otra fuente de lectura
  optimizada) y devuelve un **read model** (`XxxView`), nunca la entidad de
  dominio directamente.
- Registrado en el bus de queries: `#[AsMessageHandler(bus: 'query.bus')]`.

### Event Handlers (`Application/EventHandler/<Evento>/`)

- Reaccionan a un evento de dominio ya publicado — del propio módulo o de
  otro módulo (enviar email, actualizar una proyección, crear una entidad
  en otro módulo relacionado, etc.).
- Registrados en el bus de eventos: `#[AsMessageHandler(bus: 'event.bus')]`.
- Un evento puede tener **0, 1 o varios** handlers, en el mismo módulo o en
  módulos distintos al que lo publicó.

---

## 4. `<Modulo>/Infrastructure/`

### 4.1 `<Modulo>/Infrastructure/Doctrine/`

- `Repository/`: implementaciones concretas de la interfaz
  `<Modulo>/Domain/*RepositoryInterface`. Nombradas `Doctrine<Agregado>Repository`
  (p. ej. `DoctrinePlayerRepository implements PlayerRepositoryInterface`).
  Son las únicas clases que usan `EntityManagerInterface` / `Doctrine\ORM`.
- `Mapping/`: ficheros `*.orm.xml` con el mapping de cada entidad de
  `Domain/` de ese módulo. Registrados en `config/packages/doctrine.yaml`
  apuntando a esta carpeta (un bloque de mapping por módulo).
- Migraciones (`migrations/` en la raíz del proyecto, autogeneradas por
  `make:migration`) se generan a partir de este mapping.

### 4.2 `<Modulo>/Infrastructure/Symfony/`

- `Controller/`: controllers HTTP, **delgados**. Su única responsabilidad es:
  1. parsear/deserializar el request a un `Command`/`Query`,
  2. despacharlo al bus correspondiente (`command.bus` / `query.bus`),
  3. mapear el resultado (o el `HandledStamp`) a una `Response`.

  No hacen validación de negocio, no acceden a repositorios ni al
  `EntityManager` directamente, no contienen lógica de dominio.

  ```php
  #[Route('/api/players', methods: ['POST'])]
  public function create(Request $request, MessageBusInterface $commandBus): Response
  {
      $payload = json_decode($request->getContent(), true);

      $commandBus->dispatch(new CreatePlayerCommand(
          id: $payload['id'], // GUID generado en el frontend
          name: $payload['name'],
      ));

      return new JsonResponse(null, Response::HTTP_CREATED);
  }
  ```

- Aquí también van, si hacen falta más adelante: Kernel event
  subscribers/listeners, security voters, normalizers propios del módulo.

Lo que no pertenece a ningún módulo de negocio concreto (healthcheck,
listeners globales de excepciones, etc.) va en `_Shared/Infrastructure/`.

---

## 5. Eventos de dominio con Symfony Messenger

Se usan **3 buses** de Messenger, cada uno con su propósito, configurados en
`config/packages/messenger.yaml`:

| Bus | Propósito | Quién despacha | Quién escucha |
|---|---|---|---|
| `command.bus` | Ejecutar un caso de uso de escritura | Controllers (`<Modulo>/Infrastructure/Symfony`) | `<Modulo>/Application/Command/*/**Handler` |
| `query.bus` | Ejecutar un caso de uso de lectura | Controllers (`<Modulo>/Infrastructure/Symfony`) | `<Modulo>/Application/Query/*/**Handler` |
| `event.bus` | Notificar que algo ocurrió en el dominio | `<Modulo>/Application/Command/*/**Handler`, tras persistir | `<Modulo o otro>/Application/EventHandler/*/**Handler` |

Todos los buses son **síncronos** (sin transporte/cola) por defecto: se
comportan como un bus de mensajes en memoria, dan desacoplamiento y
consistencia de patrón sin la complejidad operativa de colas. El día que un
evento concreto necesite procesarse de forma asíncrona, se le añade
transporte propio (`config/packages/messenger.yaml` → `routing`) sin tocar
ni el evento ni el handler.

El `event.bus` es precisamente lo que permite que un módulo reaccione a lo
que pasa en otro sin que se importen `Infrastructure/` ni `Application/`
entre sí: el módulo A publica el evento, el módulo B lo escucha desde su
propio `Application/EventHandler/`, y solo comparten la clase del evento
(que vive en `Domain/` del módulo que lo origina).

Flujo de un evento:

1. Un agregado de `<Modulo>/Domain/` registra el evento con
   `$this->record(new PlayerWasCreated($this->id))` (vía el trait
   `RecordsDomainEvents`).
2. El `CommandHandler` de `<Modulo>/Application/`, después de
   `repository->save(...)`, hace `pullDomainEvents()` sobre el agregado y
   despacha cada evento al `event.bus`.
3. Cualquier `Application/EventHandler/<Evento>/*Handler` (del mismo módulo
   o de otro) tagueado con `#[AsMessageHandler(bus: 'event.bus')]` para ese
   evento se ejecuta.

---

## 6. Política de IDs: GUID (UUID v7) generados siempre en el frontend

Regla dura, sin excepciones: **el backend nunca genera un id nuevo**.

- Formato: **UUID v7** (string, 36 caracteres, con guiones) — a diferencia
  de v4, v7 codifica un timestamp en los primeros bits, así que los ids
  generados más tarde ordenan lexicográficamente después de los anteriores.
  Eso los hace secuenciales/ordenables por fecha de creación, lo cual
  importa para el rendimiento de los índices de la base de datos (menos
  fragmentación que con ids v4 totalmente aleatorios) y para poder
  ordenar por id como aproximación de "orden de creación" sin depender de
  un `createdAt`.
- El **frontend** (`app/`) genera el id como UUID v7 antes de llamar a la
  API, y lo manda como parte del payload de creación. `crypto.randomUUID()`
  del navegador solo genera v4, así que en `app/` hace falta una librería
  que soporte v7 (p. ej. el paquete npm `uuid` ≥ 9.1 vía `uuidv7()`, o
  `uuidv7`) — se deja fijado aquí para que no se use `crypto.randomUUID()`
  por defecto cuando se implemente la generación de ids en el frontend.
- Todo `Command` que crea un recurso **requiere** el campo `id` — no hay
  autoincrement, no hay generación de ids en ningún `CommandHandler`,
  `Repository` o entidad de `Domain/`.
- El value object de id (`<Modulo>/Domain/<Modulo>Id.php`, extendiendo el
  VO base `_Shared/Domain/ValueObject/Id.php`) **valida** el formato al
  construirse — formato UUID **y** que el nibble de versión sea `7`
  (lanza una excepción de dominio si no es un UUID v7 válido) — pero
  **nunca genera uno nuevo** por sí mismo — no tiene método `generate()` ni
  `random()`.
- Adicionalmente, en `Infrastructure/Symfony`, el DTO de entrada del
  controller puede validar el formato con el constraint
  `Symfony\Component\Validator\Constraints\Uuid` (con
  `versions: [Uuid::V7_MONOTONIC]` o equivalente) para devolver un 400 antes
  de llegar siquiera al bus, si se considera necesario.
- En Doctrine, la columna id se mapea como `type="guid"` (o `uuid` si se usa
  el bridge `symfony/uid`) **sin** estrategia de generación
  (`GENERATED_VALUE`/`IDENTITY`/`AUTO`). El id siempre llega ya asignado
  desde `Application/`. El tipo de columna no cambia entre v4 y v7 — sigue
  siendo un string/GUID normal, solo cambia el contenido.

Por qué: simplifica el modelo offline-first / optimistic UI del frontend
(puede crear entidades localmente con id definitivo antes de que el backend
confirme), evita relaciones "temporales" en el cliente mientras se espera un
id generado por el servidor, y con v7 además se gana orden temporal e
índices más eficientes frente a v4.

---

## 7. Checklist para un caso de uso nuevo

1. ¿Es un módulo nuevo o entra en uno existente? Aplica el criterio de la
   sección 1.1. Si es nuevo, crea `src/<Modulo>/{Domain,Application,Infrastructure/{Doctrine,Symfony}}`.
2. ¿Toca modelo de negocio nuevo? Añade/edita la entidad y sus VOs en
   `<Modulo>/Domain/`. Sin imports de framework.
3. ¿Necesita persistencia? Define/reusa la interfaz en
   `<Modulo>/Domain/<Modulo>RepositoryInterface.php` e impleméntala en
   `<Modulo>/Infrastructure/Doctrine/Repository/`, con su mapping en
   `<Modulo>/Infrastructure/Doctrine/Mapping/`.
4. ¿Es una escritura? Crea `<Modulo>/Application/Command/<CasoDeUso>/` con
   el Command + Handler (`#[AsMessageHandler(bus: 'command.bus')]`).
   ¿Es una lectura? Crea `<Modulo>/Application/Query/<CasoDeUso>/` con el
   Query + Handler (`#[AsMessageHandler(bus: 'query.bus')]`) devolviendo un
   `*View`.
5. ¿El caso de uso produce algo relevante para el resto del sistema?
   Define el evento en `<Modulo>/Domain/Event/`, regístralo en el agregado
   con `record(...)`, despáchalo desde el `CommandHandler` tras persistir, y
   añade los `Application/EventHandler/<Evento>/` que reaccionen (en este
   módulo o en otro).
6. Expón el caso de uso vía HTTP: controller delgado en
   `<Modulo>/Infrastructure/Symfony/Controller/`, que solo parsea, despacha
   al bus, y devuelve la respuesta.
7. ¿Hay un id nuevo de por medio? Viene del cliente. No lo generes en el
   backend en ningún punto de este flujo.
