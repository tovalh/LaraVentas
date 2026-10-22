<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Multi-empresa (idgen_empresa).
 *
 * - Aplica un global scope que filtra SIEMPRE por la empresa del usuario logueado.
 * - Al crear un registro, asigna automáticamente idgen_empresa.
 *
 * Reemplaza el filtrado manual por empresa: aquí es automático y a prueba de olvidos.
 */
// TODO(tests): feature test (Pest/PHPUnit) que pruebe el aislamiento multi-empresa:
// un usuario de la Empresa A no puede listar, ver ni editar registros de la Empresa B.
trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where(
                    $builder->getModel()->getTable() . '.idgen_empresa',
                    Auth::user()->idgen_empresa
                );
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && empty($model->idgen_empresa)) {
                $model->idgen_empresa = Auth::user()->idgen_empresa;
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class, 'idgen_empresa', 'idgen_empresa');
    }
}
