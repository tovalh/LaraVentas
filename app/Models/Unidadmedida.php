<?php

namespace App\Models;

/** Tipo A — Unidad de medida (gen_unidadmedida). */
class Unidadmedida extends MaestroModel
{
    protected $table = 'gen_unidadmedida';
    protected $primaryKey = 'idgen_unidadmedida';

    protected $fillable = [
        'idgen_empresa', 'idgen_unidadmedidatipo', 'descripcion', 'abreviatura', 'habilitado',
    ];

    public function tipo()
    {
        return $this->belongsTo(Unidadmedidatipo::class, 'idgen_unidadmedidatipo', 'idgen_unidadmedidatipo');
    }
}
