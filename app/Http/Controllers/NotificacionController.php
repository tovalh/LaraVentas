<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * API de notificaciones del usuario en sesión — réplica de
 * `api/v1/notificaciones.php` del ERP original (mismo contrato de datos):
 *   GET  listar         → { status:'ok', count:<no_leidas>, items:[...] }
 *   POST marcar_leidas  → marca leido=1 (todas, o una si viene `id`)
 *   GET  token          → { status:'ok', token, ws_url }  (auth del WebSocket Go)
 */
class NotificacionController extends Controller
{
    /** Página: bandeja completa del usuario (Inertia). */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $items = Notificacion::query()
            ->where('idusu_usuario', $user->id)
            ->where('idgen_empresa', $user->idgen_empresa)
            ->where('habilitado', true)
            ->orderByDesc('idusu_notificacion')
            ->limit(200)
            ->get();

        return Inertia::render('Notificaciones/Index', [
            'notificaciones' => $items->map(fn ($n) => [
                'id'      => $n->idusu_notificacion,
                'titulo'  => $n->titulo,
                'mensaje' => $n->mensaje,
                'url'     => $n->url,
                'leido'   => (bool) $n->leido,
                'fecha'   => optional($n->fechains)->format('d-m-Y H:i:s'),
            ]),
        ]);
    }

    /** Botón "Enviar notificación Live": se crea una notificación a uno mismo
     *  para probar el WebSocket (el Go la empuja y la campana se actualiza). */
    public function enviarDemo(Request $request)
    {
        $user = $request->user();

        Notificacion::add(
            $user->id,
            'Notificación de prueba',
            'Enviada a las ' . now()->format('H:i:s') . ' para probar el WebSocket en vivo.',
            '/notificaciones',
        );

        return back()->with('success', 'Notificación enviada. Mira la campana.');
    }

    /** Listar: badge (no leídas) + últimas 20 (leídas y no leídas). */
    public function listar(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = Notificacion::query()
            ->where('idusu_usuario', $user->id)
            ->where('idgen_empresa', $user->idgen_empresa)
            ->where('habilitado', true)
            ->orderByDesc('idusu_notificacion')
            ->limit(20)
            ->get();

        $count = Notificacion::query()
            ->where('idusu_usuario', $user->id)
            ->where('idgen_empresa', $user->idgen_empresa)
            ->where('habilitado', true)
            ->where('leido', false)
            ->count();

        return response()->json([
            'status' => 'ok',
            'count'  => $count,
            'items'  => $items->map(fn ($n) => [
                'id'      => $n->idusu_notificacion,
                'titulo'  => $n->titulo,
                'mensaje' => $n->mensaje,
                'url'     => $n->url,
                'leido'   => $n->leido ? 1 : 0,
                'fecha'   => optional($n->fechains)->format('d-m-Y H:i'),
            ]),
        ]);
    }

    /** Marcar como leídas (todas las no-leídas, o una sola si viene `id`). */
    public function marcarLeidas(Request $request): JsonResponse
    {
        $user = $request->user();
        $id   = (int) $request->input('id', 0);

        $marcadas = Notificacion::query()
            ->where('idusu_usuario', $user->id)
            ->where('idgen_empresa', $user->idgen_empresa)
            ->where('leido', false)
            ->when($id > 0, fn ($q) => $q->where('idusu_notificacion', $id))
            ->update([
                'leido'       => true,
                'fecha_leido' => now(),
                'usermod'     => $user->username ?? $user->email,
                'fechamod'    => now(),
            ]);

        return response()->json(['status' => 'ok', 'marcadas' => $marcadas]);
    }

    /** Token firmado (HMAC) + URL pública del WebSocket, para que el navegador se conecte al Go. */
    public function token(Request $request): JsonResponse
    {
        $user   = $request->user();
        $secret = config('services.go.ws_secret');
        $wsUrl  = config('services.go.ws_public_url');

        if (empty($secret) || empty($wsUrl)) {
            // Sin Go configurado: el front cae a polling de la API REST.
            return response()->json(['status' => 'ok', 'token' => null, 'ws_url' => null]);
        }

        $payload = json_encode([
            'idusu_usuario' => $user->id,
            'idgen_empresa' => $user->idgen_empresa,
            'exp'           => now()->addHour()->timestamp,
        ]);

        $firma = hash_hmac('sha256', $payload, $secret, true);
        $token = $this->b64url($firma) . '.' . $this->b64url($payload);

        return response()->json([
            'status' => 'ok',
            'token'  => $token,
            'ws_url' => $wsUrl,
        ]);
    }

    /** base64url (alfabeto -_ , sin padding) — compatible con el Go/PHP/JS original. */
    private function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
