<?php

namespace App\Http\Controllers;

use App\Models\Familia;
use App\Support\Maestro\Maestro;

class FamiliaController extends MaestroController
{
    protected function maestro(): Maestro
    {
        return Maestro::make('Familias', Familia::class, '/familias')
            ->column('codigo', 'Código')
            ->column('descripcion', 'Descripción')
            ->filterText('codigo', 'Código')
            ->filterText('descripcion', 'Descripción')
            ->fieldSwitch('habilitado', 'Habilitado')
            ->fieldText('codigo', 'Código', col: 4, unique: true)
            ->fieldText('descripcion', 'Descripción', col: 8, required: true, unique: true);
    }
}
