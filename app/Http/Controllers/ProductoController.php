<?php

namespace App\Http\Controllers;

use App\Models\Familia;
use App\Models\Producto;
use App\Models\Unidadmedida;
use App\Support\Maestro\Maestro;

class ProductoController extends MaestroController
{
    protected function maestro(): Maestro
    {
        $familias = Maestro::opciones(
            Familia::where('habilitado', true)->orderBy('descripcion')->get(),
            'idgen_familia',
            'descripcion'
        );

        $unidades = Maestro::opciones(
            Unidadmedida::where('habilitado', true)->orderBy('descripcion')->get(),
            'idgen_unidadmedida',
            'descripcion'
        );

        return Maestro::make('Productos', Producto::class, '/productos')
            ->with('familia', 'unidadmedida')
            ->column('codigo', 'Código')
            ->column('descripcion', 'Descripción')
            ->column('familia.descripcion', 'Familia')
            ->column('precio', 'Precio', 'clp', 'right')
            ->column('stock', 'Stock', 'entero', 'right')
            ->filterText('codigo', 'Código')
            ->filterText('descripcion', 'Descripción')
            ->filterSelect('idgen_familia', 'Familia', $familias)
            ->fieldSwitch('habilitado', 'Habilitado')
            ->fieldText('codigo', 'Código', col: 4, unique: true)
            ->fieldText('descripcion', 'Descripción', col: 8, required: true)
            ->fieldSelect('idgen_familia', 'Familia', $familias, col: 6)
            ->fieldSelect('idgen_unidadmedida', 'Unidad de Medida', $unidades, col: 6)
            ->fieldNumber('precio', 'Precio', col: 4, required: true, format: 'clp')
            ->fieldNumber('stock', 'Stock', col: 4)
            ->fieldNumber('stock_critico', 'Stock crítico', col: 4);
    }
}
