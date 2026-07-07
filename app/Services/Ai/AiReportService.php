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
            return (string) $model;
        }

        return match ($this->provider()) {
            'gemini' => 'gemini-1.5-pro',
            'openai' => 'gpt-4o',
            default => (string) config('anthropic.model', 'claude-sonnet-4-6'),
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

        return match ($this->provider()) {
            'gemini' => $this->gemini($prompt),
            'openai' => $this->openai($prompt),
            default => $this->anthropic($prompt),
        };
    }

    private function anthropic(string $prompt): string
    {
        $base = rtrim((string) config('anthropic.base_url', 'https://api.anthropic.com/v1'), '/');

        $res = Http::withHeaders([
            'x-api-key' => $this->apiKey(),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post("{$base}/messages", [
            'model' => $this->model(),
            'max_tokens' => 2000,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ])->throw();

        return (string) $res->json('content.0.text', '');
    }

    private function gemini(string $prompt): string
    {
        $model = $this->model();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $res = Http::timeout(120)->post($url.'?key='.$this->apiKey(), [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ])->throw();

        return (string) $res->json('candidates.0.content.parts.0.text', '');
    }

    private function openai(string $prompt): string
    {
        $res = Http::withToken($this->apiKey())->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model(),
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ])->throw();

        return (string) $res->json('choices.0.message.content', '');
    }
}
