<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class CrearUsuario extends Command
{
    protected $signature = 'usuario:crear {nombre} {email} {password}';

    protected $description = 'Crea un usuario para acceder a la app (registro web cerrado / invite-only).';

    public function handle(): int
    {
        $datos = [
            'name'     => $this->argument('nombre'),
            'email'    => $this->argument('email'),
            'password' => $this->argument('password'),
        ];

        $validator = Validator::make($datos, [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $datos['name'],
            'email'    => $datos['email'],
            'password' => Hash::make($datos['password']),
        ]);

        $this->info("✓ Usuario creado: {$user->name} <{$user->email}> (id {$user->id})");
        $this->line('Ya podés iniciar sesión en /login con ese correo y contraseña.');

        return self::SUCCESS;
    }
}
