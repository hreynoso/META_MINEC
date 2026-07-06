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
  Horizon como servicio s6-overlay, Redis para colas, SQLite en volumen, detrás de
  Traefik en Dokploy + Docker Swarm.

## Puesta en marcha (local)

```bash
composer setup     # install + .env + key + sqlite + migrate + npm build
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

## Fase 1 (portada del prototipo Sistema META)

Incluye el núcleo funcional traducido desde el prototipo de Lovable:

- **Dominio**: modelos, migraciones idempotentes y seeders de `institutions` (8),
  `presidential_goals` (8), `kpis` (8) y `projects` (15) con los datos observados
  en el prototipo (ver nota de fidelidad en `ProjectSeeder`).
- **AppLayout** con el sidebar de META (9 módulos; los de fases posteriores quedan
  visibles pero deshabilitados).
- **Dashboard**: tarjetas resumen, indicadores estratégicos, metas presidenciales
  y cartera de proyectos.
- **Proyectos**: grid de tarjetas con buscador por tokens, filtros por institución
  y estado, y modal de detalle (se cierra solo con botones).
- **Login** adaptado a SSO Azure AD (sin correo/contraseña del prototipo).
- Paleta reskineada a teal/cyan/sky/amber.

Para verlo en local: `composer setup` y luego `php artisan migrate:fresh --seed`,
después `composer dev`. Pendientes: Ministra, KPIs, Reportes, IA Predictiva,
Memorias, Red de Gestores y Configuración (fases 2–4).

## Corrección de despliegue (build en Dokploy)

El primer intento de deploy falló porque el repo no era una app Laravel completa
(faltaban `artisan`, `public/index.php` y las configs stock). Se completó el
esqueleto con los archivos oficiales de `laravel/laravel` v13.8 y se validó el
`vue-tsc` (que también rompía el build por el tipado de `route()` en templates).

**Ajustes de infraestructura:**

- La BD SQLite se movió a `database/sqlite/database.sqlite` para que el volumen no
  oculte `database/migrations`. El archivo se crea en la imagen para que el volumen
  herede permisos `www-data`.
- El `Dockerfile` instala paquetes del SO de forma tolerante (apt en Debian/Ubuntu,
  apk en Alpine) y agrega `pdo_sqlite`.

**Variables que DEBES definir en Dokploy** (Environment):

```
APP_KEY=base64:...      # genera una con: php artisan key:generate --show
APP_URL=https://tu-dominio
AZURE_CLIENT_ID=...
AZURE_CLIENT_SECRET=...
AZURE_TENANT_ID=...
```

> Sin `APP_KEY` el contenedor arranca pero migraciones y sesiones fallan.
> El login es solo SSO Azure AD: para entrar necesitas las credenciales `AZURE_*`.
> Si quieres ver el sistema antes de tener el SSO institucional, puedo agregar un
> acceso temporal de demo (usuario sembrado) — pídelo y lo incluyo.

## Acceso temporal de demo

Para mostrar el sistema antes de tener el SSO Azure AD, existe un acceso de demo
con correo/contraseña, **desactivado por defecto** y protegido por un flag.

Actívalo en Dokploy (Environment) y vuelve a desplegar:

```
DEMO_LOGIN_ENABLED=true
```

Credenciales sembradas (seeder `DemoUserSeeder`, rol Administrador):

```
Correo:      demo@minec.gob.sv
Contraseña:  MetaDemo2026*
```

> Requiere haber corrido los seeders (`php artisan migrate:fresh --seed` o
> `db:seed`). En cuanto el SSO institucional esté operativo, pon
> `DEMO_LOGIN_ENABLED=false` (o quítalo) para deshabilitar el formulario, y
> rota/elimina el usuario demo. El botón de Office 365 sigue disponible siempre.

## Base de datos MySQL y Redis en Dokploy

En Dokploy la app se despliega como **Application** (build del `Dockerfile`), por lo
que MySQL y Redis se crean como **servicios aparte** dentro del mismo proyecto y se
consumen por variables de entorno.

### MySQL
1. En el proyecto de Dokploy: **Create Service → Database → MySQL** (o MariaDB).
2. Toma el nombre interno del servicio (host) y las credenciales que genera.
3. En la app (Environment) define:
   ```
   DB_CONNECTION=mysql
   DB_HOST=<host-interno-del-servicio-mysql>
   DB_PORT=3306
   DB_DATABASE=meta_minec
   DB_USERNAME=<usuario>
   DB_PASSWORD=<clave>
   ```
La imagen ya incluye `pdo_mysql` y `mariadb-client` (para los backups).

### Redis
La app usa **Horizon** para supervisar colas sobre **Redis**, así que sí necesitas
un Redis mientras mantengas Horizon. Crea el servicio (**Create Service → Database →
Redis**) y en la app define:
   ```
   QUEUE_CONNECTION=redis
   REDIS_CLIENT=predis
   REDIS_HOST=<host-interno-del-servicio-redis>
   REDIS_PORT=6379
   ```

> Alternativa sin Redis: si prefieres no levantar Redis por ahora (la Fase 1 aún no
> despacha jobs), se puede usar `QUEUE_CONNECTION=database` (tabla `jobs` en MySQL) y
> desactivar el servicio s6 de Horizon. Menos infraestructura, pero pierdes el
> dashboard de Horizon. Es un cambio pequeño; pídelo y lo dejo listo.

## Siembra automática en el arranque (opcional)

La imagen incluye un script (`docker/entrypoint.d/99-seed.sh`) que corre `db:seed`
al arrancar el contenedor, **solo si** defines:

```
AUTORUN_LARAVEL_SEED=true
```

Se ejecuta después de la migración de AUTORUN. Úsalo en el **primer despliegue**
para poblar instituciones, metas, KPIs, los 15 proyectos y el usuario demo, y
después ponlo en `false`: como el `ProjectSeeder` usa `updateOrCreate`, dejarlo
activo sobrescribiría en cada reinicio los datos que se editen desde la interfaz.
