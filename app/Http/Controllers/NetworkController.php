<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NetworkController extends Controller
{
    private const CHANNELS = [
        'general' => ['label' => 'General', 'description' => 'Coordinación entre gestores del Ministerio y sus instituciones adscritas.'],
        'alertas' => ['label' => 'Alertas', 'description' => 'Proyectos en riesgo o retrasados que requieren atención.'],
        'seguimiento' => ['label' => 'Seguimiento', 'description' => 'Actualizaciones periódicas de avance de los proyectos.'],
        'metas' => ['label' => 'Metas Presidenciales', 'description' => 'Coordinación en torno a las metas presidenciales del país.'],
    ];

    private const ONLINE_MINUTES = 5;

    public function index(Request $request): Response
    {
        $this->touch($request);
        $channel = $this->channelKey($request->query('canal'));

        return Inertia::render('Network/Index', [
            'channels' => $this->channels(),
            'activeChannel' => $channel,
            'messages' => $this->messagesFor($channel),
            'gestores' => $this->gestores(),
            'onlineCount' => $this->onlineCount(),
            'institutionsConnected' => (int) User::whereNotNull('institution_id')->distinct()->count('institution_id'),
            'projects' => Project::orderBy('code')->get(['id', 'code', 'name'])
                ->map(fn (Project $p) => ['id' => $p->id, 'label' => $p->code.' — '.$p->name]),
            'currentUser' => $request->user()->name,
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $this->touch($request);

        return response()->json([
            'messages' => $this->messagesFor($this->channelKey($request->query('canal'))),
            'onlineCount' => $this->onlineCount(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(array_keys(self::CHANNELS))],
            'body' => ['required', 'string', 'max:800'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $this->touch($request);

        $message = Message::create([
            'channel' => $data['channel'],
            'user_id' => $request->user()->id,
            'project_id' => $data['project_id'] ?? null,
            'body' => $data['body'],
        ]);

        return response()->json(['message' => $this->mapMessage($message->load(['user.institution', 'project']))]);
    }

    /** Publica en #Alertas un resumen de los proyectos en riesgo o retrasados. */
    public function notifyRisks(Request $request): JsonResponse
    {
        $this->touch($request);

        $atRisk = Project::with('institution')
            ->where(fn ($q) => $q->where('risk_level', 'alto')->orWhereIn('status', ['en_riesgo', 'retrasado']))
            ->orderBy('code')
            ->get();

        if ($atRisk->isEmpty()) {
            return response()->json(['created' => 0, 'note' => 'No hay proyectos en riesgo o retrasados.']);
        }

        $lines = $atRisk
            ->map(fn (Project $p) => '• '.$p->code.' — '.$p->name.' ('.($p->institution?->short_name ?? 's/i').'), avance '.$p->physical_progress.'%')
            ->implode("\n");

        $message = Message::create([
            'channel' => 'alertas',
            'user_id' => $request->user()->id,
            'body' => '⚠ Alerta automática — proyectos en riesgo o retrasados ('.$atRisk->count()."):\n".$lines,
            'system' => true,
        ]);

        return response()->json([
            'created' => $atRisk->count(),
            'message' => $this->mapMessage($message->load(['user.institution', 'project'])),
        ]);
    }

    private function channelKey(?string $c): string
    {
        return $c !== null && array_key_exists($c, self::CHANNELS) ? $c : 'general';
    }

    private function channels(): array
    {
        return collect(self::CHANNELS)->map(fn (array $m, string $key) => [
            'key' => $key,
            'label' => $m['label'],
            'description' => $m['description'],
            'count' => Message::where('channel', $key)->count(),
        ])->values()->all();
    }

    private function messagesFor(string $channel): array
    {
        return Message::with(['user.institution', 'project'])
            ->where('channel', $channel)
            ->orderBy('created_at')
            ->limit(200)
            ->get()
            ->map(fn (Message $m) => $this->mapMessage($m))
            ->all();
    }

    private function mapMessage(Message $m): array
    {
        return [
            'id' => $m->id,
            'author' => $m->user?->name ?? 'Sistema',
            'institution' => $m->user?->institution?->short_name ?? '',
            'initials' => $this->initials($m->user?->name ?? 'S'),
            'body' => $m->body,
            'project' => $m->project?->code,
            'system' => $m->system,
            'time' => $m->created_at?->format('h:i A'),
        ];
    }

    private function gestores(): array
    {
        $threshold = now()->subMinutes(self::ONLINE_MINUTES);

        return User::with('institution')
            ->whereNotNull('institution_id')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'name' => $u->name,
                'institution' => $u->institution?->short_name ?? '',
                'initials' => $this->initials($u->name),
                'online' => $u->last_seen_at !== null && $u->last_seen_at->greaterThan($threshold),
            ])
            ->all();
    }

    private function onlineCount(): int
    {
        return User::whereNotNull('institution_id')
            ->where('last_seen_at', '>=', now()->subMinutes(self::ONLINE_MINUTES))
            ->count();
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = mb_substr(end($parts) ?: '', 0, 1);

        return mb_strtoupper($first.$last);
    }

    private function touch(Request $request): void
    {
        $request->user()->forceFill(['last_seen_at' => now()])->saveQuietly();
    }
}
