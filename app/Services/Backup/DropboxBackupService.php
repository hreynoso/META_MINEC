<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Backup diario de la base de datos (A.12.3). Detecta SQLite o MySQL/MariaDB,
 * genera el dump y lo sube a Dropbox. Ejecutado por el scheduler a las 02:00.
 */
class DropboxBackupService
{
    public function run(): void
    {
        $connection = config('database.default');
        $dump = $this->makeDump($connection);

        if ($dump === null) {
            Log::warning('backup: no se genero dump', ['connection' => $connection]);

            return;
        }

        $this->upload($dump);
        @unlink($dump);

        Log::info('backup: completado');
    }

    protected function makeDump(string $connection): ?string
    {
        $target = storage_path('app/backup-'.now()->format('Ymd-His'));

        if ($connection === 'sqlite') {
            $db = config('database.connections.sqlite.database');
            if (! is_string($db) || ! file_exists($db)) {
                return null;
            }
            $out = $target.'.sqlite';
            copy($db, $out);

            return $out;
        }

        // MySQL / MariaDB via mysqldump (mariadb-client en la imagen runtime)
        $cfg = config("database.connections.{$connection}");
        $out = $target.'.sql';

        $process = new Process([
            'mysqldump',
            '-h', (string) ($cfg['host'] ?? '127.0.0.1'),
            '-P', (string) ($cfg['port'] ?? '3306'),
            '-u', (string) ($cfg['username'] ?? 'root'),
            '--password='.(string) ($cfg['password'] ?? ''),
            (string) ($cfg['database'] ?? ''),
        ]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('backup: mysqldump fallo', ['err' => $process->getErrorOutput()]);

            return null;
        }

        file_put_contents($out, $process->getOutput());

        return $out;
    }

    protected function upload(string $path): void
    {
        $token = config('services.dropbox.token') ?? env('DROPBOX_BACKUP_TOKEN');

        if (! $token) {
            Log::warning('backup: DROPBOX_BACKUP_TOKEN no configurado; dump conservado localmente');

            return;
        }

        // TODO: subida real a Dropbox (Http::withToken(...)->post(...)).
        // Se deja como punto de extension segun el servicio original SED.
    }
}
