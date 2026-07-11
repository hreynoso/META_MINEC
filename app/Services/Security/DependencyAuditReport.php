<?php

namespace App\Services\Security;

use App\Support\Branding;
use App\Support\ExportName;
use App\Support\LocalTime;
use App\Support\SecurityAlert;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Genera el informe PDF del análisis de dependencias (A.8.8) y lo envía por
 * correo al equipo de seguridad TIC. El envío nunca interrumpe el flujo
 * (todo va dentro de rescue()), coherente con App\Support\SecurityAlert.
 */
class DependencyAuditReport
{
    public function __construct(private readonly DependencyAudit $audit) {}

    /** Documento PDF del último resultado almacenado. */
    public function pdf(): PdfDocument
    {
        return Pdf::loadView('reports.deps', $this->viewData())
            ->setPaper('a4', 'portrait');
    }

    /** Nombre de archivo del informe. */
    public function filename(): string
    {
        return ExportName::make('Analisis de dependencias', 'pdf');
    }

    /**
     * Genera el PDF y lo envía al equipo de seguridad con el informe adjunto.
     * Devuelve true si se despachó el correo.
     */
    public function send(): bool
    {
        $recipients = SecurityAlert::recipients();

        if (empty($recipients)) {
            return false;
        }

        $result = $this->audit->latest();
        $count = (int) ($result['count'] ?? 0);
        $available = (bool) ($result['available'] ?? false);

        $subject = 'META · Informe de análisis de dependencias (A.8.8)';
        $body = $this->emailBody($available, $count);
        $pdf = $this->pdf()->output();
        $filename = $this->filename();

        return (bool) rescue(function () use ($recipients, $subject, $body, $pdf, $filename) {
            Mail::raw($body, function ($m) use ($recipients, $subject, $pdf, $filename) {
                $m->to($recipients)
                    ->subject($subject)
                    ->attachData($pdf, $filename, ['mime' => 'application/pdf']);
            });

            return true;
        }, false, false);
    }

    private function emailBody(bool $available, int $count): string
    {
        $estado = ! $available
            ? 'La herramienta de análisis no está disponible en el entorno; el análisis autoritativo corre en el pipeline de CI.'
            : ($count === 0
                ? 'Sin vulnerabilidades conocidas en las dependencias.'
                : "Se detectaron {$count} vulnerabilidad(es) conocida(s) que requieren atención.");

        return "Informe programado de análisis de dependencias (ISO 27001 A.8.8).\n\n"
            .$estado."\n\n"
            ."Periodicidad configurada: cada {$this->audit->intervalDays()} días.\n"
            ."Fecha (UTC): ".now()->toDateTimeString()."\n\n"
            ."Se adjunta el informe en PDF con el detalle.";
    }

    /** @return array<string, mixed> */
    private function viewData(): array
    {
        $result = $this->audit->latest();
        $ranAt = $result['ran_at'] ?? null;

        return [
            'title' => 'Análisis de dependencias',
            'subtitle' => 'Vulnerabilidades conocidas en las dependencias del sistema.',
            'generated_at' => LocalTime::format(now(), 'd/m/Y H:i'),
            'logo' => Branding::dataUri('logo_login'),
            'institution' => config('branding.institution'),
            'available' => (bool) ($result['available'] ?? false),
            'count' => (int) ($result['count'] ?? 0),
            'advisories' => $result['advisories'] ?? [],
            'ran_at' => $ranAt ? LocalTime::format(Carbon::parse($ranAt), 'd/m/Y H:i') : null,
            'interval_days' => $this->audit->intervalDays(),
        ];
    }
}
