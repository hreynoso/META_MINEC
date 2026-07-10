<?php

// Solo se sobreescribe la retención; el resto de valores vienen del paquete
// (Spatie) vía mergeConfigFrom. Retención configurable en config/security.php.
return [
    // A.8.10 — se purga con `activitylog:clean` (programado a diario).
    'delete_records_older_than_days' => (int) env('SECURITY_LOG_RETENTION_DAYS', 365),
];
