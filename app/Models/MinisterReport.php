<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinisterReport extends Model
{
    protected $fillable = [
        'user_id',
        'from',
        'to',
        'institutions',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'from' => 'date',
            'to' => 'date',
            'institutions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
