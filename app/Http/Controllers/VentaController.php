<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Tipo B (maestro-detalle) — Ventas.
 * Cabecera (ven_venta) + líneas (ven_venta_detalle) en una sola transacción.
 * Montos en CLP (enteros); IVA 19%.
 */
class VentaController extends Controller
{
    private const IVA = 0.19;

    public function index(Request $request)
    {
        $perPage = min(500, max(10, (int) $request->input('perPage', 25)));

        return Inertia::render('Ventas/Index', [
            'page' => Venta::with('cliente')
                ->where('habilitado', true)
                ->withCount('detalles')
                ->orderByDesc('idven_venta')
                ->paginate($perPage)
                ->withQueryString(),
            'clientes' => Cliente::where('habilitado', true)->orderBy('razon_social')
                ->get(['idgen_cliente', 'rut', 'razon_social']),
            'productos' => Producto::where('habilitado', true)->orderBy('descripcion')
                ->get(['idgen_producto', 'codigo', 'descripcion', 'precio']),
        ]);
    }

    public function show(Venta $venta)
    {
        $venta->load(['detalles.producto', 'cliente']);

        return Inertia::render('Ventas/Show', ['venta' => $venta]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'idgen_cliente'             => ['nullable', 'integer'],
            'fecha'                     => ['required', 'date'],
            'detalles'                  => ['required', 'array', 'min:1'],
            'detalles.*.idgen_producto' => ['nullable', 'integer'],
            'detalles.*.descripcion'    => ['required', 'string', 'max:160'],
            'detalles.*.cantidad'       => ['required', 'integer', 'min:1'],
            'detalles.*.precio'         => ['required', 'integer', 'min:0'],
        ]);

        $venta = DB::transaction(function () use ($data) {
            $neto  = collect($data['detalles'])->sum(fn ($d) => $d['cantidad'] * $d['precio']);
            $iva   = (int) round($neto * self::IVA);
            $total = $neto + $iva;

            $venta = Venta::create([
                'idgen_cliente' => $data['idgen_cliente'] ?? null,
                'numero'        => $this->siguienteNumero(),
                'fecha'         => $data['fecha'],
                'neto'          => $neto,
                'iva'           => $iva,
                'total'         => $total,
                'habilitado'    => true,
            ]);

            foreach ($data['detalles'] as $d) {
                $venta->detalles()->create([
                    'idgen_producto' => $d['idgen_producto'] ?? null,
                    'descripcion'    => $d['descripcion'],
                    'cantidad'       => $d['cantidad'],
                    'precio'         => $d['precio'],
                    'subtotal'       => $d['cantidad'] * $d['precio'],
                ]);
            }

            return $venta;
        });

        // Disparador de notificación (como en el ERP original: un evento de negocio
        // genera una notificación al usuario). El push al WebSocket Go es best-effort.
        Notificacion::add(
            auth()->id(),
            'Venta registrada',
            "Se registró la venta {$venta->numero} por $" . number_format($venta->total, 0, ',', '.') . '.',
            "/ventas/{$venta->idven_venta}"
        );

        return redirect()->route('ventas.index')->with('success', 'Venta registrada.');
    }

    public function destroy(Venta $venta)
    {
        $venta->update(['habilitado' => false]);

        return back()->with('success', 'Venta anulada.');
    }

    /** Correlativo 001-001-0000001 dentro de la empresa. */
    private function siguienteNumero(): string
    {
        $n = Venta::max('idven_venta') + 1;

        return '001-001-' . str_pad((string) $n, 7, '0', STR_PAD_LEFT);
    }
}
