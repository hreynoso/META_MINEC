# Inventario de Activos — Sistema META

**Control:** ISO/IEC 27001:2022 A.5.9 · **Versión:** 1.0 · **Fecha:** 2026-07-09
**Sistema:** META (MINEC — Ministerio de Economía de El Salvador)

Inventario de los activos de información y activos asociados del Sistema META,
con su tipo, ubicación, responsable y clasificación. Se revisa junto con la
[Política de Seguridad](politica-seguridad.md) al menos una vez al año.

**Clasificación:** Público · Interno · **Confidencial**.

## 1. Aplicación y código

| Activo | Tipo | Ubicación | Responsable | Clasificación |
|---|---|---|---|---|
| Código fuente META | Software | Repositorio privado GitHub (`hreynoso/META_MINEC`) | Administración del Sistema | Interno |
| Imagen de contenedor | Software | Build en Dokploy (Dockerfile multi-stage) | Administración del Sistema | Interno |
| Configuración (variables de entorno) | Configuración | Panel Dokploy (Environment) | Administración del Sistema | **Confidencial** |

## 2. Datos

| Activo | Tipo | Ubicación | Responsable | Clasificación |
|---|---|---|---|---|
| Base de datos `minec_db` | Datos | MySQL (contenedor Dokploy) | Administración del Sistema | **Confidencial** |
| Proyectos, KPIs, metas, informes | Datos institucionales | BD | Directivos / Gestores | Interno |
| Usuarios y roles | Datos personales | BD (`users`, tablas de permisos) | Administración del Sistema | **Confidencial** |
| Bitácora de actividad | Registros | BD (`activity_log`), retención 365 días | Administración del Sistema | **Confidencial** |
| Archivos subidos (branding, avatares) | Archivos | Volumen persistente `storage/app/public` | Administración del Sistema | Interno |
| Respaldos de BD | Datos | Dropbox (respaldo diario) | Administración del Sistema | **Confidencial** |

## 3. Infraestructura y servicios

| Activo | Tipo | Ubicación | Responsable | Clasificación |
|---|---|---|---|---|
| Orquestador de despliegue | Servicio | Dokploy (`panel.hrm.com.do`) | Administración del Sistema | Interno |
| Servicio web (app) | Contenedor | serversideup php-fpm-nginx | Administración del Sistema | Interno |
| Base de datos | Contenedor | MySQL | Administración del Sistema | Interno |
| Colas / caché | Contenedor | Redis | Administración del Sistema | Interno |
| Proxy / TLS | Servicio | Traefik + Cloudflare | Proveedor / Administración | Interno |
| Dominio | Activo | `minec.hrm.com.do` | Administración del Sistema | Público |

## 4. Integraciones externas

| Activo | Tipo | Uso | Responsable | Clasificación |
|---|---|---|---|---|
| Google Workspace (SSO) | Identidad | Autenticación de usuarios | Administración del Sistema | **Confidencial** |
| Proveedor de IA (Anthropic/Gemini/OpenAI) | API | Informes y recomendaciones | Administración del Sistema | **Confidencial** |
| Correo (Mailgun / SMTP) | Servicio | Notificaciones y alertas | Administración del Sistema | Interno |
| Dropbox | Almacenamiento | Respaldos automáticos | Administración del Sistema | **Confidencial** |

## 5. Credenciales y secretos (Confidencial)

Todos se almacenan como variables de entorno en Dokploy (o cifrados en `settings`),
**nunca en el código** ni expuestos al cliente:

| Secreto | Propósito |
|---|---|
| `APP_KEY` | Cifrado de la aplicación (cookies, sesión). |
| Credenciales MySQL | Acceso a la base de datos. |
| `SUPERADMIN_EMAIL` / `SUPERADMIN_PASSWORD` | Cuenta break-glass (Super Admin). |
| Google OAuth (client id/secret, dominio) | SSO institucional. |
| Clave del API de IA | Proveedor de inteligencia artificial. |
| Secretos de correo (Mailgun/SMTP) | Envío de correo. |
| `DROPBOX_BACKUP_TOKEN` | Respaldos. |

> La rotación de estos secretos sigue el procedimiento correspondiente en
> [procedimientos-operativos.md](procedimientos-operativos.md).

## 6. Cuentas y accesos

| Activo | Descripción |
|---|---|
| Cuenta **Super Admin** (break-glass) | Acceso local de máximo privilegio; uso excepcional y auditable. |
| Roles operativos | Administrador, Directivo, Gestor de Proyectos, Analista, Consultor (por defecto, solo lectura). |
| Usuarios SSO | Personal del dominio institucional autenticado por Google Workspace. |
