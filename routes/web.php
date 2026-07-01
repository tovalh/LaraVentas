<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CajaEgresoTipoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\FamiliaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UnidadmedidaController;
use App\Http\Controllers\UnidadmedidatipoController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Health de dependencias para el monitor externo: { mysql: "ok" | "down" }.
Route::get('/health/dependencies', function () {
    try {
        DB::connection()->getPdo();
        $mysql = 'ok';
    } catch (\Throwable $e) {
        $mysql = 'down';
    }

    return response()->json(['mysql' => $mysql], $mysql === 'ok' ? 200 : 503);
});

/**
 * Registra de una sola línea las 4 rutas estándar de un maestro (Tipo A):
 * listar, crear, modificar y eliminar (soft-delete). Equivale a "crear el archivo
 * del módulo" en el ERP original: con declararlo, ya tiene todo el CRUD.
 */
if (!function_exists('maestro')) {
    function maestro(string $url, string $controller, string $name): void
    {
        Route::get($url, [$controller, 'index'])->name("$name.index");
        Route::post($url, [$controller, 'store'])->name("$name.store");
        Route::put("$url/{id}", [$controller, 'update'])->name("$name.update");
        Route::delete($url, [$controller, 'destroy'])->name("$name.destroy");
    }
}

// --- Invitados ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// --- Autenticados ---
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Tipo C — Inicio / Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Notificaciones (API JSON que consume la campanita + el WebSocket Go)
    Route::get('/api/notificaciones', [NotificacionController::class, 'listar'])->name('notificaciones.listar');
    Route::post('/api/notificaciones/marcar-leidas', [NotificacionController::class, 'marcarLeidas'])->name('notificaciones.marcar');
    Route::get('/api/notificaciones/token', [NotificacionController::class, 'token'])->name('notificaciones.token');

    // Tipo A — Maestros (CRUD genérico)
    maestro('/productos',    ProductoController::class,         'productos');
    maestro('/familias',     FamiliaController::class,          'familias');
    maestro('/unidades',     UnidadmedidaController::class,     'unidades');
    maestro('/unidad-tipos', UnidadmedidatipoController::class, 'unidad-tipos');
    maestro('/clientes',     ClienteController::class,          'clientes');
    maestro('/caja-egreso-tipos', CajaEgresoTipoController::class, 'caja-egreso-tipos');

    // Tipo B — Maestro-detalle (Ventas)
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::delete('/ventas/{venta}', [VentaController::class, 'destroy'])->name('ventas.destroy');

    // Caja (gestión + rendición) — cada acción registra un evento de dominio
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('/caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
    Route::post('/caja/ingreso', [CajaController::class, 'ingreso'])->name('caja.ingreso');
    Route::post('/caja/egreso', [CajaController::class, 'egreso'])->name('caja.egreso');
    Route::post('/caja/movimiento/{movimiento}/anular', [CajaController::class, 'anular'])->name('caja.anular');
    Route::post('/caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
    Route::get('/caja/rendicion/{rendicion}', [CajaController::class, 'rendicion'])->name('caja.rendicion');

    // Log de eventos de dominio (timeline)
    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
});
