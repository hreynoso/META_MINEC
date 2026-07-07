<?php

namespace App\Http\Controllers;

use App\Models\MailEvent;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MailWebhookController extends Controller
{
    /** Recibe los eventos de Mailgun (entregas, rebotes, aperturas, etc.). */
    public function mailgun(Request $request): JsonResponse
    {
        $signature = (array) $request->input('signature', []);
        $key = Setting::value('mail.mg.webhook_key');

        // Verificación de firma si hay clave configurada.
        if (filled($key) && isset($signature['timestamp'], $signature['token'], $signature['signature'])) {
            $expected = hash_hmac('sha256', $signature['timestamp'].$signature['token'], (string) $key);
            if (! hash_equals($expected, (string) $signature['signature'])) {
                return response()->json(['ok' => false], 406);
            }
        }

        $d = (array) $request->input('event-data', []);
        $ts = $d['timestamp'] ?? null;

        MailEvent::create([
            'event' => $d['event'] ?? 'unknown',
            'severity' => $d['severity'] ?? null,
            'recipient' => $d['recipient'] ?? null,
            'reason' => $d['reason'] ?? ($d['delivery-status']['description'] ?? null),
            'occurred_at' => $ts ? Carbon::createFromTimestamp((int) $ts) : now(),
            'payload' => $d ?: null,
        ]);

        return response()->json(['ok' => true]);
    }
}
