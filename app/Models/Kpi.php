<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
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
}
