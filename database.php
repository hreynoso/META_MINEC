<?php

return [
    // Identidad institucional del sistema
    'app_name' => env('APP_NAME', 'Sistema META'),
    'institution' => 'Ministerio de Economía de El Salvador',
    'institution_short' => 'MINEC',
    'country' => 'El Salvador',

    // Colaboración
    'built_by' => 'Ministerio de la Presidencia — República Dominicana (MINPRE)',
    'collaboration' => 'Cooperación técnica República Dominicana → El Salvador',

    // Paleta (sin morado: teal / cyan / sky / amber)
    'colors' => [
        'primary' => '#0d9488',   // teal-600 (barra de progreso Inertia)
        'accent' => '#0891b2',    // cyan-600
        'info' => '#0284c7',      // sky-600
        'warning' => '#d97706',   // amber-600
    ],

    'support_email' => env('MAIL_FROM_ADDRESS', 'no-reply@minec.gob.sv'),
];
