<?php

namespace App\Http\Middleware;

use App\Support\Demo\RollingDates;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mantiene la demo al día sin cron: la primera request de cada día
 * desplaza las fechas (Cache::add es atómico, así que corre una sola vez).
 */
class KeepDemoFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.demo_rolling_dates')) {
            $key = 'demo:rolling-dates:' . now()->toDateString();

            if (Cache::add($key, true, now()->endOfDay())) {
                try {
                    RollingDates::shift();
                } catch (\Throwable $e) {
                    // La demo nunca debe caerse por esto: se reintenta mañana.
                    Log::warning('No se pudieron desplazar las fechas demo', ['error' => $e->getMessage()]);
                }
            }
        }

        return $next($request);
    }
}
