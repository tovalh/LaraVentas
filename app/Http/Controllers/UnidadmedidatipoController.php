<?php

namespace App\Http\Controllers;

use App\Models\Unidadmedidatipo;
use App\Support\Maestro\Maestro;

class UnidadmedidatipoController extends MaestroController
{
    protected function maestro(): Maestro
    {
        return Maestro::make('Tipos de Unidad de Medida', Unidadmedidatipo::class, '/unidad-tipos')
            ->column('descripcion', 'Descripción')
            ->filterText('descripcion', 'Descripción')
            ->fieldSwitch('habilitado', 'Habilitado')
            ->fieldText('descripcion', 'Descripción', col: 12, required: true, unique: true);
    }
}
