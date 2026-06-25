<?php

namespace App\Models;

/** Tipo A — Tipo de egreso de caja (ven_cajarendicionegreso_tipo). */
class CajaEgresoTipo extends MaestroModel
{
    protected $table = 'ven_cajarendicionegreso_tipo';
    protected $primaryKey = 'idven_cajarendicionegreso_tipo';

    protected $fillable = ['idgen_empresa', 'descripcion', 'habilitado'];
}
