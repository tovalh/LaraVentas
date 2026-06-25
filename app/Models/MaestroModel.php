<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo base de los maestros del ERP.
 *
 * Aporta, "gratis", las mismas convenciones del sistema original:
 *  - Multi-empresa automática (BelongsToEmpresa → filtra/asigna idgen_empresa)
 *  - Auditoría automática (HasAuditoria → userins/fechains/usermod/fechamod)
 *  - Sin timestamps de Laravel (usamos los campos de auditoría)
 *  - habilitado casteado a boolean
 *
 * Un maestro nuevo solo declara $table, $primaryKey, $fillable y casts extra.
 */
abstract class MaestroModel extends Model
{
    use BelongsToEmpresa, HasAuditoria;

    public $timestamps = false;

    protected $casts = [
        'habilitado' => 'boolean',
        'fechains'   => 'datetime',
        'fechamod'   => 'datetime',
    ];
}
