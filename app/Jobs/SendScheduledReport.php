<?php

namespace App\Jobs;

use App\Services\Reports\ScheduledReports;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Genera y envía por correo un informe programado (cola Horizon), para no
 * bloquear el planificador ni la petición web. El tipo indica cuál.
 */
class SendScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    /** @param 'access_review'|'risk'|'minister' $type */
    public function __construct(public string $type) {}

    public function handle(ScheduledReports $reports): void
    {
        match ($this->type) {
            'access_review' => $reports->sendAccessReviewReminder(),
            'risk' => $reports->sendRiskReport(),
            'minister' => $reports->sendMinisterReport(),
            default => null,
        };
    }
}
