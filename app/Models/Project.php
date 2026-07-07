<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'institution_id',
        'presidential_goal_id',
        'status',
        'risk_level',
        'budget',
        'executed',
        'physical_progress',
        'start_date',
        'end_date',
        'source',
        'responsible',
        'beneficiaries',
        'location',
        'deliverables',
        'expected_impact',
        'benefits',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'float',
            'executed' => 'float',
            'physical_progress' => 'integer',
            'beneficiaries' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'deliverables' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function presidentialGoal(): BelongsTo
    {
        return $this->belongsTo(PresidentialGoal::class);
    }

    /** Avance financiero (ejecutado / presupuesto) en porcentaje entero. */
    public function financialProgress(): int
    {
        return $this->budget > 0
            ? (int) round(($this->executed / $this->budget) * 100)
            : 0;
    }
}
