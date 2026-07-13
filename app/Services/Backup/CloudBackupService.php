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

    // Dropbox OAuth (refresh token → método permanente)
    public const DROPBOX_APP_KEY_KEY = 'backup.dropbox_app_key';

    public const DROPBOX_APP_SECRET_KEY = 'backup.dropbox_app_secret';

    public const DROPBOX_REFRESH_KEY = 'backup.dropbox_refresh_token';

    // Google Cloud Storage
    public const GCS_BUCKET_KEY = 'backup.gcs_bucket';

    public const GCS_PREFIX_KEY = 'backup.gcs_prefix';

    public const GCS_CREDENTIALS_KEY = 'backup.gcs_credentials';

    // Historial y estado de las ejecuciones.
    public const HISTORY_KEY = 'backup.history';

    /** Marca de un respaldo en curso (ISO 8601) para el indicador de la UI. */
    public const RUNNING_KEY = 'backup.running';

    /** Máximo de ejecuciones que se conservan en el historial. */
    private const HISTORY_LIMIT = 90;

    /** Un respaldo en curso más antiguo que esto se considera obsoleto (colgado). */
    private const RUNNING_STALE_MINUTES = 20;

    public function enabled(): bool
    {
        return (bool) Setting::value(self::ENABLED_KEY);
    }

    public function provider(): string
    {
        return (string) Setting::value(self::PROVIDER_KEY, 'dropbox');
    }

    /** ¿Hay un respaldo en curso (encolado o ejecutándose)? */
    public function isRunning(): bool
    {
        $raw = Setting::value(self::RUNNING_KEY);

        if (! $raw) {
            return false;
        }

        // Si la marca quedó "colgada" (Horizon caído, p. ej.), se considera vieja.
        return (bool) rescue(
            fn () => \Illuminate\Support\Carbon::parse((string) $raw)
                ->addMinutes(self::RUNNING_STALE_MINUTES)->isFuture(),
            false,
            false,
        );
    }

    public function markRunning(): void
    {
        Setting::put(self::RUNNING_KEY, now()->toIso8601String());
    }

    public function clearRunning(): void
    {
        Setting::put(self::RUNNING_KEY, '');
    }

    /**
     * Ejecuta el respaldo completo (dump + subida + purga). Devuelve true si se
     * completó correctamente.
     *
     * @param  bool  $manual  Ejecución bajo demanda: corre aunque los respaldos
     *                        automáticos estén desactivados (pero requiere
     *                        credenciales del proveedor).
     */
    public function run(bool $manual = false): bool
    {
        if (! $manual && ! $this->enabled()) {
            Log::info('backup: respaldos automáticos desactivados; se omite');

            return false;
        }

        $this->markRunning();

        try {
            $dump = $this->makeDump(config('database.default'));

            if ($dump === null) {
                $this->fail(__('messages.backup.fail_dump'));

                return false;
            }

            try {
                $this->provider() === 'google_cloud'
                    ? $this->uploadGcs($dump, basename($dump))
                    : $this->uploadDropbox($dump, basename($dump));

                $this->succeed(basename($dump));

                return true;
            } finally {
                @unlink($dump);
            }
        } catch (Throwable $e) {
            $this->fail($e->getMessage());

            return false;
        } finally {
            $this->clearRunning();
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

        // MySQL / MariaDB. El cliente de MariaDB reciente expone "mariadb-dump"
        // (el symlink "mysqldump" puede no existir), así que se detecta el binario.
        $binary = $this->dumpBinary();

        if ($binary === null) {
            throw new \RuntimeException('No se encontró mariadb-dump ni mysqldump en el servidor (falta el cliente de MariaDB/MySQL).');
        }

        $cfg = config("database.connections.{$connection}");
        $out = $target.'.sql';

        $baseArgs = [
            '-h', (string) ($cfg['host'] ?? '127.0.0.1'),
            '-P', (string) ($cfg['port'] ?? '3306'),
            '-u', (string) ($cfg['username'] ?? 'root'),
            '--password='.(string) ($cfg['password'] ?? ''),
            '--single-transaction',
            '--skip-lock-tables',
            '--no-tablespaces',
        ];
        $database = (string) ($cfg['database'] ?? '');

        // Muchos MySQL/MariaDB gestionados presentan un certificado autofirmado.
        // 1) TLS sin verificar el certificado (resuelve el "self-signed cert").
        // 2) Si el servidor rechaza TLS, se fuerza sin TLS (red interna de confianza).
        $sslModes = [
            ['--ssl-verify-server-cert=0'],
            ['--skip-ssl'],
        ];

        $lastError = '';
        foreach ($sslModes as $sslArgs) {
            $process = new Process([$binary, ...$baseArgs, ...$sslArgs, $database]);
            $process->setTimeout(600);
            $process->run();

            if ($process->isSuccessful()) {
                file_put_contents($out, $process->getOutput());

                if (is_file($out) && filesize($out) > 0) {
                    return $out;
                }

                @unlink($out);
                $lastError = 'el volcado quedó vacío';

                continue;
            }

            $lastError = trim($process->getErrorOutput()) ?: ('código de salida '.$process->getExitCode());

            // Si el fallo no es de TLS/SSL, reintentar con otro modo no ayudará.
            if (stripos($lastError, 'ssl') === false && stripos($lastError, 'tls') === false) {
                break;
            }
        }

        Log::error('backup: dump falló', ['binary' => $binary, 'err' => $lastError]);

        throw new \RuntimeException(basename($binary).': '.$lastError);
    }

    /** Localiza el binario de volcado disponible (prioriza mariadb-dump). */
    private function dumpBinary(): ?string
    {
        $finder = new \Symfony\Component\Process\ExecutableFinder();

        foreach (['mariadb-dump', 'mysqldump'] as $bin) {
            $path = $finder->find($bin);
            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    // ── Subida por proveedor ────────────────────────────────────────────────

    /**
     * Canjea un código de autorización de Dropbox por un refresh token
     * (flujo OAuth con token_access_type=offline). Nunca lanza.
     *
     * @return array{ok: bool, refresh_token?: string, message: string}
     */
    public function exchangeDropboxCode(string $appKey, string $appSecret, string $code): array
    {
        if ($appKey === '' || $appSecret === '' || $code === '') {
            return ['ok' => false, 'message' => 'Faltan App key, App secret o el código de autorización.'];
        }

        try {
            $res = Http::asForm()->withBasicAuth($appKey, $appSecret)->timeout(30)
                ->post('https://api.dropbox.com/oauth2/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                ]);

            $refresh = $res->json('refresh_token');

            if (! $res->successful() || ! $refresh) {
                return ['ok' => false, 'message' => 'Dropbox '.$res->status().': '.$this->briefBody($res->body())];
            }

            return ['ok' => true, 'refresh_token' => (string) $refresh, 'message' => ''];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resuelve el access token de Dropbox: si hay app key + secret + refresh
     * token, se canjea por uno nuevo (permanente); si no, usa el token directo.
     *
     * @param  array<string, mixed>  $o  Overrides (para la prueba antes de guardar).
     */
    private function dropboxAccessToken(array $o = []): string
    {
        $refresh = (string) ($o['dropbox_refresh_token'] ?? '') ?: (string) Setting::value(self::DROPBOX_REFRESH_KEY);
        $appKey = (string) ($o['dropbox_app_key'] ?? '') ?: (string) Setting::value(self::DROPBOX_APP_KEY_KEY);
        $appSecret = (string) ($o['dropbox_app_secret'] ?? '') ?: (string) Setting::value(self::DROPBOX_APP_SECRET_KEY);

        if ($refresh !== '' && $appKey !== '' && $appSecret !== '') {
            $res = Http::asForm()->withBasicAuth($appKey, $appSecret)->timeout(30)
                ->post('https://api.dropbox.com/oauth2/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refresh,
                ]);

            $token = $res->json('access_token');

            if (! $res->successful() || ! $token) {
                throw new \RuntimeException('Dropbox OAuth '.$res->status().': '.$this->briefBody($res->body()));
            }

            return (string) $token;
        }

        $token = (string) ($o['dropbox_token'] ?? '') ?: (string) Setting::value(self::DROPBOX_TOKEN_KEY);

        if ($token === '') {
            throw new \RuntimeException('Falta el token de acceso de Dropbox (o las credenciales OAuth: app key, app secret y refresh token).');
        }

        return $token;
    }

    protected function uploadDropbox(string $path, string $name): void
    {
        $token = $this->dropboxAccessToken();

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

            throw new \RuntimeException('Dropbox '.$res->status().': '.$this->briefBody($res->body()));
        }

        rescue(fn () => $this->pruneDropbox($token, $folder), null, false);
    }

    protected function uploadGcs(string $path, string $name): void
    {
        $bucket = (string) Setting::value(self::GCS_BUCKET_KEY);
        $credentials = (string) Setting::value(self::GCS_CREDENTIALS_KEY);

        if ($bucket === '' || $credentials === '') {
            throw new \RuntimeException('Falta el bucket o las credenciales de Google Cloud.');
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

            throw new \RuntimeException('Google Cloud '.$res->status().': '.$this->briefBody($res->body()));
        }
    }

    /** Recorta el cuerpo de una respuesta de error para el historial/correo. */
    private function briefBody(string $body): string
    {
        $body = trim($body);

        return mb_strlen($body) > 300 ? mb_substr($body, 0, 300).'…' : ($body !== '' ? $body : 'sin detalle');
    }

    // ── Pruebas de conexión ─────────────────────────────────────────────────

    /** @param array<string, mixed> $o @return array{ok: bool, message: string} */
    protected function testDropbox(array $o): array
    {
        try {
            $token = $this->dropboxAccessToken($o);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
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
