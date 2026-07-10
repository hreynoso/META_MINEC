# Procedimientos Operativos — Sistema META

**Control:** ISO/IEC 27001:2022 A.5.37 · **Versión:** 1.0 · **Fecha:** 2026-07-09
**Sistema:** META (MINEC — Ministerio de Economía de El Salvador)

Procedimientos documentados para la operación segura del sistema. Complementan la
[Política de Seguridad](politica-seguridad.md), el
[Inventario de Activos](inventario-activos.md) y la
[Declaración de Aplicabilidad](declaracion-aplicabilidad.md).

---

## 1. Despliegue de cambios

1. Editar el código localmente y validar (`vue-tsc --noEmit`).
2. `git commit` y `git push` a la rama `main` en GitHub.
3. En Dokploy → servicio **META_MINEC_Web** → **Deploy**.
4. El build ejecuta `composer install`, compila los assets (Vite) y corre las
   migraciones automáticamente (`AUTORUN_LARAVEL_MIGRATION=true`).
5. Verificar en **Logs** de Dokploy que el contenedor levanta sin errores.

> Escape hatch: si tras un deploy la app se ve en blanco, poner
> `CSP_REPORT_ONLY=true` (o `CSP_ENABLED=false`) en el entorno y redesplegar.

## 2. Respaldo y restauración

- **Respaldo:** automático a diario a las 02:00 (a Dropbox) mediante el
  planificador. Requiere `DROPBOX_BACKUP_TOKEN` configurado.
- **Restauración:** descargar el respaldo más reciente desde Dropbox e importarlo
  al servicio MySQL. Verificar integridad tras restaurar.
- Validar periódicamente que los respaldos se generan (revisar la carpeta destino).

## 3. Alta de usuarios

- **SSO (ordinario):** el usuario ingresa con Google Workspace del dominio
  institucional; se crea automáticamente con el rol por defecto **Consultor**
  (solo lectura).
- **Elevación de privilegios:** un Administrador ajusta el rol en
  **Configuración → Usuarios**, según la función de la persona (mínimo privilegio).

## 4. Baja de usuarios / cambios de personal

- Marcar la cuenta como **bloqueada** o **eliminarla** en Configuración → Usuarios.
  El bloqueo impide el acceso y expulsa la sesión activa.
- Revisar en la **Revisión de accesos** que no queden cuentas activas de personal
  que ya no debe tener acceso.

## 5. Acceso de emergencia (break-glass — Super Admin)

- Uso **excepcional** cuando el SSO no está disponible o para provisión inicial.
- Se provisiona con `SUPERADMIN_EMAIL` / `SUPERADMIN_PASSWORD` en Dokploy
  (`php artisan superadmin:sync` corre en cada deploy).
- El acceso local queda registrado en la bitácora. Cambiar la contraseña tras un
  uso excepcional.

## 6. Revisión periódica de accesos (mensual)

1. Entrar a **Configuración → Seguridad → Revisión de accesos**.
2. Revisar roles, últimos accesos y cuentas marcadas como *privilegiado*,
   *bloqueado* o *inactivo 90+ días*.
3. Ajustar o dar de baja lo que corresponda.
4. **Exportar (XLSX)** para el expediente y pulsar **Registrar revisión**
   (deja constancia de quién revisó y cuándo).

## 7. Auditoría de dependencias

- Automática en el pipeline de **CI** (GitHub Actions: `composer audit` + `npm audit`)
  en cada push/PR a `main` y semanalmente.
- En la app: **Configuración → Seguridad → Dependencias** muestra el último análisis
  y permite ejecutarlo manualmente. También corre semanalmente (`security:audit`).
- Ante una vulnerabilidad: actualizar la dependencia afectada y redesplegar.

## 8. Revisión de la bitácora

- **Logs del Sistema** muestra accesos y cambios. La bitácora se conserva
  `SECURITY_LOG_RETENTION_DAYS` días (365 por defecto) y se purga a diario.
- Revisar ante alertas o incidentes; exportar evidencia si es necesario.

## 9. Rotación de secretos y claves

1. Generar el nuevo valor en el proveedor correspondiente (Google, IA, correo,
   Dropbox, BD).
2. Actualizar la variable de entorno en Dokploy.
3. **Deploy** para aplicar (el `.env` se hornea en el build).
4. Verificar el servicio afectado (login, correo, respaldo, etc.).

## 10. Respuesta a incidentes

1. **Contener:** bloquear cuentas comprometidas; si aplica, activar mantenimiento.
2. **Registrar:** revisar y conservar la bitácora relacionada.
3. **Notificar:** a la Administración del Sistema (las alertas de fuerza bruta
   llegan por correo automáticamente).
4. **Recuperar:** restaurar desde respaldo si hubo pérdida o alteración de datos.
5. **Aprender:** documentar la causa y las acciones correctivas.

## 11. Verificación de la postura de seguridad

- Revisar **Configuración → Seguridad → Pruebas automatizadas** tras cada cambio
  relevante; atender advertencias (🟡) y críticos (🔴).
