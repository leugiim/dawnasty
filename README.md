# Dawnasty

Juego de navegador. Monorepo con backend (API) y frontend (SPA) separados.

## Estructura

```
.
├── api/   # Backend — Symfony 7 (webapp), Doctrine ORM + SQLite
└── app/   # Frontend — SvelteKit + TypeScript
```

## api/ — Symfony

Backend REST. Symfony 7 (flavor `webapp`), Doctrine ORM sobre SQLite, CORS
habilitado para `/api/*` (nelmio/cors-bundle) pensado para ser consumido
desde `app/` en desarrollo local.

```bash
cd api
composer install
symfony server:start        # o: php -S 127.0.0.1:8000 -t public
```

Healthcheck: `GET /api/health` → `{"status":"ok"}`

Base de datos (el archivo SQLite se crea solo al conectar):

```bash
php bin/console doctrine:migrations:migrate
```

Flujo de migraciones:

```bash
php bin/console make:entity                 # crear/editar entidad
php bin/console make:migration               # generar migración
php bin/console doctrine:migrations:migrate   # aplicar migraciones pendientes
```

## app/ — SvelteKit

Frontend SPA. SvelteKit + TypeScript, ESLint + Prettier, `adapter-auto`.

```bash
cd app
npm install
npm run dev -- --open
```

## Desarrollo

Arranca ambos servidores en paralelo (dos terminales): `api` en
`http://127.0.0.1:8000` y `app` en `http://localhost:5173`. El CORS de la
API ya permite peticiones desde `localhost`/`127.0.0.1` en cualquier puerto.
