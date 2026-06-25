<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Auditoría estilo ERP original:
 *  - Al crear  → userins + fechains
 *  - Al guardar → usermod + fechamod
 *
 * Reemplaza el seteo manual en cada lfn_save(): aquí es automático.
 */
trait HasAuditoria
{
    public static function bootHasAuditoria(): void
    {
        static::creating(function ($model) {
            $model->userins  = static::auditUser();
            $model->fechains = now();
        });

        static::saving(function ($model) {
            $model->usermod  = static::auditUser();
            $model->fechamod = now();
        });
    }

    protected static function auditUser(): string
    {
        $user = Auth::user();

        return $user?->username ?? $user?->email ?? 'system';
    }
}
