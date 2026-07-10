# Política de Seguridad de la Información — Sistema META

**Organización:** Ministerio de Economía de El Salvador (MINEC)
**Sistema:** META — Monitoreo Estratégico de Acciones
**Marco de referencia:** ISO/IEC 27001:2022
**Versión:** 1.0 · **Última actualización:** 2026-07-09
**Responsable del documento:** Administración del Sistema META

> Documento interno. Debe revisarse al menos una vez al año o ante cambios
> significativos en el sistema, la normativa o el análisis de riesgos.

---

## 1. Propósito

Establecer los principios y controles que protegen la **confidencialidad,
integridad y disponibilidad** de la información gestionada por el Sistema META,
que da seguimiento a proyectos de inversión pública, indicadores (KPIs), metas
presidenciales e informes ejecutivos.

## 2. Alcance

Aplica a la aplicación web META (Laravel + Inertia + Vue), su base de datos, sus
respaldos, sus integraciones (SSO Google Workspace, proveedores de IA, correo) y
a todas las personas usuarias y administradoras del sistema.

## 3. Principios

1. **Mínimo privilegio (least privilege).** Cada persona recibe solo los permisos
   necesarios para su función. El acceso nuevo es de solo lectura por defecto.
2. **Autenticación institucional.** El acceso ordinario es exclusivamente por SSO
   de Google Workspace, restringido al dominio de la organización. El acceso local
   se limita a una cuenta administrativa de emergencia (*break-glass*).
3. **Trazabilidad.** Los accesos y las acciones sobre datos quedan registrados en
   una bitácora consultable.
4. **Defensa en profundidad.** Controles en red (proxy/TLS), aplicación
   (cabeceras, CSP, RBAC, sesión) y datos (respaldos, retención).
5. **Privacidad por diseño.** No se registran credenciales; los datos personales
   se limitan a lo necesario (nombre, correo institucional, foto opcional).

## 4. Responsabilidades

- **Super Admin (break-glass):** cuenta local de máximo privilegio para
  provisión y recuperación. Uso excepcional y auditable.
- **Administrador:** gestiona usuarios, roles, configuración, correo y bitácora.
- **Roles operativos** (Directivo, Gestor de Proyectos, Analista, Consultor):
  acceso acotado por permisos a los módulos correspondientes.
- **Personas usuarias:** uso responsable, protección de su sesión y equipo,
  notificación de incidentes.

## 5. Controles clave (resumen)

- Cifrado en tránsito (HTTPS/HSTS) y cabeceras de seguridad + CSP con *nonce*.
- SSO Google con validación de dominio; anti–fuerza bruta con bloqueo temporal.
- RBAC por rol y permiso en el área administrativa y en los módulos operativos.
- Una sola sesión activa por usuario; cierre de sesión por inactividad.
- Política de contraseñas e historial para cuentas locales.
- Bitácora de accesos y de cambios; alertas ante eventos sospechosos.
- Respaldos automáticos diarios; retención y purga de bitácora.

El detalle control por control está en la **Declaración de Aplicabilidad (SoA)**:
[`declaracion-aplicabilidad.md`](declaracion-aplicabilidad.md).

## 6. Gestión de incidentes

Ante un incidente (acceso no autorizado, fuga, indisponibilidad): contener,
registrar en la bitácora, notificar a la Administración del Sistema y, si
procede, restaurar desde respaldo. Las alertas automáticas por fuerza bruta se
envían a los administradores.

## 7. Cumplimiento y revisión

El cumplimiento de esta política es obligatorio. La Administración del Sistema
revisa accesos y controles periódicamente y actualiza este documento y la SoA
conforme evoluciona el sistema y el análisis de riesgos.
