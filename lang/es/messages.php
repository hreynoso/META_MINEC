<?php

return [
    'auth' => [
        'google_failed' => 'No fue posible autenticar con Google Workspace.',
        'google_domain_only' => 'Solo se permite el acceso con cuentas de :domain.',
        'account_blocked' => 'Su cuenta ha sido bloqueada. Contacte al administrador.',
        'session_closed_other_device' => 'Su sesión se cerró porque se inició sesión en otro dispositivo.',
        'session_kept_other_device' => 'No se inició sesión: continúa activa en el otro dispositivo.',
        'invalid_credentials' => 'Las credenciales no son válidas.',
        'invalid_local_credentials' => 'Credenciales no válidas para el acceso local.',
        'too_many_attempts' => 'Demasiados intentos fallidos. Intente de nuevo en :seconds segundos.',
        'idle_timeout' => 'Su sesión se cerró por inactividad.',
    ],

    'ai' => [
        'updated' => 'Configuración de IA actualizada.',
        'not_configured' => 'El API de IA no está configurado. Ve a Configuración → Inteligencia Artificial.',
    ],

    'branding' => [
        'colors_updated' => 'Colores del sistema actualizados.',
        'updated' => 'Identidad visual actualizada.',
    ],

    'sso' => [
        'updated' => 'Configuración de SSO de Google actualizada.',
    ],

    'kpi' => [
        'created' => 'Indicador creado correctamente.',
        'updated' => 'Indicador actualizado correctamente.',
        'deleted' => 'Indicador eliminado correctamente.',
    ],

    'mail' => [
        'updated' => 'Configuración de correo actualizada.',
        'test_failed' => 'No se pudo enviar el correo de prueba: :error',
        'test_sent' => 'Correo de prueba enviado a :email.',
    ],

    'notifications' => [
        'updated' => 'Preferencias de notificación actualizadas.',
        'report_queued' => 'Informe en cola; se enviará por correo en unos momentos.',
    ],

    'role' => [
        'created' => 'Rol creado correctamente.',
        'updated' => 'Rol actualizado correctamente.',
        'deleted' => 'Rol eliminado correctamente.',
        'cannot_delete' => 'El rol :name no se puede eliminar.',
    ],

    'user' => [
        'only_admin_can_grant_admin' => 'Solo un usuario con el rol Administrador puede asignar el rol Administrador.',
        'created' => 'Usuario creado correctamente.',
        'updated' => 'Usuario actualizado correctamente.',
        'cannot_delete_self' => 'No puedes eliminar tu propia cuenta.',
        'deleted' => 'Usuario eliminado correctamente.',
        'never_logged_in' => 'Nunca',
        'password_reused' => 'No puede reutilizar una de sus últimas contraseñas.',
        'cannot_block_self' => 'No puedes bloquear tu propia cuenta.',
        'blocked' => 'Cuenta bloqueada correctamente.',
        'unblocked' => 'Cuenta desbloqueada correctamente.',
    ],

    'minister' => [
        'report_failed' => 'No se pudo generar el informe: :error',
        'report_generated' => 'Informe presidencial generado.',
    ],

    'memoir' => [
        'generate_failed' => 'No se pudo generar la memoria: :error',
    ],

    'predictive' => [
        'ai_not_configured_fallback' => 'IA no configurada; se muestra la recomendación del modelo. Configúrala en Configuración → Inteligencia Artificial.',
        'ai_query_failed' => 'No se pudo consultar la IA: :error',
    ],

    'profile' => [
        'photo_updated' => 'Foto de perfil actualizada.',
        'photo_removed' => 'Foto de perfil eliminada.',
    ],

    'security' => [
        'review_recorded' => 'Revisión de accesos registrada.',
        'alerts_saved' => 'Destinatarios de las alertas de seguridad actualizados.',
        'invalid_email' => 'El correo ":email" no es válido.',
        'deps_run' => 'Análisis de dependencias ejecutado.',
        'deps_schedule_saved' => 'Periodicidad del análisis de dependencias actualizada.',
        'deps_report_sent' => 'Informe de dependencias generado y enviado al equipo de seguridad.',
        'deps_report_no_recipients' => 'No hay destinatarios configurados para el informe de seguridad.',
    ],

    'backup' => [
        'saved' => 'Configuración de respaldos actualizada.',
        'fail_dump' => 'No se pudo generar el volcado de la base de datos.',
        'fail_upload' => 'No se pudo subir el respaldo al proveedor :provider.',
        'run_ok' => 'Respaldo generado y subido correctamente.',
        'run_failed' => 'No se pudo completar el respaldo. Revisa la configuración y la bitácora.',
        'run_queued' => 'Respaldo iniciado en segundo plano. El resultado aparecerá en el historial en unos momentos.',
        'oauth_ok' => 'Dropbox conectado: refresh token guardado. Los respaldos ya no caducarán.',
        'oauth_failed' => 'No se pudo obtener el refresh token de Dropbox: :detail',
        'invalid_credentials' => 'El JSON de credenciales no es válido o le faltan campos (client_email / private_key).',
        'test_no_token' => 'Falta el token de acceso de Dropbox.',
        'test_no_gcs' => 'Faltan el bucket o las credenciales de Google Cloud.',
        'test_bad_credentials' => 'Las credenciales de la cuenta de servicio no son válidas.',
        'test_dropbox_ok' => 'Conexión con Dropbox correcta (:account).',
        'test_dropbox_failed' => 'No se pudo conectar con Dropbox (código :detail).',
        'test_gcs_ok' => 'Conexión con Google Cloud correcta (bucket :bucket).',
        'test_gcs_failed' => 'No se pudo acceder al bucket de Google Cloud (código :detail).',
        'test_token_failed' => 'No se pudo obtener el token de Google Cloud (código :detail).',
    ],

    'catalog' => [
        'created' => 'Valor agregado al catálogo.',
        'updated' => 'Catálogo actualizado.',
        'deleted' => 'Valor eliminado del catálogo.',
        'duplicate' => 'Ese valor ya existe en el catálogo.',
        'in_use' => 'No se puede eliminar: el valor está en uso por una o más instituciones.',
        'code_saved' => 'Nomenclatura del código de institución actualizada.',
    ],

    'institution' => [
        'created' => 'Institución creada correctamente.',
        'updated' => 'Institución actualizada correctamente.',
        'deleted' => 'Institución eliminada correctamente.',
        'has_projects' => 'No se puede eliminar: la institución tiene proyectos vinculados. Cámbiala a Inactiva.',
    ],

    'project' => [
        'created' => 'Proyecto creado correctamente.',
        'updated' => 'Proyecto actualizado correctamente.',
        'deleted' => 'Proyecto eliminado correctamente.',
    ],
];
