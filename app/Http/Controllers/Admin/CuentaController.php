<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camion;
use App\Models\Cuenta;
use App\Models\User;
use App\Models\Viaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * El panel del administrador: las cuentas de los clientes del sistema. No
 * hay registro público, así que acá se crea cada cuenta con su primer
 * usuario, y se le pasan los datos para entrar.
 *
 * Desde acá no se ven ni se tocan los datos de una cuenta (viajes, gastos):
 * sólo cuántos tiene, para saber si la está usando.
 */
class CuentaController extends Controller
{
    public function index()
    {
        $cuentas = Cuenta::withCount('usuarios')->orderByDesc('activa')->orderBy('nombre')->get();

        // Cuánto usa cada una, contado de todas las cuentas a la vez.
        $camiones = Camion::withoutGlobalScope('cuenta')->selectRaw('cuenta_id, count(*) as total')
            ->groupBy('cuenta_id')->pluck('total', 'cuenta_id');
        // "cantidad" y no "total": Viaje castea total como monto.
        $viajes = Viaje::withoutGlobalScope('cuenta')->selectRaw('cuenta_id, count(*) as cantidad, max(fecha) as ultimo')
            ->groupBy('cuenta_id')->get()->keyBy('cuenta_id');

        return view('admin.cuentas.index', compact('cuentas', 'camiones', 'viajes'));
    }

    public function create()
    {
        return view('admin.cuentas.create');
    }

    /** La cuenta nueva, su primer usuario y lo que necesita para arrancar. */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre'   => 'required|string|max:100',
            'notas'    => 'nullable|string|max:1000',
            'patente'  => 'nullable|string|max:20',
            'usuario'  => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'string', Password::min(8)],
            'funciones' => 'nullable|array',
        ]);

        $cuenta = DB::transaction(function () use ($datos) {
            $cuenta = Cuenta::create([
                'nombre'    => $datos['nombre'],
                'notas'     => $datos['notas'] ?? null,
                'activa'    => true,
                'funciones' => Cuenta::funcionesValidas($datos['funciones'] ?? []),
            ]);

            $this->crearUsuario($cuenta, $datos['usuario'], $datos['email'], $datos['password']);
            $cuenta->prepararDatosIniciales($datos['patente'] ?? null);

            return $cuenta;
        });

        return redirect()->route('admin.cuentas.edit', $cuenta)
            ->with('success', 'Cuenta creada.')
            ->with('credenciales', ['email' => $datos['email'], 'password' => $datos['password']]);
    }

    public function edit(Cuenta $cuenta)
    {
        $cuenta->load(['usuarios' => fn ($q) => $q->orderBy('name')]);

        $camiones = Camion::withoutGlobalScope('cuenta')->where('cuenta_id', $cuenta->id)->count();
        $viajes = Viaje::withoutGlobalScope('cuenta')->where('cuenta_id', $cuenta->id);
        $cantidadViajes = (clone $viajes)->count();
        $ultimoViaje = $viajes->max('fecha');

        return view('admin.cuentas.edit', compact('cuenta', 'camiones', 'cantidadViajes', 'ultimoViaje'));
    }

    public function update(Request $request, Cuenta $cuenta)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'notas'  => 'nullable|string|max:1000',
            'funciones' => 'nullable|array',
        ]);
        $datos['activa'] = $request->boolean('activa');
        $datos['funciones'] = Cuenta::funcionesValidas($datos['funciones'] ?? []);

        // Suspender la propia cuenta dejaría al administrador afuera.
        if (! $datos['activa'] && $cuenta->id === $request->user()->cuenta_id) {
            return back()->withErrors(['activa' => 'No podés suspender tu propia cuenta.'])->withInput();
        }

        $cuenta->update($datos);

        return redirect()->route('admin.cuentas.edit', $cuenta)->with('success', $cuenta->activa
            ? 'Cuenta actualizada.'
            : 'Cuenta suspendida: sus usuarios ya no pueden entrar. No se borró nada.');
    }

    /** Otro usuario en la misma cuenta: ve y carga lo mismo. */
    public function agregarUsuario(Request $request, Cuenta $cuenta)
    {
        $datos = $request->validateWithBag('usuarioNuevo', [
            'usuario'  => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $this->crearUsuario($cuenta, $datos['usuario'], $datos['email'], $datos['password']);

        return redirect()->route('admin.cuentas.edit', $cuenta)
            ->with('success', 'Usuario agregado.')
            ->with('credenciales', ['email' => $datos['email'], 'password' => $datos['password']]);
    }

    /** Para cuando alguien se olvida la contraseña: el mail de recuperación todavía no sale. */
    public function cambiarContrasena(Request $request, User $usuario)
    {
        $datos = $request->validateWithBag('contrasena' . $usuario->id, [
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $usuario->update(['password' => $datos['password']]);

        return redirect()->route('admin.cuentas.edit', $usuario->cuenta_id)
            ->with('success', "Contraseña de {$usuario->name} cambiada.")
            ->with('credenciales', ['email' => $usuario->email, 'password' => $datos['password']]);
    }

    /** La cuenta no se asigna en masa (User::$fillable): va aparte, a propósito. */
    private function crearUsuario(Cuenta $cuenta, string $nombre, string $email, string $password): User
    {
        $usuario = new User(['name' => $nombre, 'email' => $email, 'password' => $password]);
        $usuario->cuenta_id = $cuenta->id;
        $usuario->email_verified_at = now();
        $usuario->save();

        return $usuario;
    }
}
