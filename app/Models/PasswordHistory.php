<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de contrasenas (control ISO 27001). Aplica solo si en algun
 * momento se habilita login local; con SSO puro queda como bitacora.
 */
class PasswordHistory extends Model
{
    protected $table = 'password_histories';

    protected $fillable = ['user_id', 'password'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
