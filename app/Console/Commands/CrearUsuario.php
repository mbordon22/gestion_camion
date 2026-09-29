<?php

namespace App\Console\Commands;

use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Crear un usuario desde la terminal. Lo normal es hacerlo desde el panel
 * de cuentas; esto queda para arrancar una instalación nueva (el primer
 * administrador) o para cuando no se puede entrar al panel.
 */
class CrearUsuario extends Command
{
    protected $signature = 'usuario:crear {nombre} {email} {password}
                            {--cuenta= : id de una cuenta existente; si no, se crea una nueva a su nombre}
                            {--admin : el usuario administra el sistema (el panel de cuentas)}';

    protected $description = 'Crea un usuario, en su propia cuenta o en una existente (no hay registro web).';

    public function handle(): int
    {
        $datos = [
            'name'     => $this->argument('nombre'),
            'email'    => $this->argument('email'),
            'password' => $this->argument('password'),
            'cuenta'   => $this->option('cuenta'),
        ];

        $validator = Validator::make($datos, [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'cuenta'   => 'nullable|integer|exists:cuentas,id',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($datos) {
            $cuenta = $datos['cuenta']
                ? Cuenta::find($datos['cuenta'])
                : tap(Cuenta::create(['nombre' => $datos['name'], 'activa' => true]))->prepararDatosIniciales();

            // cuenta_id y es_admin no se asignan en masa: van aparte.
            $user = new User(['name' => $datos['name'], 'email' => $datos['email'], 'password' => $datos['password']]);
            $user->cuenta_id = $cuenta->id;
            $user->es_admin = (bool) $this->option('admin');
            $user->email_verified_at = now();
            $user->save();

            return $user;
        });

        $this->info("✓ Usuario creado: {$user->name} <{$user->email}> en la cuenta «{$user->cuenta->nombre}»"
            . ($user->es_admin ? ' (administrador)' : ''));
        $this->line('Ya puede iniciar sesión en /login con ese correo y contraseña.');

        return self::SUCCESS;
    }
}
