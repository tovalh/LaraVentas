<?php

namespace App\Models;

/** Tipo A — Producto (gen_producto). Precio/stock en CLP (enteros). */
class Producto extends MaestroModel
{
    protected $table = 'gen_producto';
    protected $primaryKey = 'idgen_producto';

    protected $fillable = [
        'idgen_empresa', 'idgen_familia', 'idgen_unidadmedida',
        'codigo', 'descripcion', 'precio', 'stock', 'stock_critico', 'habilitado',
    ];

    protected $casts = [
        'habilitado'    => 'boolean',
        'fechains'      => 'datetime',
        'fechamod'      => 'datetime',
        'precio'        => 'integer',
        'stock'         => 'integer',
        'stock_critico' => 'integer',
    ];

    public function familia()
    {
        return $this->belongsTo(Familia::class, 'idgen_familia', 'idgen_familia');
    }

    public function unidadmedida()
    {
        return $this->belongsTo(Unidadmedida::class, 'idgen_unidadmedida', 'idgen_unidadmedida');
    }
}
