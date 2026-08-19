# Arquitectura del backend (`api/`)

Este documento es la fuente de verdad de cómo se organiza y escribe código en
`api/`. Aplica a **todo** el código nuevo. Si algo existente no lo cumple, se
va migrando a medida que se toca.

## Resumen rápido (TL;DR)

- Arquitectura **hexagonal** con 3 carpetas en `src/`: `Domain/`,
  `Application/`, `Infrastructure/`.
- `Application/` usa **CQRS**: casos de uso = `Command/` (escritura) o
  `Query/` (lectura), cada uno con su Handler.
- `Domain/` define **interfaces** de repositorio; `Infrastructure/Doctrine/`
  las implementa.
- `Infrastructure/Symfony/` tiene los controllers y todo lo que sea "cableado"
  con el framework HTTP.
- Los **eventos de dominio** se publican y consumen con **Symfony
  Messenger** (bus dedicado `event.bus`).
- Todos los **IDs son GUID (UUID v4)** y se **generan siempre en el
  frontend**. El backend nunca genera un id nuevo, solo los valida y los usa.

---

## 1. Las 3 carpetas de `src/`

```
src/
├── Domain/
├── Application/
└── Infrastructure/
    ├── Doctrine/
    └── Symfony/
```

Regla de dependencias (de fuera hacia dentro, nunca al revés):

```
Infrastructure  →  Application  →  Domain
```

- **`Domain/`** no depende de nada. Cero imports de Symfony, Doctrine,
  Messenger, HttpFoundation, etc. Es PHP puro + interfaces (puertos).
- **`Application/`** orquesta el dominio. Puede depender de **Symfony
  Messenger** (porque los Command/Query/Event Handlers son "message
  handlers"), pero **no** de Doctrine ni de HttpFoundation.
- **`Infrastructure/`** es donde vive todo el framework: Doctrine
  (persistencia) y Symfony (HTTP, controllers, listeners, configuración).

Dentro de cada carpeta se agrupa por módulo/agregado (`Player`, `Game`,
`Shared`, ...), no al revés. Ejemplo con el módulo `Player`:

```
src/
├── Domain/
│   ├── Player/
│   │   ├── Player.php                     # Entidad / Aggregate Root
│   │   ├── PlayerId.php                   # Value Object del id
│   │   ├── PlayerRepositoryInterface.php  # Puerto (interfaz)
│   │   └── Event/
│   │       └── PlayerWasCreated.php       # Evento de dominio
│   └── Shared/
│       ├── ValueObject/
│       │   └── Id.php                     # VO base para todos los *Id
│       └── Event/
│           ├── DomainEvent.php            # Interfaz marcadora
│           └── RecordsDomainEvents.php    # Trait para agregados
│
├── Application/
│   ├── Command/
│   │   └── CreatePlayer/
│   │       ├── CreatePlayerCommand.php
│   │       └── CreatePlayerCommandHandler.php
│   ├── Query/
│   │   └── GetPlayer/
│   │       ├── GetPlayerQuery.php
│   │       ├── GetPlayerQueryHandler.php
│   │       └── PlayerView.php             # DTO de salida (read model)
│   └── EventHandler/
│       └── PlayerWasCreated/
│           └── SendWelcomeEmailHandler.php
│
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

`Shared/` (dentro de cada una de las 3 carpetas) es para lo transversal:
value objects base, excepciones base, interfaces comunes. No es un módulo de
negocio.

---

## 2. `Domain/`

Contiene el modelo de negocio: entidades/aggregates, value objects,
interfaces de repositorio, eventos de dominio y excepciones de dominio.

Reglas:

- **Cero dependencias de framework.** Nada de `#[ORM\...]`, nada de
  `Symfony\...`, nada de `Doctrine\...` (salvo `ramsey/uuid` o
  `symfony/uid` *solo* si se usa exclusivamente como librería de valor —
  ver sección de IDs — evita incluso eso si se puede resolver con validación
  manual de formato).
- Las entidades se mapean a Doctrine mediante **mapping XML** en
  `Infrastructure/Doctrine/Mapping/`, no con atributos en la clase. Así la
  entidad de dominio no sabe que existe Doctrine.
- Los repositorios se definen aquí como **interfaces** (`PlayerRepositoryInterface`).
  La implementación real vive en `Infrastructure/Doctrine/Repository/`.
- Los agregados que necesiten publicar eventos usan el trait
  `Domain\Shared\Event\RecordsDomainEvents` (guarda eventos en memoria con
  `record()` y los expone con `pullDomainEvents()`).
- Las excepciones de dominio (reglas de negocio violadas) viven en
  `Domain/<Modulo>/Exception/` y extienden una excepción de dominio base.

---

## 3. `Application/` — Casos de uso con CQRS

Cada caso de uso es un **Command** (cambia estado) o una **Query** (lee
estado), nunca ambas cosas. Un Command/Query = un Handler. No hay "services"
genéricos con múltiples métodos.

### Comandos (`Application/Command/<CasoDeUso>/`)

- `XxxCommand`: DTO inmutable con los datos de entrada. Sin lógica.
- `XxxCommandHandler`: orquesta el dominio — carga agregados vía el
  repositorio (interfaz de `Domain/`), invoca comportamiento del dominio,
  persiste, y **publica los eventos de dominio pendientes** (ver sección 5).
  No devuelve entidades de dominio; si necesita devolver algo, devuelve un
  id o un DTO simple.
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

- Reaccionan a un evento de dominio ya publicado (enviar email, actualizar
  una proyección, seed de otro agregado, etc.).
- Registrados en el bus de eventos: `#[AsMessageHandler(bus: 'event.bus')]`.
- Un evento puede tener **0, 1 o varios** handlers.

---

## 4. `Infrastructure/`

### 4.1 `Infrastructure/Doctrine/`

- `Repository/`: implementaciones concretas de las interfaces de
  `Domain/*/*RepositoryInterface`. Nombradas `Doctrine<Agregado>Repository`
  (p. ej. `DoctrinePlayerRepository implements PlayerRepositoryInterface`).
  Son las únicas clases que usan `EntityManagerInterface` / `Doctrine\ORM`.
- `Mapping/`: ficheros `*.orm.xml` con el mapping de cada entidad de
  `Domain/`. Registrados en `config/packages/doctrine.yaml` apuntando a esta
  carpeta.
- Migraciones (`migrations/` en la raíz del proyecto, autogeneradas por
  `make:migration`) se generan a partir de este mapping.

### 4.2 `Infrastructure/Symfony/`

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
  subscribers/listeners, security voters, normalizers, comandos de consola
  de infraestructura (no confundir con los `Command` de CQRS).

---

## 5. Eventos de dominio con Symfony Messenger

Se usan **3 buses** de Messenger, cada uno con su propósito, configurados en
`config/packages/messenger.yaml`:

| Bus | Propósito | Quién despacha | Quién escucha |
|---|---|---|---|
| `command.bus` | Ejecutar un caso de uso de escritura | Controllers (`Infrastructure/Symfony`) | `Application/Command/*/**Handler` |
| `query.bus` | Ejecutar un caso de uso de lectura | Controllers (`Infrastructure/Symfony`) | `Application/Query/*/**Handler` |
| `event.bus` | Notificar que algo ocurrió en el dominio | `Application/Command/*/**Handler`, tras persistir | `Application/EventHandler/*/**Handler` |

Todos los buses son **síncronos** (sin transporte/cola) por defecto: se
comportan como un bus de mensajes en memoria, dan desacoplamiento y
consistencia de patrón sin la complejidad operativa de colas. El día que un
evento concreto necesite procesarse de forma asíncrona, se le añade
transporte propio (`config/packages/messenger.yaml` → `routing`) sin tocar
ni el evento ni el handler.

Flujo de un evento:

1. Un agregado de `Domain/` registra el evento con
   `$this->record(new PlayerWasCreated($this->id))` (vía el trait
   `RecordsDomainEvents`).
2. El `CommandHandler` de `Application/`, después de `repository->save(...)`,
   hace `pullDomainEvents()` sobre el agregado y despacha cada evento al
   `event.bus`.
3. Cualquier `Application/EventHandler/<Evento>/*Handler` tagueado con
   `#[AsMessageHandler(bus: 'event.bus')]` para ese evento se ejecuta.

Los eventos de dominio (las clases del evento en sí, p. ej.
`PlayerWasCreated`) viven en `Domain/<Modulo>/Event/` porque describen algo
que pasó en el dominio — son datos, no dependen de Messenger para nada
(Messenger solo los transporta).

---

## 6. Política de IDs: GUID generados siempre en el frontend

Regla dura, sin excepciones: **el backend nunca genera un id nuevo**.

- Formato: UUID v4 (string, 36 caracteres, con guiones).
- El **frontend** (`app/`) genera el id (`crypto.randomUUID()`) antes de
  llamar a la API, y lo manda como parte del payload de creación.
- Todo `Command` que crea un recurso **requiere** el campo `id` — no hay
  autoincrement, no hay `Uuid::v4()` en ningún `CommandHandler`,
  `Repository` o entidad de `Domain/`.
- El value object de id (`Domain/<Modulo>/<Modulo>Id.php`, extendiendo el
  VO base `Domain/Shared/ValueObject/Id.php`) **valida** el formato al
  construirse (lanza una excepción de dominio si no es un UUID válido) pero
  **nunca genera uno nuevo** por sí mismo — no tiene método `generate()` ni
  `random()`.
- Adicionalmente, en `Infrastructure/Symfony`, el DTO de entrada del
  controller puede validar el formato con el constraint
  `Symfony\Component\Validator\Constraints\Uuid` para devolver un 400 antes
  de llegar siquiera al bus, si se considera necesario.
- En Doctrine, la columna id se mapea como `type="guid"` (o `uuid` si se usa
  el bridge `symfony/uid`) **sin** estrategia de generación
  (`GENERATED_VALUE`/`IDENTITY`/`AUTO`). El id siempre llega ya asignado
  desde `Application/`.

Por qué: simplifica el modelo offline-first / optimistic UI del frontend
(puede crear entidades localmente con id definitivo antes de que el backend
confirme), y evita relaciones "temporales" en el cliente mientras se espera
un id generado por el servidor.

---

## 7. Checklist para un caso de uso nuevo

1. ¿Toca modelo de negocio nuevo? Añade/edita la entidad y sus VOs en
   `Domain/<Modulo>/`. Sin imports de framework.
2. ¿Necesita persistencia? Define/reusa la interfaz en
   `Domain/<Modulo>/<Modulo>RepositoryInterface.php` e impleméntala en
   `Infrastructure/Doctrine/Repository/`, con su mapping en
   `Infrastructure/Doctrine/Mapping/`.
3. ¿Es una escritura? Crea `Application/Command/<CasoDeUso>/` con el
   Command + Handler (`#[AsMessageHandler(bus: 'command.bus')]`).
   ¿Es una lectura? Crea `Application/Query/<CasoDeUso>/` con el Query +
   Handler (`#[AsMessageHandler(bus: 'query.bus')]`) devolviendo un `*View`.
4. ¿El caso de uso produce algo relevante para el resto del sistema?
   Define el evento en `Domain/<Modulo>/Event/`, regístralo en el agregado
   con `record(...)`, despáchalo desde el `CommandHandler` tras persistir, y
   añade los `Application/EventHandler/<Evento>/` que reaccionen.
5. Expón el caso de uso vía HTTP: controller delgado en
   `Infrastructure/Symfony/Controller/`, que solo parsea, despacha al bus, y
   devuelve la respuesta.
6. ¿Hay un id nuevo de por medio? Viene del cliente. No lo generes en el
   backend en ningún punto de este flujo.
