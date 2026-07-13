<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de IA agnóstico del proveedor. Lee la configuración administrable
 * desde Settings (Configuración → Inteligencia Artificial) y despacha la
 * generación al proveedor seleccionado (Anthropic, Gemini u OpenAI).
 */
class AiReportService
{
    public function provider(): string
    {
        return (string) Setting::value('ai.provider', 'anthropic');
    }

    /** Nombre legible del proveedor configurado. */
    public function providerLabel(): string
    {
        return [
            'anthropic' => 'Anthropic (Claude)',
            'gemini' => 'Google Gemini',
            'openai' => 'OpenAI',
        ][$this->provider()] ?? 'IA';
    }

    public function model(): string
    {
        $model = Setting::value('ai.model');

        if (filled($model)) {
            // Los modelos Gemini 1.5 fueron retirados del API; se sustituyen por el
            // actual para no romper una configuración guardada anteriormente.
            if ($this->provider() === 'gemini' && str_starts_with((string) $model, 'gemini-1.5')) {
                return $this->defaultModel('gemini');
            }

            return (string) $model;
        }

        return $this->defaultModel($this->provider());
    }

    /** Modelo por defecto de un proveedor. */
    private function defaultModel(string $provider): string
    {
        return match ($provider) {
            'gemini' => 'gemini-2.0-flash',
            'openai' => 'gpt-4o',
            default => (string) config('anthropic.model', 'claude-sonnet-5'),
        };
    }

    public function apiKey(): ?string
    {
        $key = Setting::value('ai.api_key');

        return filled($key) ? (string) $key : config('anthropic.api_key');
    }

    public function enabled(): bool
    {
        return (bool) Setting::value('ai.enabled', false);
    }

    public function isConfigured(): bool
    {
        return $this->enabled() && filled($this->apiKey());
    }

    /** Genera texto a partir del prompt usando el proveedor configurado. */
    public function generate(string $prompt): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('El API de IA no está configurado. Ve a Configuración → Inteligencia Artificial.');
        }

        return $this->dispatch($this->provider(), $prompt, (string) $this->apiKey(), $this->model());
    }

    /**
     * Prueba la conexión con un proveedor usando credenciales explícitas (o las
     * guardadas si no se pasan). Nunca lanza: devuelve el resultado.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(string $provider, ?string $model, ?string $apiKey): array
    {
        $apiKey = filled($apiKey) ? (string) $apiKey : (string) $this->apiKey();

        if (blank($apiKey)) {
            return ['ok' => false, 'message' => 'No hay clave del API configurada para probar. Guarda una clave o escríbela.'];
        }

        $model = filled($model) ? (string) $model : $this->defaultModel($provider);

        try {
            $this->dispatch($provider, 'Responde únicamente con la palabra: OK', $apiKey, $model);

            return ['ok' => true, 'message' => 'Conexión exitosa: el proveedor respondió correctamente.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Error de conexión: '.$e->getMessage()];
        }
    }

    /**
     * Lista los modelos que la clave del proveedor puede usar (para elegir uno
     * válido; la disponibilidad varía por clave/proyecto/región). Nunca lanza.
     *
     * @return array{ok: bool, models: string[], message: string}
     */
    public function listModels(string $provider, ?string $apiKey): array
    {
        $apiKey = filled($apiKey) ? (string) $apiKey : (string) $this->apiKey();

        if (blank($apiKey)) {
            return ['ok' => false, 'models' => [], 'message' => 'No hay clave del API para consultar los modelos.'];
        }

        try {
            $models = match ($provider) {
                'gemini' => $this->geminiModels($apiKey),
                'openai' => $this->openaiModels($apiKey),
                default => $this->anthropicModels($apiKey),
            };

            sort($models);

            return ['ok' => true, 'models' => array_values(array_unique($models)), 'message' => ''];
        } catch (\Throwable $e) {
            return ['ok' => false, 'models' => [], 'message' => $e->getMessage()];
        }
    }

    /** @return string[] */
    private function geminiModels(string $apiKey): array
    {
        $res = Http::timeout(30)->get('https://generativelanguage.googleapis.com/v1beta/models', [
            'key' => $apiKey,
            'pageSize' => 200,
        ])->throw();

        $models = [];
        foreach ((array) $res->json('models', []) as $m) {
            $methods = (array) ($m['supportedGenerationMethods'] ?? []);
            if (in_array('generateContent', $methods, true)) {
                // "models/gemini-2.0-flash" → "gemini-2.0-flash"
                $models[] = preg_replace('#^models/#', '', (string) ($m['name'] ?? ''));
            }
        }

        return array_filter($models);
    }

    /** @return string[] */
    private function openaiModels(string $apiKey): array
    {
        $res = Http::withToken($apiKey)->timeout(30)->get('https://api.openai.com/v1/models')->throw();

        $models = [];
        foreach ((array) $res->json('data', []) as $m) {
            $id = (string) ($m['id'] ?? '');
            if (str_starts_with($id, 'gpt')) {
                $models[] = $id;
            }
        }

        return $models;
    }

    /** @return string[] */
    private function anthropicModels(string $apiKey): array
    {
        $base = rtrim((string) config('anthropic.base_url', 'https://api.anthropic.com/v1'), '/');

        $res = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->get("{$base}/models", ['limit' => 100])->throw();

        return array_values(array_filter(array_map(
            fn ($m) => (string) ($m['id'] ?? ''),
            (array) $res->json('data', []),
        )));
    }

    private function dispatch(string $provider, string $prompt, string $apiKey, string $model): string
    {
        return match ($provider) {
            'gemini' => $this->gemini($prompt, $apiKey, $model),
            'openai' => $this->openai($prompt, $apiKey, $model),
            default => $this->anthropic($prompt, $apiKey, $model),
        };
    }

    private function anthropic(string $prompt, string $apiKey, string $model): string
    {
        $base = rtrim((string) config('anthropic.base_url', 'https://api.anthropic.com/v1'), '/');

        $res = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post("{$base}/messages", [
            'model' => $model,
            'max_tokens' => 2000,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ])->throw();

        return (string) $res->json('content.0.text', '');
    }

    private function gemini(string $prompt, string $apiKey, string $model): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $res = Http::timeout(120)->post($url.'?key='.$apiKey, [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ])->throw();

        return (string) $res->json('candidates.0.content.parts.0.text', '');
    }

    private function openai(string $prompt, string $apiKey, string $model): string
    {
        $res = Http::withToken($apiKey)->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ])->throw();

        return (string) $res->json('choices.0.message.content', '');
    }
}
