<?php

namespace App\Models;

/** Tipo A — Cliente (gen_cliente). */
class Cliente extends MaestroModel
{
    protected $table = 'gen_cliente';
    protected $primaryKey = 'idgen_cliente';

    protected $fillable = [
        'idgen_empresa', 'idgen_comuna', 'rut', 'razon_social',
        'direccion', 'email', 'telefono', 'habilitado',
    ];

    public function comuna()
    {
        return $this->belongsTo(Comuna::class, 'idgen_comuna', 'idgen_comuna');
    }
}
