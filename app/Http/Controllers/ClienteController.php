<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Comuna;
use App\Support\Maestro\Maestro;

class ClienteController extends MaestroController
{
    protected function maestro(): Maestro
    {
        $comunas = Maestro::opciones(
            Comuna::where('habilitado', true)->orderBy('descripcion')->get(),
            'idgen_comuna',
            'descripcion'
        );

        return Maestro::make('Clientes', Cliente::class, '/clientes')
            ->with('comuna')
            ->column('rut', 'RUT')
            ->column('razon_social', 'Razón social')
            ->column('comuna.descripcion', 'Comuna')
            ->column('email', 'Email')
            ->filterText('rut', 'RUT')
            ->filterText('razon_social', 'Razón social')
            ->fieldSwitch('habilitado', 'Habilitado')
            ->fieldText('rut', 'RUT', col: 4, unique: true)
            ->fieldText('razon_social', 'Razón social', col: 8, required: true)
            ->fieldText('direccion', 'Dirección', col: 8)
            ->fieldSelect('idgen_comuna', 'Comuna', $comunas, col: 4)
            ->fieldText('email', 'Email', col: 6)
            ->fieldText('telefono', 'Teléfono', col: 6);
    }
}
