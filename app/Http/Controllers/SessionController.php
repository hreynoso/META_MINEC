<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    /** Minutos de inactividad tras los que un usuario deja de contar como conectado. */
    private const ONLINE_MINUTES = 5;

    public function keepAlive(): JsonResponse
    {
        return response()->json(['ok' => true, 'ts' => now()->timestamp]);
    }

    /**
     * Usuarios conectados (activos en los últimos minutos). Alimenta el modal del
     * contador "Conectados" en Configuración. Tolerante si falta la columna.
     */
    public function connected(): JsonResponse
    {
        $users = rescue(fn () => User::query()
            ->where('last_seen_at', '>=', now()->subMinutes(self::ONLINE_MINUTES))
            ->orderByDesc('last_seen_at')
            ->get(['id', 'name', 'email', 'avatar_path', 'last_seen_at']), collect(), false);

        return response()->json([
            'users' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatarUrl(),
                'initials' => $this->initials($u->name),
                'lastActive' => $u->last_seen_at?->diffForHumans(),
            ])->all(),
            'count' => $users->count(),
        ]);
    }

    /** Iniciales a partir del nombre (primera y última palabra). */
    private function initials(?string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim((string) $name)) ?: []));

        if (empty($parts)) {
            return 'U';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}
