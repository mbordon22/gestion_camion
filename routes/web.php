<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\ViajeController;
use App\Http\Controllers\CombustibleController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\MedioPagoController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ProfileController;

Route::get('/', fn() => redirect()->route('inicio'));

Route::middleware('auth')->group(function () {

    Route::get('/inicio', [InicioController::class, 'index'])->name('inicio');

    // Breeze redirige acá tras el login. El nombre de ruta 'dashboard' lo usan
    // los tests de Breeze, así que se conserva apuntando a la home real.
    Route::get('/dashboard', fn() => redirect()->route('inicio'))->name('dashboard');

    Route::resource('camiones', CamionController::class)->except(['show'])
        ->parameters(['camiones' => 'camion']);
    Route::resource('viajes', ViajeController::class)->except(['show']);
    Route::patch('viajes/{viaje}/cobrado', [ViajeController::class, 'toggleCobrado'])->name('viajes.cobrado');
    Route::resource('combustible', CombustibleController::class)->except(['show']);
    Route::resource('mantenimiento', MantenimientoController::class)->except(['show']);

    Route::resource('medios-pago', MedioPagoController::class)->except(['show'])
        ->parameters(['medios-pago' => 'medioPago']);

    Route::resource('prestamos', PrestamoController::class)->except(['show']);
    Route::patch('cuotas/{cuota}/toggle', [PrestamoController::class, 'toggleCuota'])->name('cuotas.toggle');

    Route::get('/pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

    // Perfil (Breeze): editar datos, cambiar contraseña, eliminar cuenta.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
