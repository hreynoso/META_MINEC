<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunBackup;
use App\Models\Setting;
use App\Services\Backup\CloudBackupService;
use App\Support\LocalTime;
use App\Support\SecurityAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Configuración → Respaldos automáticos (A.8.13). Conecta la base de datos con
 * una cuenta de almacenamiento en la nube (Dropbox o Google Cloud Storage).
 * Los secretos (token / credenciales) nunca se envían completos al cliente:
 * solo se indica si están configurados y se actualizan si se escribe uno nuevo.
 */
class BackupSettingsController extends Controller
{
    public function edit(CloudBackupService $backup): Response
    {
        $lastRunRaw = Setting::value('backup.last_run_at');

        return Inertia::render('Admin/BackupSettings', [
            'settings' => [
                'enabled' => (bool) Setting::value(CloudBackupService::ENABLED_KEY),
                'provider' => (string) Setting::value(CloudBackupService::PROVIDER_KEY, 'dropbox'),
                'frequency' => (string) Setting::value(CloudBackupService::FREQUENCY_KEY, 'daily'),
                'time' => (string) Setting::value(CloudBackupService::TIME_KEY, '02:00'),
                'retention_days' => (int) Setting::value(CloudBackupService::RETENTION_KEY, 30),
                // Dropbox
                'dropbox_folder' => (string) Setting::value(CloudBackupService::DROPBOX_FOLDER_KEY, '/META/backups'),
                'has_dropbox_token' => filled(Setting::value(CloudBackupService::DROPBOX_TOKEN_KEY)),
                'dropbox_app_key' => (string) Setting::value(CloudBackupService::DROPBOX_APP_KEY_KEY, ''),
                'has_dropbox_app_secret' => filled(Setting::value(CloudBackupService::DROPBOX_APP_SECRET_KEY)),
                'has_dropbox_refresh_token' => filled(Setting::value(CloudBackupService::DROPBOX_REFRESH_KEY)),
                // Google Cloud Storage
                'gcs_bucket' => (string) Setting::value(CloudBackupService::GCS_BUCKET_KEY, ''),
                'gcs_prefix' => (string) Setting::value(CloudBackupService::GCS_PREFIX_KEY, 'meta/backups'),
                'has_gcs_credentials' => filled(Setting::value(CloudBackupService::GCS_CREDENTIALS_KEY)),
                // Estado del último respaldo
                'last_status' => (string) Setting::value('backup.last_status', ''),
                'last_run_at' => $lastRunRaw ? LocalTime::format(Carbon::parse((string) $lastRunRaw)) : null,
            ],
            'history' => $this->history(),
            'running' => $backup->isRunning(),
        ]);
    }

    /**
     * Historial de respaldos automáticos, más reciente primero (A.8.13).
     *
     * @return array<int, array{at: string|null, date: string|null, status: string, provider: string, detail: string}>
     */
    private function history(): array
    {
        $raw = json_decode((string) Setting::value(CloudBackupService::HISTORY_KEY), true);

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(function (array $e) {
                $at = ! empty($e['at']) ? Carbon::parse((string) $e['at']) : null;

                return [
                    'at' => $at ? LocalTime::format($at) : null,
                    'date' => $at ? LocalTime::format($at, 'Y-m-d') : null,
                    'status' => (string) ($e['status'] ?? 'error'),
                    'provider' => (string) ($e['provider'] ?? ''),
                    'detail' => (string) ($e['detail'] ?? ''),
                ];
            })
            ->all();
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'provider' => ['required', 'in:dropbox,google_cloud'],
            'frequency' => ['required', 'in:daily,weekly'],
            'time' => ['required', 'date_format:H:i'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            // Dropbox (los tokens "sl." pueden superar holgadamente los 1000 chars)
            'dropbox_folder' => ['nullable', 'string', 'max:255'],
            'dropbox_token' => ['nullable', 'string', 'max:8192'],
            'dropbox_app_key' => ['nullable', 'string', 'max:255'],
            'dropbox_app_secret' => ['nullable', 'string', 'max:255'],
            'dropbox_refresh_token' => ['nullable', 'string', 'max:1024'],
            // Google Cloud
            'gcs_bucket' => ['nullable', 'string', 'max:255'],
            'gcs_prefix' => ['nullable', 'string', 'max:255'],
            'gcs_credentials' => ['nullable', 'string', 'max:20000'],
        ], [], [
            'provider' => 'proveedor',
            'time' => 'hora',
            'retention_days' => 'retención',
            'gcs_credentials' => 'credenciales de Google Cloud',
        ]);

        // Si se pega el JSON de credenciales, validar que sea JSON válido.
        if (filled($data['gcs_credentials'] ?? null)) {
            $decoded = json_decode((string) $data['gcs_credentials'], true);
            if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                return back()->withErrors(['gcs_credentials' => __('messages.backup.invalid_credentials')]);
            }
        }

        Setting::put(CloudBackupService::ENABLED_KEY, $request->boolean('enabled') ? '1' : '');
        Setting::put(CloudBackupService::PROVIDER_KEY, $data['provider']);
        Setting::put(CloudBackupService::FREQUENCY_KEY, $data['frequency']);
        Setting::put(CloudBackupService::TIME_KEY, $data['time']);
        Setting::put(CloudBackupService::RETENTION_KEY, (string) $data['retention_days']);
        Setting::put(CloudBackupService::DROPBOX_FOLDER_KEY, $data['dropbox_folder'] ?? '/META/backups');
        Setting::put(CloudBackupService::DROPBOX_APP_KEY_KEY, $data['dropbox_app_key'] ?? '');
        Setting::put(CloudBackupService::GCS_BUCKET_KEY, $data['gcs_bucket'] ?? '');
        Setting::put(CloudBackupService::GCS_PREFIX_KEY, $data['gcs_prefix'] ?? 'meta/backups');

        // Secretos: solo se sobrescriben si el usuario escribió uno nuevo.
        if (filled($data['dropbox_token'] ?? null)) {
            Setting::put(CloudBackupService::DROPBOX_TOKEN_KEY, $data['dropbox_token']);
        }
        if (filled($data['dropbox_app_secret'] ?? null)) {
            Setting::put(CloudBackupService::DROPBOX_APP_SECRET_KEY, $data['dropbox_app_secret']);
        }
        if (filled($data['dropbox_refresh_token'] ?? null)) {
            Setting::put(CloudBackupService::DROPBOX_REFRESH_KEY, $data['dropbox_refresh_token']);
        }
        if (filled($data['gcs_credentials'] ?? null)) {
            Setting::put(CloudBackupService::GCS_CREDENTIALS_KEY, $data['gcs_credentials']);
        }

        SecurityAlert::configChanged('Respaldos automáticos (proveedor / credenciales de almacenamiento en la nube)');

        return back()->with('success', __('messages.backup.saved'));
    }

    /**
     * Genera un respaldo bajo demanda. Se ejecuta en segundo plano (cola Horizon)
     * para no bloquear la petición; el resultado aparece en el historial y, si
     * falla, se notifica a los Super Admin.
     */
    public function runNow(CloudBackupService $backup): RedirectResponse
    {
        // Se marca "en curso" de inmediato para que la UI lo refleje antes de que
        // Horizon recoja el Job (el servicio lo vuelve a marcar y lo limpia al final).
        $backup->markRunning();
        RunBackup::dispatch();

        return back()->with('success', __('messages.backup.run_queued'));
    }

    /** Canjea el código de autorización de Dropbox por un refresh token y lo guarda. */
    public function authorizeDropbox(Request $request, CloudBackupService $backup): RedirectResponse
    {
        $data = $request->validate([
            'dropbox_app_key' => ['required', 'string', 'max:255'],
            'dropbox_app_secret' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:1024'],
        ], [], ['dropbox_app_key' => 'App key', 'code' => 'código de autorización']);

        // App secret: el escrito o el guardado previamente.
        $appSecret = filled($data['dropbox_app_secret'] ?? null)
            ? (string) $data['dropbox_app_secret']
            : (string) Setting::value(CloudBackupService::DROPBOX_APP_SECRET_KEY);

        $result = $backup->exchangeDropboxCode($data['dropbox_app_key'], $appSecret, $data['code']);

        if (! $result['ok']) {
            return back()->with('error', __('messages.backup.oauth_failed', ['detail' => $result['message']]));
        }

        Setting::put(CloudBackupService::DROPBOX_APP_KEY_KEY, $data['dropbox_app_key']);
        if (filled($appSecret)) {
            Setting::put(CloudBackupService::DROPBOX_APP_SECRET_KEY, $appSecret);
        }
        Setting::put(CloudBackupService::DROPBOX_REFRESH_KEY, $result['refresh_token']);

        return back()->with('success', __('messages.backup.oauth_ok'));
    }

    /** Prueba la conexión con el proveedor (usa credenciales escritas o guardadas). */
    public function test(Request $request, CloudBackupService $backup): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:dropbox,google_cloud'],
            'dropbox_token' => ['nullable', 'string', 'max:8192'],
            'dropbox_app_key' => ['nullable', 'string', 'max:255'],
            'dropbox_app_secret' => ['nullable', 'string', 'max:255'],
            'dropbox_refresh_token' => ['nullable', 'string', 'max:1024'],
            'gcs_bucket' => ['nullable', 'string', 'max:255'],
            'gcs_credentials' => ['nullable', 'string', 'max:20000'],
        ]);

        return response()->json($backup->test($data['provider'], $data));
    }
}
