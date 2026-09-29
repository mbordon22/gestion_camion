<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'es_admin' => 'boolean',
        ];
    }

    /**
     * La cuenta de la que es: sólo ve sus datos. cuenta_id y es_admin no se
     * asignan en masa, así nadie se cambia de cuenta ni se hace admin desde
     * un formulario.
     */
    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class);
    }

    /** El administrador del sistema: crea las cuentas de los clientes. */
    public function esAdmin(): bool
    {
        return (bool) $this->es_admin;
    }
}
