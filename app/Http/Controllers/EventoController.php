<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Log de eventos de dominio (timeline). Lectura de la capa reusable sys_evento.
 */
class EventoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(200, max(10, (int) $request->input('perPage', 30)));
        $tipo    = $request->input('tipo');

        $page = Evento::query()
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->orderByDesc('idsys_evento')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Eventos/Index', [
            'page'   => $page,
            'tipos'  => Evento::query()->distinct()->orderBy('tipo')->pluck('tipo'),
            'filtro' => ['tipo' => $tipo],
        ]);
    }
}
