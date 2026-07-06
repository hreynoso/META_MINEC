<?php

return [
    // A.9.4.2 se cubre con SSO institucional; NO se implementa 2FA/TOTP.
    'sso_only' => true,

    // Historial de contrasenas (PasswordHistory)
    'password_history' => [
        'enabled' => true,
        'remember' => 5,
    ],

    // Cabeceras de seguridad (middleware SecurityHeaders)
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-XSS-Protection' => '1; mode=block',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    // Bloqueo global del sistema (EnforceSystemLock) via Setting
    'system_lock_setting_key' => 'system.locked',

    // Keep-alive de sesion
    'keep_alive_minutes' => 15,
];
