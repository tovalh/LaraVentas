<?php

namespace App\Models;

/** Tipo B — Venta (ven_venta), cabecera. Totales en CLP (enteros). */
class Venta extends MaestroModel
{
    protected $table = 'ven_venta';
    protected $primaryKey = 'idven_venta';

    protected $fillable = [
        'idgen_empresa', 'idgen_cliente', 'numero', 'fecha',
        'neto', 'iva', 'total', 'habilitado',
    ];

    protected $casts = [
        'habilitado' => 'boolean',
        'fecha'      => 'date',
        'fechains'   => 'datetime',
        'fechamod'   => 'datetime',
        'neto'       => 'integer',
        'iva'        => 'integer',
        'total'      => 'integer',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idgen_cliente', 'idgen_cliente');
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class, 'idven_venta', 'idven_venta');
    }
}
