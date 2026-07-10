# Declaración de Aplicabilidad (SoA) — Sistema META

**Norma:** ISO/IEC 27001:2022, Anexo A · **Versión:** 1.0 · **Fecha:** 2026-07-09
**Sistema:** META (MINEC — Ministerio de Economía de El Salvador)

Este documento declara, para cada control del Anexo A, si **aplica** al Sistema
META y su **estado de implementación**. Complementa la
[Política de Seguridad](politica-seguridad.md).

## Leyenda de estado

| Estado | Significado |
|---|---|
| ✅ Implementado | Control operativo en el sistema (código/configuración). |
| 🟡 Parcial | Implementado en parte; queda trabajo o depende de operación. |
| ⛔ Pendiente | Aplica pero aún no implementado. |
| 🏢 Organizacional | Aplica, pero se cubre fuera del código (procesos, RRHH, proveedor de hosting/nube). |
| ➖ No aplica | No pertinente para este sistema. |

> "Aplica = Sí" salvo que se indique ➖. Las referencias apuntan a la
> implementación técnica cuando existe.

---

## A.5 Controles organizativos

| # | Control | Estado | Implementación / Nota |
|---|---|---|---|
| 5.1 | Políticas de seguridad de la información | ✅ | Política de Seguridad + esta SoA (`docs/ISO27001/`). |
| 5.2 | Roles y responsabilidades | ✅ | Roles RBAC (Spatie) + Política §4. |
| 5.3 | Segregación de funciones | 🟡 | Separación por roles/permisos; sin flujo de aprobación dual. |
| 5.4 | Responsabilidades de la dirección | 🏢 | Proceso institucional. |
| 5.5 | Contacto con autoridades | 🏢 | Proceso institucional. |
| 5.6 | Contacto con grupos de interés | 🏢 | Proceso institucional. |
| 5.7 | Inteligencia de amenazas | ⛔ | No implementado (fuera de alcance actual). |
| 5.8 | Seguridad en la gestión de proyectos | 🏢 | Proceso institucional. |
| 5.9 | Inventario de activos | ✅ | [inventario-activos.md](inventario-activos.md). |
| 5.10 | Uso aceptable de los activos | ✅ | Aviso de uso aceptable con aceptación obligatoria en el primer acceso (SSO y local). |
| 5.11 | Devolución de activos | 🟡 | Bloqueo/baja de cuentas (`blocked_at`, gestión de usuarios). |
| 5.12 | Clasificación de la información | 🏢 | Proceso institucional. |
| 5.13 | Etiquetado de la información | ➖ | No aplica al sistema. |
| 5.14 | Transferencia de información | 🟡 | TLS en tránsito; correo saliente configurable. |
| 5.15 | Control de acceso | ✅ | RBAC por rol y permiso (`routes/web.php`, Spatie). |
| 5.16 | Gestión de identidades | ✅ | SSO Google + gestión de usuarios (Configuración → Usuarios). |
| 5.17 | Información de autenticación | ✅ | SSO; política de contraseñas + historial para cuentas locales (`PasswordPolicy`, `NotInPasswordHistory`). |
| 5.18 | Derechos de acceso | ✅ | Alta/baja/asignación por admin + revisión periódica en Configuración → Seguridad → Revisión de accesos (con atestación y export). |
| 5.19–5.22 | Relación con proveedores | 🏢 | Google, proveedor de hosting (Dokploy), proveedores de IA. |
| 5.23 | Seguridad en servicios en la nube | 🟡 | Google Workspace + hosting; validación de dominio SSO. |
| 5.24 | Planificación de gestión de incidentes | 🟡 | Política §6. |
| 5.25 | Evaluación de eventos de seguridad | 🟡 | Bitácora + alertas de fuerza bruta. |
| 5.26 | Respuesta a incidentes | 🟡 | Bloqueo de cuentas, restauración desde respaldo. |
| 5.27 | Aprendizaje de incidentes | 🏢 | Proceso institucional. |
| 5.28 | Recolección de evidencia | 🟡 | Bitácora de actividad (Spatie Activitylog). |
| 5.29 | Seguridad durante la disrupción | 🟡 | Respaldos diarios. |
| 5.30 | Preparación TIC para continuidad | 🟡 | Respaldo diario a Dropbox (`DropboxBackupService`). |
| 5.31 | Requisitos legales y regulatorios | 🏢 | Proceso institucional. |
| 5.32 | Derechos de propiedad intelectual | 🏢 | Proceso institucional. |
| 5.33 | Protección de registros | 🟡 | Bitácora con retención configurable + respaldos. |
| 5.34 | Privacidad y protección de PII | ✅ | Datos personales mínimos (nombre, correo, foto opcional) + aviso de privacidad aceptado en el primer acceso. |
| 5.35 | Revisión independiente de seguridad | 🏢 | Proceso institucional. |
| 5.36 | Cumplimiento de políticas | 🏢 | Proceso institucional. |
| 5.37 | Procedimientos operativos documentados | ✅ | [procedimientos-operativos.md](procedimientos-operativos.md). |

## A.6 Controles de personas

| # | Control | Estado | Implementación / Nota |
|---|---|---|---|
| 6.1 | Investigación de antecedentes | 🏢 | RRHH institucional. |
| 6.2 | Términos y condiciones de empleo | 🏢 | RRHH institucional. |
| 6.3 | Concienciación y formación | 🏢 | Proceso institucional. |
| 6.4 | Proceso disciplinario | 🏢 | RRHH institucional. |
| 6.5 | Responsabilidades tras el cese | 🟡 | Bloqueo/eliminación de cuenta y expulsión de sesión. |
| 6.6 | Acuerdos de confidencialidad | 🏢 | RRHH institucional. |
| 6.7 | Trabajo remoto | 🟡 | Acceso web con TLS, SSO, un solo dispositivo, timeout. |
| 6.8 | Reporte de eventos de seguridad | 🟡 | Alertas automáticas + bitácora. |

## A.7 Controles físicos

| # | Control | Estado | Nota |
|---|---|---|---|
| 7.1–7.14 | Perímetro, acceso físico, equipos, cableado, mantenimiento, etc. | 🏢 | Responsabilidad del proveedor de hosting/centro de datos. No gestionados por la aplicación. |

## A.8 Controles tecnológicos

| # | Control | Estado | Implementación / Nota |
|---|---|---|---|
| 8.1 | Dispositivos de punto final | 🟡 | Un solo dispositivo por usuario (`EnforceSingleDevice`) + timeout por inactividad. |
| 8.2 | Derechos de acceso privilegiado | ✅ | Super Admin break-glass, `Gate::before`, RBAC del área admin. |
| 8.3 | Restricción de acceso a la información | ✅ | `permission:` en rutas de módulos operativos; `useCan()` en UI. |
| 8.4 | Acceso al código fuente | 🏢 | Repositorio privado en GitHub. |
| 8.5 | Autenticación segura | ✅ | SSO Google + validación de dominio; anti–fuerza bruta (RateLimiter + Lockout). |
| 8.6 | Gestión de capacidad | 🏢 | Infraestructura/hosting. |
| 8.7 | Protección contra malware | 🏢 | Infraestructura. |
| 8.8 | Gestión de vulnerabilidades técnicas | ✅ | Auditoría en CI (`composer audit` + `npm audit`) en push/PR y semanal; vista en Configuración → Seguridad → Dependencias. |
| 8.9 | Gestión de la configuración | 🟡 | Config por variables de entorno; Docker/Dokploy como IaC. |
| 8.10 | Eliminación de información | ✅ | Purga de bitácora por retención (`activitylog:clean`); borrado de archivos (foto/branding). |
| 8.11 | Enmascaramiento de datos | 🟡 | Claves/secretos nunca se envían al cliente (solo "existe/no existe"). |
| 8.12 | Prevención de fuga de datos | 🟡 | RBAC + CSP + control de descargas por permiso. |
| 8.13 | Respaldo de la información | ✅ | Respaldo diario automático a Dropbox (cron 02:00). |
| 8.14 | Redundancia | 🏢 | Infraestructura/hosting. |
| 8.15 | Registro (logging) | ✅ | Bitácora de accesos y cambios (Activitylog + `LogAuthenticationEvents`). |
| 8.16 | Actividades de monitoreo | 🟡 | Alertas por correo ante bloqueo de fuerza bruta (`SecurityAlert`); sin SIEM. |
| 8.17 | Sincronización de reloj | 🟡 | Almacenamiento en UTC; visualización en la zona del usuario. |
| 8.18 | Uso de utilidades privilegiadas | 🏢 | Infraestructura. |
| 8.19 | Instalación de software en producción | 🏢 | Despliegue controlado vía Dokploy/git. |
| 8.20 | Seguridad de redes | 🟡 | Traefik/Cloudflare + TLS; proxy confiable configurado. |
| 8.21 | Seguridad de servicios de red | 🟡 | HTTPS forzado; cabeceras de seguridad. |
| 8.22 | Segregación de redes | 🏢 | Infraestructura. |
| 8.23 | Filtrado web | ➖ | No aplica al sistema. |
| 8.24 | Uso de criptografía | ✅ | TLS/HSTS, CSP con *nonce*, cookies cifradas, hashing de contraseñas. |
| 8.25 | Ciclo de vida de desarrollo seguro | 🟡 | Framework moderno, control de versiones, revisión de cambios. |
| 8.26 | Requisitos de seguridad de aplicaciones | 🟡 | Validación, CSRF, CSP, RBAC. |
| 8.27 | Arquitectura de sistemas segura | 🟡 | Separación de capas; middleware de seguridad. |
| 8.28 | Codificación segura | 🟡 | Validación, escape de salida, ORM (evita inyección). |
| 8.29 | Pruebas de seguridad | 🟡 | Autoevaluación de postura + auditoría de dependencias en CI; sin pruebas de penetración formales. |
| 8.30 | Desarrollo subcontratado | 🏢 | Gobernanza institucional. |
| 8.31 | Separación de entornos | 🟡 | Configuración por entorno; separación dev/prod. |
| 8.32 | Gestión de cambios | 🟡 | Git + despliegue controlado. |
| 8.33 | Información de prueba | 🏢 | Proceso de desarrollo. |
| 8.34 | Protección durante auditorías | 🏢 | Proceso institucional. |

---

## Resumen de brechas (aplican y no están completas)

Las brechas priorizadas de la versión anterior (A.5.18, A.5.10/A.5.34, A.8.8,
A.5.9/A.5.37) se **cerraron** en esta iteración. Quedan puntos menores/residuales:

- **A.8.29** — pruebas de penetración formales (hay autoevaluación + auditoría de
  dependencias, pero no *pentest*).
- **A.5.3 / A.8.25–8.28** — mejoras continuas de segregación de funciones y de ciclo
  de desarrollo seguro.
- Controles 🏢 **organizacionales/infraestructura** (RRHH, físicos, proveedores) que
  se gestionan fuera de la aplicación.

El resto de controles aplicables está ✅ implementado o 🏢 cubierto por la
organización/infraestructura. Este documento se revisa junto con la Política de
Seguridad al menos una vez al año o ante cambios significativos.
