<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = [
        'code',
        'name',
        'short_name',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
