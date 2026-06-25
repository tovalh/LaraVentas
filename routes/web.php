<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamiliaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UnidadmedidaController;
use App\Http\Controllers\UnidadmedidatipoController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

/**
 * Registra de una sola línea las 4 rutas estándar de un maestro (Tipo A):
 * listar, crear, modificar y eliminar (soft-delete). Equivale a "crear el archivo
 * del módulo" en el ERP original: con declararlo, ya tiene todo el CRUD.
 */
function maestro(string $url, string $controller, string $name): void
{
    Route::get($url, [$controller, 'index'])->name("$name.index");
    Route::post($url, [$controller, 'store'])->name("$name.store");
    Route::put("$url/{id}", [$controller, 'update'])->name("$name.update");
    Route::delete($url, [$controller, 'destroy'])->name("$name.destroy");
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

    // Tipo B — Maestro-detalle (Ventas)
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::delete('/ventas/{venta}', [VentaController::class, 'destroy'])->name('ventas.destroy');
});
