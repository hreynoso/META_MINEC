<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Kpi extends Model
{
    use LogsActivity;

    protected $fillable = [
        'key',
        'label',
        'value',
        'unit',
        'target',
        'trend',
        'strategic',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'target' => 'float',
            'strategic' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
