<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'cuit', 'telefono', 'notas', 'activo', 'created_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function viajes()
    {
        return $this->hasMany(Viaje::class);
    }

    /** "30123456789" o "30-12345678-9" -> "30-12345678-9". */
    public static function formatearCuit(?string $cuit): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $cuit);

        if ($digitos === '') {
            return null;
        }

        return substr($digitos, 0, 2) . '-' . substr($digitos, 2, 8) . '-' . substr($digitos, 10);
    }
}
