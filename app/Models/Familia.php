<?php

namespace App\Models;

/** Tipo A — Familia de productos (gen_familia). */
class Familia extends MaestroModel
{
    protected $table = 'gen_familia';
    protected $primaryKey = 'idgen_familia';

    protected $fillable = ['idgen_empresa', 'codigo', 'descripcion', 'habilitado'];
}
