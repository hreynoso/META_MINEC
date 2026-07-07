<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailEvent extends Model
{
    protected $fillable = ['event', 'severity', 'recipient', 'reason', 'occurred_at', 'payload'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'payload' => 'array',
        ];
    }
}
