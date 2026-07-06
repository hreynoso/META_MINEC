<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Busqueda por tokens en backend: divide el termino en palabras y exige que
 * TODAS aparezcan en al menos una de las columnas indicadas (AND entre tokens,
 * OR entre columnas). Espejo del composable useTokenSearch del frontend.
 */
class TokenSearch
{
    public static function apply(Builder $query, ?string $term, array $columns): Builder
    {
        $term = trim((string) $term);

        if ($term === '' || empty($columns)) {
            return $query;
        }

        $tokens = preg_split('/\s+/', $term) ?: [];

        foreach ($tokens as $token) {
            $query->where(function (Builder $q) use ($token, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%'.Str::lower($token).'%');
                }
            });
        }

        return $query;
    }
}
