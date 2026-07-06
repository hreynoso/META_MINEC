# META MINEC

Plataforma institucional para el **Ministerio de Economía de El Salvador (MINEC)**,
desarrollada como cooperación técnica desde la **República Dominicana (MINPRE)**.

El andamiaje sigue el stack ya validado en producción en SED/SPCG: monolito
Inertia con Laravel en el backend y Vue 3 en el cliente, sin API REST separada.

## Stack

- **Backend**: PHP 8.3+, Laravel 13, Inertia (adapter Laravel), Horizon, Socialite
  (SSO Azure AD), spatie/permission, spatie/activitylog, DomPDF, OpenSpout, Predis,
  Mailgun, Ziggy.
- **Frontend**: Vue 3 (`<script setup>` + Composition API), TypeScript, Vite 8,
  Tailwind 4 (config CSS-first), PrimeVue 4 (preset Aura), vue-i18n, lucide.
- **Infra**: Docker multi-stage → runtime `serversideup/php:8.4-fpm-nginx`,
  Horizon como servicio s6-overlay, Redis para colas, MySQL/MariaDB como base de
  datos (recurso aparte en Dokploy), detrás de Traefik en Dokploy + Docker Swarm.

## Puesta en marcha (local)

```bash
composer setup     # install + .env + key + migrate (MySQL) + npm build
composer dev       # server + queue + logs (pail) + vite, todo en paralelo
```

Build de producción con validación de tipos:

```bash
npm run build      # vue-tsc --noEmit && vite build
```

Formato y pruebas:

```bash
./vendor/bin/pint
composer test
```

## SSO

El acceso es solo con cuenta institucional Office 365 / Azure AD. **No hay 2FA**:
el control A.9.4.2 de ISO 27001 se cubre con el SSO. Configurar las variables
`AZURE_*` en el `.env`.

## Convenciones de UI (obligatorias)

- Toda tabla usa el toolbar uniforme **Columnas · XLSX · Mostrar · Filtros · Buscador**
  (ver `Components/DataTable.vue`, `SortableTh.vue`, composables `useSortable` y
  `useTokenSearch`).
- Confirmaciones siempre con `useConfirm()` + `<ConfirmDialog>`. Nunca `confirm()` nativo.
- Modales se cierran solo con botones (sin Escape, sin click en el backdrop).
- Paleta **teal / cyan / sky / amber**. Morado prohibido. Barra de progreso `#0d9488`.
- Dark mode por clase `.dark`.

## Estructura

```
app/Http/Middleware/   SecurityHeaders, EnsureUserIsNotBlocked, EnforceSystemLock, HandleInertiaRequests
app/Services/          Backup/ (Dropbox), Auth/, Erp/
app/Support/           TokenSearch (espejo backend del buscador por tokens)
resources/js/          app.ts, i18n.ts, Pages/, Layouts/, Components/, Composables/, Constants/, data/, locales/
config/                branding, security, anthropic, erp, services (Azure/Mailgun), horizon
docker/                s6-overlay → servicio Horizon
```

## Notas y pendientes (leer antes de arrancar)

Este esqueleto trae los archivos propios del stack (middleware, composables,
componentes, Docker, SSO, migraciones idempotentes). Antes de correrlo revisar:

1. **Configs stock de Laravel**: faltan `config/app.php`, `database.php`, `session.php`,
   `cache.php`, `queue.php`, `logging.php`, `mail.php`, etc. Son los de un
   `laravel/laravel` limpio. Bajar una base v13 y copiarlos, o generar el proyecto
   con `composer create-project` y montar encima estos archivos.
2. **Configs de paquetes**: `config/permission.php` y `config/activitylog.php` se
   publican con `php artisan vendor:publish` (spatie). Las migraciones aquí ya
   asumen esos valores.
3. **Versión de Laravel**: el template fija `^13.7`. Se respetó tal cual; verificar
   disponibilidad al hacer `composer install`.
4. **`laravel/pao`** aparecía en el template como dependencia de dev, pero no existe
   un paquete con ese nombre (posible typo). Se omitió. Si era `laravel/sail` u otro,
   agregarlo a mano.
5. **Roles**: el seeder trae 4 roles de ejemplo. En SED eran 6 — ajustar a la
   estructura funcional real de MINEC.
6. **`composer.lock` / `package-lock.json`**: no se incluyen. Se generan en el
   primer `install`; el Dockerfile los usa si están presentes.
