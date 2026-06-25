<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CajaEgresoTipo;
use App\Models\CajaMovimiento;
use App\Models\CajaRendicion;
use App\Models\Evento;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Gestión de Caja — modernización del gestion_caja del ERP original.
 *
 * Flujo (cada acción registra un evento de dominio en sys_evento vía Evento::registrar):
 *   abrir → ingreso/egreso (+ ventas vinculadas) → cerrar (genera rendición).
 * El saldo se DERIVA de los movimientos (eventos), no se almacena.
 */
class CajaController extends Controller
{
    public function index()
    {
        $caja = $this->cajaAbierta();

        return Inertia::render('Caja/Index', [
            'caja'        => $caja ? $this->serializarCaja($caja) : null,
            'egresoTipos' => CajaEgresoTipo::where('habilitado', true)->orderBy('descripcion')
                ->get(['idven_cajarendicionegreso_tipo', 'descripcion']),
            'rendiciones' => CajaRendicion::where('habilitado', true)
                ->orderByDesc('idven_cajarendicion')->limit(15)->get(),
        ]);
    }

    public function abrir(Request $request)
    {
        if ($this->cajaAbierta()) {
            return back()->with('error', 'Ya tienes una caja abierta.');
        }

        $data = $request->validate([
            'monto_inicial' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $caja = Caja::create([
                'idusu_usuario'    => auth()->id(),
                'idven_cajaestado' => Caja::ABIERTA,
                'descripcion'      => 'Caja',
                'monto_inicial'    => $data['monto_inicial'],
                'fecha_apertura'   => now(),
                'habilitado'       => true,
            ]);

            $caja->movimientos()->create([
                'idgen_empresa' => $caja->idgen_empresa,
                'tipo'          => 'apertura',
                'descripcion'   => 'Monto apertura de caja',
                'monto'         => $data['monto_inicial'],
                'fecha'         => now()->toDateString(),
            ]);

            Evento::registrar('caja.abierta', 'Caja abierta',
                ['monto_inicial' => $data['monto_inicial']], 'ven_caja', $caja->idven_caja);
        });

        return back()->with('success', 'Caja abierta.');
    }

    public function ingreso(Request $request)
    {
        $caja = $this->cajaAbiertaOrFail();

        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:160'],
            'monto'       => ['required', 'integer', 'min:1'],
        ]);

        $mov = $caja->movimientos()->create([
            'idgen_empresa' => $caja->idgen_empresa,
            'tipo'          => 'ingreso',
            'descripcion'   => $data['descripcion'],
            'monto'         => $data['monto'],
            'fecha'         => now()->toDateString(),
        ]);

        Evento::registrar('caja.ingreso', 'Ingreso de caja: ' . $data['descripcion'],
            ['monto' => $data['monto']], 'ven_cajamovimiento', $mov->idven_cajamovimiento);

        return back()->with('success', 'Ingreso registrado.');
    }

    public function egreso(Request $request)
    {
        $caja = $this->cajaAbiertaOrFail();

        $data = $request->validate([
            'descripcion'                    => ['required', 'string', 'max:160'],
            'monto'                          => ['required', 'integer', 'min:1'],
            'idven_cajarendicionegreso_tipo' => ['required', 'integer'],
        ]);

        $mov = $caja->movimientos()->create([
            'idgen_empresa'                  => $caja->idgen_empresa,
            'tipo'                           => 'egreso',
            'idven_cajarendicionegreso_tipo' => $data['idven_cajarendicionegreso_tipo'],
            'descripcion'                    => $data['descripcion'],
            'monto'                          => $data['monto'],
            'fecha'                          => now()->toDateString(),
        ]);

        Evento::registrar('caja.egreso', 'Egreso de caja: ' . $data['descripcion'],
            ['monto' => $data['monto']], 'ven_cajamovimiento', $mov->idven_cajamovimiento);

        return back()->with('success', 'Egreso registrado.');
    }

    public function anular(Request $request, CajaMovimiento $movimiento)
    {
        if ($movimiento->tipo === 'apertura') {
            return back()->with('error', 'No se puede anular el movimiento de apertura.');
        }
        if ($movimiento->anulado) {
            return back()->with('error', 'El movimiento ya está anulado.');
        }

        $data = $request->validate([
            'anulamotivo' => ['required', 'string', 'max:200'],
        ]);

        $movimiento->update([
            'anulado'     => true,
            'anulamotivo' => $data['anulamotivo'],
            'anulauser'   => auth()->user()->username ?? auth()->user()->email,
            'anulafecha'  => now(),
        ]);

        Evento::registrar('caja.anulacion', 'Movimiento anulado: ' . $movimiento->descripcion,
            ['motivo' => $data['anulamotivo'], 'monto' => $movimiento->monto],
            'ven_cajamovimiento', $movimiento->idven_cajamovimiento);

        return back()->with('success', 'Movimiento anulado.');
    }

    public function cerrar(Request $request)
    {
        $caja = $this->cajaAbiertaOrFail();

        $rendicion = DB::transaction(function () use ($caja) {
            $numero = (CajaRendicion::max('rendicion') ?? 0) + 1;

            $rendicion = CajaRendicion::create([
                'idven_caja'       => $caja->idven_caja,
                'idusu_usuario'    => $caja->idusu_usuario,
                'rendicion'        => $numero,
                'fecha'            => now()->toDateString(),
                'apertura_fecha'   => $caja->fecha_apertura,
                'apertura_usuario' => auth()->user()->username ?? auth()->user()->email,
                'cierre_fecha'     => now(),
                'cierre_usuario'   => auth()->user()->username ?? auth()->user()->email,
                'monto_inicial'    => $caja->monto_inicial,
                'total_ingresos'   => $caja->totalIngresos(),
                'total_egresos'    => $caja->totalEgresos(),
                'total_ventas'     => $caja->totalVentas(),
                'saldo_final'      => $caja->saldo(),
                'habilitado'       => true,
            ]);

            $caja->update([
                'idven_cajaestado' => Caja::CERRADA,
                'idusu_usuario'    => null,
                'fecha_cierre'     => now(),
            ]);

            Evento::registrar('caja.cerrada', "Caja cerrada (rendición #$numero)",
                ['saldo_final' => $rendicion->saldo_final, 'rendicion' => $numero],
                'ven_cajarendicion', $rendicion->idven_cajarendicion);

            return $rendicion;
        });

        return redirect()->route('caja.rendicion', $rendicion->idven_cajarendicion)
            ->with('success', "Caja cerrada. Rendición #{$rendicion->rendicion} generada.");
    }

    public function rendicion(CajaRendicion $rendicion)
    {
        $movimientos = CajaMovimiento::with('egresoTipo')
            ->where('idven_caja', $rendicion->idven_caja)
            ->orderBy('idven_cajamovimiento')
            ->get();

        $ventas = Venta::where('idven_caja', $rendicion->idven_caja)
            ->where('habilitado', true)
            ->orderByDesc('idven_venta')
            ->get(['idven_venta', 'numero', 'total', 'fecha']);

        return Inertia::render('Caja/Rendicion', [
            'rendicion'   => $rendicion,
            'movimientos' => $movimientos,
            'ventas'      => $ventas,
        ]);
    }

    // -------------------------------------------------------------------------

    private function cajaAbierta(): ?Caja
    {
        return Caja::where('idusu_usuario', auth()->id())
            ->where('idven_cajaestado', Caja::ABIERTA)
            ->where('habilitado', true)
            ->first();
    }

    private function cajaAbiertaOrFail(): Caja
    {
        $caja = $this->cajaAbierta();
        abort_unless($caja, 422, 'No tienes una caja abierta.');

        return $caja;
    }

    private function serializarCaja(Caja $caja): array
    {
        $movimientos = $caja->movimientos()->with('egresoTipo')
            ->orderByDesc('idven_cajamovimiento')->get();

        $ventas = $caja->ventas()->where('habilitado', true)
            ->orderByDesc('idven_venta')->get(['idven_venta', 'numero', 'total', 'fecha']);

        return [
            'idven_caja'     => $caja->idven_caja,
            'descripcion'    => $caja->descripcion,
            'monto_inicial'  => $caja->monto_inicial,
            'fecha_apertura' => $caja->fecha_apertura,
            'movimientos'    => $movimientos,
            'ventas'         => $ventas,
            'totales'        => [
                'monto_inicial' => $caja->monto_inicial,
                'ingresos'      => $caja->totalIngresos(),
                'egresos'       => $caja->totalEgresos(),
                'ventas'        => $caja->totalVentas(),
                'saldo'         => $caja->saldo(),
            ],
        ];
    }
}
