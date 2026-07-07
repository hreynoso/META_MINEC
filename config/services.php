<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    // SSO Google Workspace (driver nativo de laravel/socialite)
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        // Dominio de Google Workspace permitido (p. ej. minec.gob.sv). Restringe
        // el acceso a las cuentas de la organización. Vacío = cualquier cuenta Google.
        'hosted_domain' => env('GOOGLE_HOSTED_DOMAIN'),
    ],

];
