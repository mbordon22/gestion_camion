<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chofer extends Model
{
    public $timestamps = false;

    /** 'chofer' pluraliza como 'chofers' en inglés; la tabla es 'choferes'. */
    protected $table = 'choferes';

    protected $fillable = [
        'nombre', 'dni', 'telefono', 'notas', 'activo', 'created_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function viajes()
    {
        return $this->hasMany(Viaje::class);
    }

    /** "12345678" o "12.345.678" -> "12.345.678". */
    public static function formatearDni(?string $dni): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $dni);

        if ($digitos === '') {
            return null;
        }

        return number_format((int) $digitos, 0, ',', '.');
    }
}
