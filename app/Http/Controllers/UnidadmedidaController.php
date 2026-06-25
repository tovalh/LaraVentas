<?php

namespace App\Http\Controllers;

use App\Models\Unidadmedida;
use App\Models\Unidadmedidatipo;
use App\Support\Maestro\Maestro;

class UnidadmedidaController extends MaestroController
{
    protected function maestro(): Maestro
    {
        $tipos = Maestro::opciones(
            Unidadmedidatipo::where('habilitado', true)->orderBy('descripcion')->get(),
            'idgen_unidadmedidatipo',
            'descripcion'
        );

        return Maestro::make('Unidades de Medida', Unidadmedida::class, '/unidades')
            ->with('tipo')
            ->column('tipo.descripcion', 'Tipo')
            ->column('descripcion', 'Descripción')
            ->column('abreviatura', 'Abreviatura')
            ->filterSelect('idgen_unidadmedidatipo', 'Tipo', $tipos)
            ->filterText('descripcion', 'Descripción')
            ->fieldSwitch('habilitado', 'Habilitado')
            ->fieldSelect('idgen_unidadmedidatipo', 'Tipo', $tipos, col: 12, required: true)
            ->fieldText('descripcion', 'Descripción', col: 7, required: true, unique: true)
            ->fieldText('abreviatura', 'Abreviatura', col: 5, required: true, unique: true);
    }
}
