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
    ],

    'project' => [
        'created' => 'Proyecto creado correctamente.',
        'updated' => 'Proyecto actualizado correctamente.',
        'deleted' => 'Proyecto eliminado correctamente.',
    ],
];
