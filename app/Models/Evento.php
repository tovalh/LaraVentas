<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Log de eventos de dominio (sys_evento) — capa reusable, append-only.
 *
 * Cualquier módulo registra un evento inmutable con `Evento::registrar()`. A partir
 * del stream de eventos se puede auditar y/o reconstruir estado (event-sourcing
 * ligero). La caja lo usa para apertura/ingreso/egreso/cierre/anulación; las ventas
 * para 'venta.registrada'.
 */
class Evento extends Model
{
    use BelongsToEmpresa;

    protected $table = 'sys_evento';
    protected $primaryKey = 'idsys_evento';
    public $timestamps = false;

    protected $fillable = [
        'idgen_empresa', 'idusu_usuario', 'tipo', 'titulo', 'detalle',
        'referencia_tabla', 'referencia_id', 'userins', 'fechains',
    ];

    protected $casts = [
        'detalle'  => 'array',
        'fechains' => 'datetime',
    ];

    /**
     * Registra un evento de dominio (inmutable).
     *
     * @param  string  $tipo       p.ej. 'caja.abierta', 'caja.ingreso', 'venta.registrada'
     * @param  array   $detalle    datos arbitrarios (se guardan como JSON)
     */
    public static function registrar(
        string $tipo,
        string $titulo,
        array $detalle = [],
        ?string $referenciaTabla = null,
        ?int $referenciaId = null,
    ): self {
        $user = Auth::user();

        return static::create([
            'idusu_usuario'    => $user?->id,
            'tipo'             => $tipo,
            'titulo'           => $titulo,
            'detalle'          => $detalle,
            'referencia_tabla' => $referenciaTabla,
            'referencia_id'    => $referenciaId,
            'userins'          => $user?->username ?? $user?->email ?? 'system',
            'fechains'         => now(),
        ]);
    }
}
