<?php

namespace App\Http\Controllers;

use App\Models\CajaEgresoTipo;
use App\Support\Maestro\Maestro;

class CajaEgresoTipoController extends MaestroController
{
    protected function maestro(): Maestro
    {
        return Maestro::make('Tipos de Egreso', CajaEgresoTipo::class, '/caja-egreso-tipos')
            ->column('descripcion', 'Descripción')
            ->filterText('descripcion', 'Descripción')
            ->fieldSwitch('habilitado', 'Habilitado')
            ->fieldText('descripcion', 'Descripción', col: 12, required: true, unique: true);
    }
}
