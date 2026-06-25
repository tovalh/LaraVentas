<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tipo B — Detalle de venta (ven_venta_detalle). Hereda la empresa por la cabecera. */
class VentaDetalle extends Model
{
    protected $table = 'ven_venta_detalle';
    protected $primaryKey = 'idven_venta_detalle';
    public $timestamps = false;

    protected $fillable = [
        'idven_venta', 'idgen_producto', 'descripcion', 'cantidad', 'precio', 'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio'   => 'integer',
        'subtotal' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'idgen_producto', 'idgen_producto');
    }
}
