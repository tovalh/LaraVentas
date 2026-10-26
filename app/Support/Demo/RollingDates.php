<?php

namespace App\Support\Demo;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ventana móvil de fechas para la demo pública.
 *
 * La data demo se siembra una sola vez (en el deploy) con fechas relativas
 * a ese día. Sin esto, con el tiempo el dashboard muestra $0 "hoy" y un
 * gráfico plano. shift() corre todas las fechas hacia adelante los días
 * transcurridos desde la venta más reciente, así la demo siempre se ve viva.
 */
class RollingDates
{
    /** Columnas de fecha a desplazar, por tabla. */
    private const COLUMNS = [
        'ven_venta'           => ['fecha', 'fechains', 'fechamod'],
        'ven_caja'            => ['fecha_apertura', 'fecha_cierre'],
        'ven_cajamovimiento'  => ['fecha', 'anulafecha'],
        'ven_cajarendicion'   => ['fecha', 'apertura_fecha', 'cierre_fecha'],
        'usu_notificacion'    => ['fecha_leido', 'fechains', 'fechamod'],
        'sys_evento'          => ['fechains'],
    ];

    /** Desplaza las fechas y devuelve cuántos días se corrieron (0 si ya estaba al día). */
    public static function shift(): int
    {
        // DB::table a propósito: salta el global scope multi-empresa y corre para todas.
        $ultima = DB::table('ven_venta')->max('fecha');
        if ($ultima === null) {
            return 0;
        }

        $dias = (int) Carbon::parse($ultima)->startOfDay()->diffInDays(Carbon::today(), false);
        if ($dias <= 0) {
            return 0;
        }

        DB::transaction(function () use ($dias) {
            foreach (self::COLUMNS as $tabla => $columnas) {
                $cambios = [];
                foreach ($columnas as $col) {
                    $cambios[$col] = DB::raw(self::sumarDias($col, $dias));
                }
                DB::table($tabla)->update($cambios);
            }
        });

        return $dias;
    }

    private static function sumarDias(string $col, int $dias): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "datetime({$col}, '+{$dias} days')"
            : "DATE_ADD({$col}, INTERVAL {$dias} DAY)";
    }
}
