<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notificación por usuario (usu_notificacion).
 *
 * Réplica de `fc::add_notif()` del ERP original: `Notificacion::add()` inserta la
 * fila y hace un push best-effort al servicio Go (que la empuja por WebSocket).
 * Si GO_NOTIFY_URL no está configurada, el push es no-op y el front igual la ve por
 * la API REST / el polling del Go.
 */
class Notificacion extends Model
{
    protected $table = 'usu_notificacion';
    protected $primaryKey = 'idusu_notificacion';
    public $timestamps = false;

    protected $fillable = [
        'idgen_empresa', 'idusu_usuario', 'titulo', 'mensaje', 'url',
        'leido', 'fecha_leido', 'userins', 'fechains', 'usermod', 'fechamod', 'habilitado',
    ];

    protected $casts = [
        'leido'       => 'boolean',
        'habilitado'  => 'boolean',
        'fecha_leido' => 'datetime',
        'fechains'    => 'datetime',
        'fechamod'    => 'datetime',
    ];

    /**
     * Crea una notificación para un usuario y avisa al servicio Go (best-effort).
     */
    public static function add(int $idusuUsuario, string $titulo, string $mensaje, ?string $url = null): ?self
    {
        $destino = User::find($idusuUsuario);
        if (! $destino) {
            return null;
        }

        $actor = Auth::user()?->username ?? Auth::user()?->email ?? 'system';

        $notif = static::create([
            'idgen_empresa' => $destino->idgen_empresa,
            'idusu_usuario' => $idusuUsuario,
            'titulo'        => $titulo,
            'mensaje'       => $mensaje,
            'url'           => $url,
            'leido'         => false,
            'userins'       => $actor,
            'fechains'      => now(),
            'habilitado'    => true,
        ]);

        $notif->notificarServicioGo();

        return $notif;
    }

    /** POST best-effort al servicio Go para bajar la latencia a ~instantáneo. */
    public function notificarServicioGo(): void
    {
        $url = config('services.go.notify_url');
        if (empty($url)) {
            return; // sin Go configurado: el polling del Go / la API REST igual la entregan
        }

        try {
            Http::timeout(1)->post($url, [
                'id'            => $this->idusu_notificacion,
                'idgen_empresa' => $this->idgen_empresa,
                'idusu_usuario' => $this->idusu_usuario,
                'titulo'        => $this->titulo,
                'mensaje'       => $this->mensaje,
                'url'           => $this->url,
            ]);
        } catch (\Throwable $e) {
            Log::debug('notificarServicioGo falló (no crítico): ' . $e->getMessage());
        }
    }
}
