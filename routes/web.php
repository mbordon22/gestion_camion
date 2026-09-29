<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\ViajeController;
use App\Http\Controllers\CombustibleController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Admin\CuentaController;
use App\Http\Controllers\ChoferController;
use App\Http\Controllers\DestinoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\LiquidacionController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\MedioPagoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SimuladorController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ProfileController;

Route::get('/', fn() => redirect()->route('inicio'));

Route::middleware(['auth', 'cuenta.activa'])->group(function () {

    Route::get('/inicio', [InicioController::class, 'index'])->name('inicio');

    // Breeze redirige acá tras el login. El nombre de ruta 'dashboard' lo usan
    // los tests de Breeze, así que se conserva apuntando a la home real.
    Route::get('/dashboard', fn() => redirect()->route('inicio'))->name('dashboard');

    Route::resource('camiones', CamionController::class)->except(['show'])
        ->parameters(['camiones' => 'camion']);
    Route::resource('clientes', ClienteController::class)->except(['show'])
        ->parameters(['clientes' => 'cliente']);
    Route::get('choferes/{chofer}/liquidacion', [LiquidacionController::class, 'index'])->name('choferes.liquidacion');
    Route::post('choferes/{chofer}/liquidacion', [LiquidacionController::class, 'store'])->name('choferes.liquidacion.store');
    Route::post('choferes/{chofer}/movimientos', [LiquidacionController::class, 'storeMovimiento'])->name('choferes.movimientos.store');
    Route::delete('movimientos-chofer/{movimiento}', [LiquidacionController::class, 'destroyMovimiento'])->name('choferes.movimientos.destroy');
    Route::get('liquidaciones/{liquidacion}', [LiquidacionController::class, 'show'])->name('liquidaciones.show');
    Route::delete('liquidaciones/{liquidacion}', [LiquidacionController::class, 'destroy'])->name('liquidaciones.destroy');
    Route::resource('choferes', ChoferController::class)->except(['show'])
        ->parameters(['choferes' => 'chofer']);
    Route::resource('destinos', DestinoController::class)->except(['show']);
    Route::resource('productos', ProductoController::class)->except(['show']);
    Route::get('equipos/{equipo}/pagos', [EquipoController::class, 'pagos'])->name('equipos.pagos');
    Route::post('equipos/{equipo}/pagos', [EquipoController::class, 'registrarPago'])->name('equipos.pagos.store');
    Route::delete('equipos/{equipo}/pagos/{fecha}', [EquipoController::class, 'deshacerPago'])
        ->where('fecha', '\d{4}-\d{2}-\d{2}')->name('equipos.pagos.destroy');
    Route::resource('equipos', EquipoController::class)->except(['show']);
    Route::get('tarifas/sugerir', [TarifaController::class, 'sugerir'])->name('tarifas.sugerir');
    Route::resource('tarifas', TarifaController::class)->except(['show']);
    Route::get('simulador', [SimuladorController::class, 'index'])->name('simulador.index');
    Route::get('simulador/resultado', [SimuladorController::class, 'resultado'])->name('simulador.resultado');
    Route::get('viajes/buscar-orden', [ViajeController::class, 'buscarPorOrden'])->name('viajes.buscar-orden');
    Route::resource('viajes', ViajeController::class)->except(['show']);
    Route::patch('viajes/{viaje}/cobrado', [ViajeController::class, 'toggleCobrado'])->name('viajes.cobrado');
    Route::resource('combustible', CombustibleController::class)->except(['show']);
    Route::resource('mantenimiento', MantenimientoController::class)->except(['show']);

    Route::resource('medios-pago', MedioPagoController::class)->except(['show'])
        ->parameters(['medios-pago' => 'medioPago']);

    Route::get('/pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

    // Perfil (Breeze): editar datos y cambiar contraseña. Borrarse no: las
    // cuentas y sus usuarios los maneja el administrador.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Panel del administrador del sistema: las cuentas de los clientes.
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('cuentas', [CuentaController::class, 'index'])->name('cuentas.index');
        Route::get('cuentas/nueva', [CuentaController::class, 'create'])->name('cuentas.create');
        Route::post('cuentas', [CuentaController::class, 'store'])->name('cuentas.store');
        Route::get('cuentas/{cuenta}', [CuentaController::class, 'edit'])->name('cuentas.edit');
        Route::put('cuentas/{cuenta}', [CuentaController::class, 'update'])->name('cuentas.update');
        Route::post('cuentas/{cuenta}/usuarios', [CuentaController::class, 'agregarUsuario'])->name('cuentas.usuarios.store');
        Route::put('usuarios/{usuario}/contrasena', [CuentaController::class, 'cambiarContrasena'])->name('usuarios.contrasena');
    });
});

require __DIR__.'/auth.php';
