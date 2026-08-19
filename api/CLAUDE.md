# api/ — Symfony backend

Arquitectura hexagonal **organizada por módulos** obligatoria para todo el
código de este proyecto. Antes de tocar nada en `src/`, lee
**[docs/architecture.md](docs/architecture.md)** completo — define los
módulos de negocio en `src/<Modulo>/` con sus propias `Domain/`,
`Application/`, `Infrastructure/{Doctrine,Symfony}`, el módulo `_Shared/`
para lo transversal, el criterio para decidir el tamaño de un módulo, el
patrón CQRS de `Application/`, el uso de los 3 buses de Symfony Messenger
(`command.bus`, `query.bus`, `event.bus`) para casos de uso y eventos de
dominio, y la política de IDs (GUID **UUID v7**, secuenciales, generados
siempre en el frontend, nunca en el backend).

Reglas que no tienen excepción:
- Cada módulo de negocio (`Player`, `Game`, ...) vive en `src/<Modulo>/`
  con sus 3 carpetas dentro. Lo transversal va en `src/_Shared/`.
- Ni un módulo por entidad ni un módulo con todo el dominio: un módulo es
  una capacidad de negocio cohesionada (ver sección 1.1 del documento).
- `<Modulo>/Domain/` no importa nada de Symfony/Doctrine/Messenger.
- Un caso de uso = un `Command` o `Query` = un Handler.
- Los controllers (`<Modulo>/Infrastructure/Symfony/Controller/`) solo
  parsean, despachan a un bus y devuelven la respuesta — cero lógica de
  negocio.
- Ningún `id` se genera en el backend, nunca.
- Los ids son **UUID v7** (no v4): secuenciales por timestamp. La
  validación del VO de id comprueba también que la versión sea 7.
- Los controllers no construyen la `Response` de éxito/error a mano: 
  devuelven un `ApiResult`/`ApiResultList` (envuelto en `{"data": ...}` por
  el listener de `kernel.view`, `200`) o `void` (`204`), y lanzan
  excepciones que implementan `ApiException`/`ApiValidationException` para
  los errores (envueltas en `{"message", "errors"}` por el listener de
  `kernel.exception`). Ver sección 7 de `docs/architecture.md`.
