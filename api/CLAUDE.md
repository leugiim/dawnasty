# api/ — Symfony backend

Arquitectura hexagonal obligatoria para todo el código de este proyecto.
Antes de tocar nada en `src/`, lee **[docs/architecture.md](docs/architecture.md)**
completo — define la estructura de carpetas (`Domain/`, `Application/`,
`Infrastructure/{Doctrine,Symfony}`), el patrón CQRS de `Application/`, el
uso de los 3 buses de Symfony Messenger (`command.bus`, `query.bus`,
`event.bus`) para casos de uso y eventos de dominio, y la política de IDs
(GUID generados siempre en el frontend, nunca en el backend).

Reglas que no tienen excepción:
- `Domain/` no importa nada de Symfony/Doctrine/Messenger.
- Un caso de uso = un `Command` o `Query` = un Handler.
- Los controllers (`Infrastructure/Symfony/Controller/`) solo parsean,
  despachan a un bus y devuelven la respuesta — cero lógica de negocio.
- Ningún `id` se genera en el backend, nunca.
