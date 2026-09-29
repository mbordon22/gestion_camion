<?php

namespace App\Models;

use App\Models\Concerns\DeLaCuenta;
use Illuminate\Database\Eloquent\Model;

class Chofer extends Model
{
    use DeLaCuenta;

    public $timestamps = false;

    /** 'chofer' pluraliza como 'chofers' en inglés; la tabla es 'choferes'. */
    protected $table = 'choferes';

    protected $fillable = [
        'nombre', 'dni', 'telefono', 'modalidad', 'valor', 'notas', 'activo', 'created_at',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Cómo cobra el chofer por viaje. Sin modalidad no cobra por viaje
     * (maneja el dueño, o tiene sueldo) y sus viajes no descuentan nada.
     */
    public static array $modalidades = [
        'porcentaje' => 'Porcentaje de cada viaje',
        'fijo_viaje' => 'Monto fijo por viaje',
    ];

    public function viajes()
    {
        return $this->hasMany(Viaje::class);
    }

    public function movimientos()
    {
        return $this->hasMany(ChoferMovimiento::class);
    }

    public function liquidaciones()
    {
        return $this->hasMany(Liquidacion::class);
    }

    public function cobraPorViaje(): bool
    {
        return $this->modalidad !== null;
    }

    public function esPorcentaje(): bool
    {
        return $this->modalidad === 'porcentaje';
    }

    /** "15% de cada viaje", "$ 20.000 por viaje" o "Sin comisión". */
    public function condicion(): string
    {
        if (! $this->cobraPorViaje()) {
            return 'Sin comisión';
        }

        return $this->esPorcentaje()
            ? Equipo::porcentajeFormateado($this->valor) . '% de cada viaje'
            : '$ ' . number_format((float) $this->valor, 0, ',', '.') . ' por viaje';
    }

    /**
     * La comisión de un viaje con este total, sobre el bruto.
     *
     * Si el viaje ya era de este chofer se respeta lo que se grabó al
     * cargarlo, como con el alquiler del equipo (Equipo::alquilerDe). Y si
     * además ya se le liquidó, no se toca nada: lo que se le pagó, se le pagó.
     */
    public function comisionDe(float $total, ?Viaje $anterior = null): array
    {
        $mismoChofer = $anterior && $anterior->chofer_id === $this->id;

        if ($mismoChofer && $anterior->liquidacion_id) {
            return [
                'comision_porcentaje' => $anterior->comision_porcentaje,
                'comision_monto'      => $anterior->comision_monto,
            ];
        }

        if (! $this->cobraPorViaje()) {
            return ['comision_porcentaje' => null, 'comision_monto' => null];
        }

        if ($this->esPorcentaje()) {
            $porcentaje = $mismoChofer && $anterior->comision_porcentaje !== null
                ? (float) $anterior->comision_porcentaje
                : (float) $this->valor;

            return [
                'comision_porcentaje' => $porcentaje,
                'comision_monto'      => round($total * $porcentaje / 100, 2),
            ];
        }

        $monto = $mismoChofer && $anterior->comision_monto !== null
            ? (float) $anterior->comision_monto
            : (float) $this->valor;

        return ['comision_porcentaje' => null, 'comision_monto' => $monto];
    }

    /**
     * El teléfono listo para un enlace de WhatsApp: "381 526-1730" ->
     * "5493815261730". Da por hecho que es un celular argentino con el código
     * de área y sin el 15, que es como se agenda casi siempre.
     */
    public function whatsapp(): ?string
    {
        $digitos = ltrim(preg_replace('/\D/', '', (string) $this->telefono), '0');

        if (strlen($digitos) < 10) {
            return null;
        }

        return str_starts_with($digitos, '54') ? $digitos : '549' . $digitos;
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
