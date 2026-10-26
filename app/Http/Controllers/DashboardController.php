<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

/**
 * Tipo C (especial) — Inicio / Dashboard.
 * Réplica de inicio.php: grilla de widgets (KPIs + gráfico + accesos). Montos en CLP.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        $ventasHoy = Venta::where('habilitado', true)->whereDate('fecha', $hoy)
            ->selectRaw('COUNT(*) docs, COALESCE(SUM(total),0) monto')->first();

        $ventasMes = (int) Venta::where('habilitado', true)
            ->whereYear('fecha', $hoy->year)->whereMonth('fecha', $hoy->month)
            ->sum('total');

        $stockCritico = Producto::where('habilitado', true)
            ->whereColumn('stock', '<=', 'stock_critico')->count();

        $clientes = Cliente::where('habilitado', true)->count();

        // Gráfico: ventas (total) por día, últimos 14 días.
        $desde = $hoy->copy()->subDays(13);
        $porDia = Venta::where('habilitado', true)
            ->where('fecha', '>=', $desde->toDateString())
            ->groupBy('fecha')
            ->selectRaw('fecha, SUM(total) total')
            ->pluck('total', 'fecha');

        $grafico = [];
        for ($d = $desde->copy(); $d <= $hoy; $d->addDay()) {
            $grafico[] = ['fecha' => $d->format('d/m'), 'total' => (int) ($porDia[$d->toDateString()] ?? 0)];
        }

        // Productos con stock bajo (mini-tabla).
        $stockBajo = Producto::where('habilitado', true)
            ->whereColumn('stock', '<=', 'stock_critico')
            ->orderBy('stock')->limit(5)
            ->get(['idgen_producto', 'codigo', 'descripcion', 'stock', 'stock_critico']);

        $ultimasVentas = Venta::with('cliente')->where('habilitado', true)
            ->orderByDesc('fecha')->orderByDesc('idven_venta')->limit(6)->get();

        return Inertia::render('Dashboard', [
            'kpis' => [
                'ventasHoy'    => ['docs' => (int) $ventasHoy->docs, 'monto' => (int) $ventasHoy->monto],
                'ventasMes'    => $ventasMes,
                'stockCritico' => $stockCritico,
                'clientes'     => $clientes,
            ],
            'grafico'       => $grafico,
            'stockBajo'     => $stockBajo,
            'ultimasVentas' => $ultimasVentas,
        ]);
    }
}
