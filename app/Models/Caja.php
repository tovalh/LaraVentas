<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Model;

/**
 * Caja (ven_caja). Estado 1=abierta, 2=cerrada.
 * El saldo NO se almacena: se deriva de los movimientos (eventos) + ventas vinculadas.
 */
class Caja extends Model
{
    use BelongsToEmpresa, HasAuditoria;

    public const ABIERTA = 1;
    public const CERRADA = 2;

    protected $table = 'ven_caja';
    protected $primaryKey = 'idven_caja';
    public $timestamps = false;

    protected $fillable = [
        'idgen_empresa', 'idusu_usuario', 'idven_cajaestado', 'descripcion',
        'monto_inicial', 'fecha_apertura', 'fecha_cierre', 'habilitado',
    ];

    protected $casts = [
        'habilitado'     => 'boolean',
        'monto_inicial'  => 'integer',
        'fecha_apertura' => 'datetime',
        'fecha_cierre'   => 'datetime',
        'fechains'       => 'datetime',
        'fechamod'       => 'datetime',
    ];

    public function movimientos()
    {
        return $this->hasMany(CajaMovimiento::class, 'idven_caja', 'idven_caja');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'idven_caja', 'idven_caja');
    }

    public function estaAbierta(): bool
    {
        return (int) $this->idven_cajaestado === self::ABIERTA;
    }

    // --- Saldo derivado del stream de eventos (movimientos no anulados) + ventas ---

    public function totalIngresos(): int
    {
        return (int) $this->movimientos()->where('anulado', false)
            ->whereIn('tipo', ['apertura', 'ingreso'])->sum('monto');
    }

    public function totalEgresos(): int
    {
        return (int) $this->movimientos()->where('anulado', false)
            ->where('tipo', 'egreso')->sum('monto');
    }

    public function totalVentas(): int
    {
        return (int) $this->ventas()->where('habilitado', true)->sum('total');
    }

    public function saldo(): int
    {
        return $this->totalIngresos() + $this->totalVentas() - $this->totalEgresos();
    }
}
