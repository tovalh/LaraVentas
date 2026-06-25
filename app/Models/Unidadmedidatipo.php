<?php

namespace App\Models;

/** Tipo A — Tipo de unidad de medida (gen_unidadmedidatipo). */
class Unidadmedidatipo extends MaestroModel
{
    protected $table = 'gen_unidadmedidatipo';
    protected $primaryKey = 'idgen_unidadmedidatipo';

    protected $fillable = ['idgen_empresa', 'descripcion', 'habilitado'];
}
