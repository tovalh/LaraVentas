<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Model;

/**
 * Rendición (ven_cajarendicion) — conciliación de cierre con totales congelados.
 */
class CajaRendicion extends Model
{
    use BelongsToEmpresa, HasAuditoria;

    protected $table = 'ven_cajarendicion';
    protected $primaryKey = 'idven_cajarendicion';
    public $timestamps = false;

    protected $fillable = [
        'idgen_empresa', 'idven_caja', 'idusu_usuario', 'rendicion', 'fecha',
        'apertura_fecha', 'apertura_usuario', 'cierre_fecha', 'cierre_usuario',
        'monto_inicial', 'total_ingresos', 'total_egresos', 'total_ventas', 'saldo_final', 'habilitado',
    ];

    protected $casts = [
        'habilitado'     => 'boolean',
        'fecha'          => 'date',
        'apertura_fecha' => 'datetime',
        'cierre_fecha'   => 'datetime',
        'monto_inicial'  => 'integer',
        'total_ingresos' => 'integer',
        'total_egresos'  => 'integer',
        'total_ventas'   => 'integer',
        'saldo_final'    => 'integer',
        'fechains'       => 'datetime',
        'fechamod'       => 'datetime',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'idven_caja', 'idven_caja');
    }
}
