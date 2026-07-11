<?php

namespace App\Services\Backup;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Respaldo automático de la base de datos (A.8.13). Genera el dump y lo sube al
 * proveedor de almacenamiento en la nube configurado en Configuración →
 * Respaldos (Dropbox o Google Cloud Storage). Las credenciales viven en la
 * tabla `settings` (claves backup.*). Nunca interrumpe el flujo: los errores se
 * registran en el log y no propagan excepción.
 */
class CloudBackupService
{
    // Claves de configuración (tabla settings).
    public const ENABLED_KEY = 'backup.enabled';

    public const PROVIDER_KEY = 'backup.provider';

    public const FREQUENCY_KEY = 'backup.frequency';

    public const TIME_KEY = 'backup.time';

    public const RETENTION_KEY = 'backup.retention_days';

    // Dropbox
    public const DROPBOX_TOKEN_KEY = 'backup.dropbox_token';

    public const DROPBOX_FOLDER_KEY = 'backup.dropbox_folder';

    // Google Cloud Storage
    public const GCS_BUCKET_KEY = 'backup.gcs_bucket';

    public const GCS_PREFIX_KEY = 'backup.gcs_prefix';

    public const GCS_CREDENTIALS_KEY = 'backup.gcs_credentials';

    // Historial y estado de las ejecuciones.
    public const HISTORY_KEY = 'backup.history';

    /** Máximo de ejecuciones que se conservan en el historial. */
    private const HISTORY_LIMIT = 90;

    public function enabled(): bool
    {
        return (bool) Setting::value(self::ENABLED_KEY);
    }

    public function provider(): string
    {
        return (string) Setting::value(self::PROVIDER_KEY, 'dropbox');
    }

    /** Ejecuta el respaldo completo (dump + subida + purga). */
    public function run(): void
    {
        if (! $this->enabled()) {
            Log::info('backup: respaldos automáticos desactivados; se omite');

            return;
        }

        $connection = config('database.default');
        $dump = $this->makeDump($connection);

        if ($dump === null) {
            $this->fail(__('messages.backup.fail_dump'));

            return;
        }

        try {
            $ok = $this->provider() === 'google_cloud'
                ? $this->uploadGcs($dump, basename($dump))
                : $this->uploadDropbox($dump, basename($dump));

            $ok
                ? $this->succeed(basename($dump))
                : $this->fail(__('messages.backup.fail_upload', ['provider' => $this->provider()]));
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        } finally {
            @unlink($dump);
        }
    }

    // ── Registro de ejecuciones y notificación de fallos ─────────────────────

    /** Registra una ejecución correcta. */
    protected function succeed(string $file): void
    {
        $this->record('ok', $file);
        Log::info('backup: completado', ['provider' => $this->provider(), 'file' => $file]);
    }

    /** Registra un fallo en el log y notifica a los Super Admin. */
    protected function fail(string $detail): void
    {
        $this->record('error', $detail);
        Log::error('backup: fallo', ['provider' => $this->provider(), 'detail' => $detail]);
        $this->notifySuperAdmins($detail);
    }

    /** Añade una entrada al historial y actualiza el estado del último respaldo. */
    protected function record(string $status, string $detail = ''): void
    {
        $at = now()->toIso8601String();

        $history = json_decode((string) Setting::value(self::HISTORY_KEY), true);
        $history = is_array($history) ? $history : [];

        array_unshift($history, [
            'at' => $at,
            'status' => $status,
            'provider' => $this->provider(),
            'detail' => $detail,
        ]);

        Setting::put(self::HISTORY_KEY, json_encode(array_slice($history, 0, self::HISTORY_LIMIT)));
        Setting::put('backup.last_run_at', $at);
        Setting::put('backup.last_status', $status);
    }

    /** Envía un correo de aviso a los Super Admin activos ante un fallo de respaldo. */
    protected function notifySuperAdmins(string $detail): void
    {
        rescue(function () use ($detail) {
            $to = User::role('Super Admin')
                ->whereNull('blocked_at')
                ->pluck('email')
                ->filter()
                ->values()
                ->all();

            if (empty($to)) {
                return;
            }

            $body = "Falló un respaldo automático de la base de datos (A.8.13).\n\n"
                ."Proveedor: {$this->provider()}\n"
                ."Detalle: {$detail}\n"
                ."Fecha (UTC): ".now()->utc()->toDateTimeString()."\n\n"
                ."Revisa Configuración → Respaldos y la bitácora del sistema.";

            Mail::raw($body, fn ($m) => $m->to($to)->subject('META · Alerta: fallo en respaldo automático'));
        }, null, false);
    }

    /**
     * Prueba la conexión con el proveedor indicado usando las credenciales dadas
     * (o las guardadas). No sube nada: solo verifica el acceso.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{ok: bool, message: string}
     */
    public function test(string $provider, array $overrides = []): array
    {
        try {
            return $provider === 'google_cloud'
                ? $this->testGcs($overrides)
                : $this->testDropbox($overrides);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Dump ──────────────────────────────────────────────────────────────

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

        // MySQL / MariaDB via mysqldump (mariadb-client en la imagen runtime).
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
            Log::error('backup: mysqldump falló', ['err' => $process->getErrorOutput()]);

            return null;
        }

        file_put_contents($out, $process->getOutput());

        return $out;
    }

    // ── Subida por proveedor ────────────────────────────────────────────────

    protected function uploadDropbox(string $path, string $name): bool
    {
        $token = (string) Setting::value(self::DROPBOX_TOKEN_KEY);

        if ($token === '') {
            Log::warning('backup: token de Dropbox no configurado; dump conservado localmente');

            return false;
        }

        $folder = $this->normalizeFolder((string) Setting::value(self::DROPBOX_FOLDER_KEY, '/META/backups'));
        $remote = $folder.'/'.$name;

        $res = Http::withToken($token)
            ->withBody(file_get_contents($path), 'application/octet-stream')
            ->withHeaders([
                'Dropbox-API-Arg' => json_encode(['path' => $remote, 'mode' => 'overwrite', 'mute' => true]),
            ])
            ->timeout(600)
            ->post('https://content.dropboxapi.com/2/files/upload');

        if (! $res->successful()) {
            Log::error('backup: subida a Dropbox falló', ['status' => $res->status(), 'body' => $res->body()]);

            return false;
        }

        rescue(fn () => $this->pruneDropbox($token, $folder), null, false);

        return true;
    }

    protected function uploadGcs(string $path, string $name): bool
    {
        $bucket = (string) Setting::value(self::GCS_BUCKET_KEY);
        $credentials = (string) Setting::value(self::GCS_CREDENTIALS_KEY);

        if ($bucket === '' || $credentials === '') {
            Log::warning('backup: bucket o credenciales de Google Cloud no configurados');

            return false;
        }

        $token = $this->gcsAccessToken($credentials);
        $prefix = trim((string) Setting::value(self::GCS_PREFIX_KEY, 'meta/backups'), '/');
        $object = ($prefix !== '' ? $prefix.'/' : '').$name;

        $res = Http::withToken($token)
            ->withBody(file_get_contents($path), 'application/octet-stream')
            ->timeout(600)
            ->post('https://storage.googleapis.com/upload/storage/v1/b/'.rawurlencode($bucket).'/o?uploadType=media&name='.rawurlencode($object));

        if (! $res->successful()) {
            Log::error('backup: subida a Google Cloud falló', ['status' => $res->status(), 'body' => $res->body()]);

            return false;
        }

        return true;
    }

    // ── Pruebas de conexión ─────────────────────────────────────────────────

    /** @param array<string, mixed> $o @return array{ok: bool, message: string} */
    protected function testDropbox(array $o): array
    {
        $token = (string) ($o['dropbox_token'] ?? '') ?: (string) Setting::value(self::DROPBOX_TOKEN_KEY);

        if ($token === '') {
            return ['ok' => false, 'message' => __('messages.backup.test_no_token')];
        }

        $res = Http::withToken($token)
            ->withBody('null', 'application/json')
            ->timeout(20)
            ->post('https://api.dropboxapi.com/2/users/get_current_account');

        if (! $res->successful()) {
            return ['ok' => false, 'message' => __('messages.backup.test_dropbox_failed', ['detail' => $res->status()])];
        }

        $account = $res->json('email') ?? $res->json('name.display_name') ?? '';

        return ['ok' => true, 'message' => __('messages.backup.test_dropbox_ok', ['account' => $account])];
    }

    /** @param array<string, mixed> $o @return array{ok: bool, message: string} */
    protected function testGcs(array $o): array
    {
        $credentials = (string) ($o['gcs_credentials'] ?? '') ?: (string) Setting::value(self::GCS_CREDENTIALS_KEY);
        $bucket = (string) ($o['gcs_bucket'] ?? '') ?: (string) Setting::value(self::GCS_BUCKET_KEY);

        if ($credentials === '' || $bucket === '') {
            return ['ok' => false, 'message' => __('messages.backup.test_no_gcs')];
        }

        $token = $this->gcsAccessToken($credentials);

        // Comprueba acceso al bucket (metadata).
        $res = Http::withToken($token)
            ->timeout(20)
            ->get('https://storage.googleapis.com/storage/v1/b/'.rawurlencode($bucket));

        if (! $res->successful()) {
            return ['ok' => false, 'message' => __('messages.backup.test_gcs_failed', ['detail' => $res->status()])];
        }

        return ['ok' => true, 'message' => __('messages.backup.test_gcs_ok', ['bucket' => $bucket])];
    }

    // ── Google: OAuth2 con cuenta de servicio (JWT RS256) ────────────────────

    /** Obtiene un access token de GCS firmando un JWT con la cuenta de servicio. */
    protected function gcsAccessToken(string $credentialsJson): string
    {
        $creds = json_decode($credentialsJson, true);

        if (! is_array($creds) || empty($creds['client_email']) || empty($creds['private_key'])) {
            throw new \RuntimeException(__('messages.backup.test_bad_credentials'));
        }

        $tokenUri = $creds['token_uri'] ?? 'https://oauth2.googleapis.com/token';
        $now = time();

        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64Url(json_encode([
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/devstorage.read_write',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signature = '';
        openssl_sign($header.'.'.$claim, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $header.'.'.$claim.'.'.$this->base64Url($signature);

        $res = Http::asForm()->timeout(20)->post($tokenUri, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $token = $res->json('access_token');

        if (! $res->successful() || ! $token) {
            throw new \RuntimeException(__('messages.backup.test_token_failed', ['detail' => $res->status()]));
        }

        return (string) $token;
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // ── Retención ────────────────────────────────────────────────────────────

    /** Elimina de Dropbox los respaldos más antiguos que la retención configurada. */
    protected function pruneDropbox(string $token, string $folder): void
    {
        $days = (int) Setting::value(self::RETENTION_KEY, 30);
        if ($days <= 0) {
            return;
        }

        $threshold = now()->subDays($days);

        $list = Http::withToken($token)->timeout(30)
            ->post('https://api.dropboxapi.com/2/files/list_folder', ['path' => $folder]);

        if (! $list->successful()) {
            return;
        }

        foreach ((array) $list->json('entries', []) as $entry) {
            $modified = $entry['server_modified'] ?? null;
            if (($entry['.tag'] ?? '') !== 'file' || ! $modified) {
                continue;
            }

            if (\Illuminate\Support\Carbon::parse($modified)->lt($threshold)) {
                Http::withToken($token)->timeout(30)
                    ->post('https://api.dropboxapi.com/2/files/delete_v2', ['path' => $entry['path_lower'] ?? ($folder.'/'.$entry['name'])]);
            }
        }
    }

    private function normalizeFolder(string $folder): string
    {
        $folder = '/'.trim($folder, '/');

        return $folder === '/' ? '/META/backups' : $folder;
    }
}
