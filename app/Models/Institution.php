<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Institution extends Model
{
    use LogsActivity;

    /** Catálogos (opciones fijas de los selectores del mantenimiento). */
    public const TYPES = [
        'Ministerio', 'Viceministerio', 'Dirección', 'Instituto', 'Consejo',
        'Comisión', 'Banco', 'Institución Autónoma', 'Institución Descentralizada',
        'Empresa Pública', 'Otro',
    ];

    public const SECTORS = [
        'Economía', 'Educación', 'Salud', 'Infraestructura', 'Seguridad',
        'Agropecuario', 'Turismo', 'Financiero', 'Tecnología', 'Comercio',
        'Trabajo', 'Medio Ambiente', 'Social', 'Otro',
    ];

    public const DEPENDENCIES = [
        'Gobierno Central', 'Institución Autónoma', 'Empresa Pública', 'Municipal', 'Otro',
    ];

    public const STATUSES = ['activa', 'inactiva'];

    protected $fillable = [
        'code', 'name', 'short_name', 'type', 'sector', 'rnc', 'status', 'logo_path',
        'parent_id', 'admin_dependency',
        'phone_main', 'phone_alt', 'email', 'website',
        'province', 'addr_sector', 'addr_street', 'addr_number', 'addr_reference', 'postal_code',
        'authority_name', 'authority_position', 'authority_email', 'authority_phone',
        'created_by', 'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /** Solo instituciones activas (alimentan los desplegables del sistema). */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'activa');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** Institución superior (jerarquía de gobierno). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** URL pública del logo institucional (o null). */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
