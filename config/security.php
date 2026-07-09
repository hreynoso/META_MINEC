<?php

return [
    // Autenticación exclusiva por SSO Google Workspace. A.9.4.2 se cubre con el
    // SSO institucional; NO se implementa 2FA/TOTP ni login local. Con esto
    // activo, el único método de acceso es "Iniciar sesión con Google".
    'sso_only' => (bool) env('SECURITY_SSO_ONLY', true),

    // Acceso temporal de demo (correo+contraseña). Queda deshabilitado siempre
    // que sso_only esté activo (SSO solo Google).
    'demo_login' => ! (bool) env('SECURITY_SSO_ONLY', true) && (bool) env('DEMO_LOGIN_ENABLED', false),

    // Acceso local de la cuenta Super Admin (break-glass). Es independiente del
    // SSO: solo funciona para el usuario con rol "Super Admin". El resto de
    // usuarios entra exclusivamente por Google.
    'local_admin_login' => (bool) env('SECURITY_LOCAL_ADMIN_LOGIN', true),

    // Correos adicionales autorizados para el acceso local (correo+contraseña),
    // además del Super Admin. Útil para cuentas de demostración puntuales.
    // Separados por coma. Vacío = solo Super Admin. Quitar tras la demo.
    'local_login_emails' => array_values(array_filter(array_map(
        fn ($e) => strtolower(trim((string) $e)),
        explode(',', (string) env('SECURITY_LOCAL_LOGIN_EMAILS', '')),
    ))),

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

    // HSTS (A.8.24) — solo se envía sobre HTTPS (Traefik termina el TLS).
    'hsts' => [
        'enabled' => (bool) env('HSTS_ENABLED', true),
        'max_age' => (int) env('HSTS_MAX_AGE', 31536000),
        'include_subdomains' => (bool) env('HSTS_INCLUDE_SUBDOMAINS', true),
        'preload' => (bool) env('HSTS_PRELOAD', false),
    ],

    // Content-Security-Policy (A.8.24). %s = nonce por petición (scripts).
    // style-src usa 'unsafe-inline' por los estilos en línea de Vue/PrimeVue.
    // Si tras un deploy la app se ve en blanco, poner CSP_REPORT_ONLY=true para
    // diagnosticar sin bloquear, o CSP_ENABLED=false como último recurso.
    'csp' => [
        'enabled' => (bool) env('CSP_ENABLED', true),
        'report_only' => (bool) env('CSP_REPORT_ONLY', false),
        'policy' => "default-src 'self'; script-src 'self' 'nonce-%s'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
    ],

    // Bloqueo global del sistema (EnforceSystemLock) via Setting
    'system_lock_setting_key' => 'system.locked',

    // Keep-alive de sesion
    'keep_alive_minutes' => 15,
];
