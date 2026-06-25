<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Model;

/**
 * Movimiento de caja (ven_cajamovimiento) — un EVENTO del ledger.
 * tipo: apertura | ingreso | egreso. Anular no borra: marca anulado.
 */
class CajaMovimiento extends Model
{
    use BelongsToEmpresa, HasAuditoria;

    protected $table = 'ven_cajamovimiento';
    protected $primaryKey = 'idven_cajamovimiento';
    public $timestamps = false;

    protected $fillable = [
        'idgen_empresa', 'idven_caja', 'tipo', 'idven_cajarendicionegreso_tipo',
        'descripcion', 'monto', 'fecha', 'anulado', 'anulamotivo', 'anulauser', 'anulafecha', 'habilitado',
    ];

    protected $casts = [
        'anulado'    => 'boolean',
        'habilitado' => 'boolean',
        'monto'      => 'integer',
        'fecha'      => 'date',
        'anulafecha' => 'datetime',
        'fechains'   => 'datetime',
        'fechamod'   => 'datetime',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'idven_caja', 'idven_caja');
    }

    public function egresoTipo()
    {
        return $this->belongsTo(CajaEgresoTipo::class, 'idven_cajarendicionegreso_tipo', 'idven_cajarendicionegreso_tipo');
    }
}
