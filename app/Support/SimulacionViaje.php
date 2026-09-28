<?php

namespace App\Support;

use App\Models\Chofer;
use App\Models\Equipo;

/**
 * La cuenta de un viaje antes de hacerlo: cuánto entra, cuánto se va y
 * cuánto queda. No guarda nada; es para decidir si un flete conviene y a
 * cuánto cotizarlo.
 *
 * Hay dos clases de gasto:
 *  - los que son un porcentaje de lo que se cobra (el alquiler del equipo o
 *    la comisión del chofer cuando van a porcentaje): crecen con el precio;
 *  - los fijos del viaje (combustible, peajes, comida, desgaste y los
 *    acuerdos por monto fijo): son los mismos cobre lo que cobre.
 * Esa separación es la que permite calcular el precio mínimo: con más
 * precio suben los primeros, los segundos no.
 */
class SimulacionViaje
{
    /** Margen desde el cual el viaje conviene, y por debajo del cual no. */
    public const MARGEN_BUENO = 20;
    public const MARGEN_JUSTO = 5;

    public function __construct(
        public readonly float $km = 0,
        public readonly bool $vuelveVacio = true,
        public readonly string $modo = 'cantidad',
        public readonly ?float $cantidad = null,
        public readonly ?string $unidad = null,
        public readonly ?float $precioUnitario = null,
        public readonly ?float $totalFijo = null,
        public readonly ?float $consumo = null,
        public readonly ?float $precioLitro = null,
        public readonly ?Equipo $equipo = null,
        public readonly ?Chofer $chofer = null,
        public readonly float $peajes = 0,
        public readonly float $viaticos = 0,
        public readonly float $otros = 0,
        public readonly float $costoPorKm = 0,
    ) {}

    /** Los km que hace el camión: ida, y la vuelta si vuelve vacío. */
    public function kmTotales(): float
    {
        return $this->km * ($this->vuelveVacio ? 2 : 1);
    }

    public function porCantidad(): bool
    {
        return $this->modo === 'cantidad';
    }

    /** Lo que se cobra por el viaje. */
    public function ingreso(): float
    {
        if ($this->porCantidad()) {
            return round(($this->cantidad ?? 0) * ($this->precioUnitario ?? 0), 2);
        }

        return round($this->totalFijo ?? 0, 2);
    }

    /** "28 toneladas × $ 9.094,25", o null con precio cerrado. */
    public function detalleIngreso(): ?string
    {
        if (! $this->porCantidad() || ! $this->cantidad || ! $this->precioUnitario) {
            return null;
        }

        return Numero::texto($this->cantidad) . ' ' . ($this->unidad ?? '') . ' × $ ' . Numero::texto($this->precioUnitario);
    }

    /** "toneladas" -> "tonelada", para leer "$ 9.094,25 por tonelada". */
    public function unidadSingular(): string
    {
        $unidad = mb_strtolower((string) $this->unidad);

        return match ($unidad) {
            ''           => 'unidad',
            'kilogramos' => 'kilo',
            default      => preg_replace('/(?<=[^s])s$/u', '', $unidad),
        };
    }

    public function litros(): float
    {
        return round($this->kmTotales() * ($this->consumo ?? 0) / 100, 1);
    }

    public function combustible(): float
    {
        return round($this->litros() * ($this->precioLitro ?? 0), 2);
    }

    /** El equipo sólo cuesta si es alquilado; uno propio no se lleva nada. */
    public function alquiler(): float
    {
        return $this->parteDe($this->equipo?->alquilado ? $this->equipo : null);
    }

    public function comision(): float
    {
        return $this->parteDe($this->chofer?->cobraPorViaje() ? $this->chofer : null);
    }

    public function desgaste(): float
    {
        return round($this->kmTotales() * $this->costoPorKm, 2);
    }

    /**
     * Cada gasto con su detalle, en el orden en que se muestran. Los que dan
     * cero no aparecen.
     *
     * @return array<int, array{concepto: string, detalle: ?string, monto: float}>
     */
    public function gastos(): array
    {
        $km = number_format($this->kmTotales(), 0, ',', '.') . ' km';

        $gastos = [
            [
                'concepto' => 'Combustible',
                'detalle'  => $this->combustible() > 0
                    ? $km . ' · ' . Numero::texto($this->litros()) . ' L a $ ' . Numero::texto($this->precioLitro)
                    : null,
                'monto'    => $this->combustible(),
            ],
            ['concepto' => 'Alquiler del equipo', 'detalle' => $this->equipo?->alquilado ? $this->equipo->nombre . ' · ' . $this->equipo->condicion() : null, 'monto' => $this->alquiler()],
            ['concepto' => 'Chofer', 'detalle' => $this->chofer?->cobraPorViaje() ? $this->chofer->nombre . ' · ' . $this->chofer->condicion() : null, 'monto' => $this->comision()],
            ['concepto' => 'Peajes', 'detalle' => null, 'monto' => round($this->peajes, 2)],
            ['concepto' => 'Comida y viáticos', 'detalle' => null, 'monto' => round($this->viaticos, 2)],
            ['concepto' => 'Otros gastos', 'detalle' => null, 'monto' => round($this->otros, 2)],
            [
                'concepto' => 'Desgaste',
                'detalle'  => $km . ' a $ ' . Numero::texto($this->costoPorKm) . ' el km',
                'monto'    => $this->desgaste(),
            ],
        ];

        return array_values(array_filter($gastos, fn ($gasto) => $gasto['monto'] > 0));
    }

    public function totalGastos(): float
    {
        return round(array_sum(array_column($this->gastos(), 'monto')), 2);
    }

    public function ganancia(): float
    {
        return round($this->ingreso() - $this->totalGastos(), 2);
    }

    /** La ganancia sobre lo cobrado, en %. Sin ingreso no hay margen. */
    public function margen(): ?float
    {
        return $this->ingreso() > 0 ? round($this->ganancia() / $this->ingreso() * 100, 1) : null;
    }

    public function gananciaPorKm(): ?float
    {
        return $this->kmTotales() > 0 ? round($this->ganancia() / $this->kmTotales(), 2) : null;
    }

    /**
     * El veredicto, en palabras y con el color que lo acompaña:
     * conviene (20 % o más), margen chico (5 a 20 %), no conviene (0 a 5 %)
     * o perdés plata.
     *
     * @return array{nivel: string, texto: string}
     */
    public function veredicto(): array
    {
        $margen = $this->margen();

        return match (true) {
            $margen === null               => ['nivel' => 'sin_datos', 'texto' => 'Cargá lo que cobrás para ver si conviene'],
            $this->ganancia() < 0          => ['nivel' => 'pierde', 'texto' => 'Perdés plata'],
            $margen < self::MARGEN_JUSTO   => ['nivel' => 'no_conviene', 'texto' => 'No te conviene'],
            $margen < self::MARGEN_BUENO   => ['nivel' => 'justo', 'texto' => 'Margen chico'],
            default                        => ['nivel' => 'conviene', 'texto' => 'Te conviene'],
        };
    }

    /**
     * Lo que habría que cobrar para ganar ese margen (0 = salir hecho):
     * por unidad si se cobra por cantidad, el total si es precio cerrado.
     * Null si no hay con qué calcularlo, o si con esos porcentajes ningún
     * precio alcanza (el equipo y el chofer ya se llevan eso o más).
     */
    public function precioPara(float $margen): ?float
    {
        $porcentajes = $this->porcentajeDelIngreso();
        $queda = 1 - $porcentajes - $margen / 100;

        if ($queda <= 0) {
            return null;
        }

        $ingreso = $this->gastosFijos() / $queda;

        if (! $this->porCantidad()) {
            return round($ingreso, 2);
        }

        return $this->cantidad > 0 ? round($ingreso / $this->cantidad, 2) : null;
    }

    /** Lo que se lleva alguien según su acuerdo: un % del ingreso o un monto fijo. */
    private function parteDe(Equipo|Chofer|null $quien): float
    {
        if (! $quien) {
            return 0;
        }

        return $quien->esPorcentaje()
            ? round($this->ingreso() * (float) $quien->valor / 100, 2)
            : round((float) $quien->valor, 2);
    }

    /** La parte del ingreso que se llevan los que cobran por porcentaje (0,25 = 25 %). */
    private function porcentajeDelIngreso(): float
    {
        $total = 0;

        foreach ([$this->equipo?->alquilado ? $this->equipo : null, $this->chofer?->cobraPorViaje() ? $this->chofer : null] as $quien) {
            if ($quien?->esPorcentaje()) {
                $total += (float) $quien->valor / 100;
            }
        }

        return $total;
    }

    /** Todo lo que cuesta el viaje sin importar el precio. */
    private function gastosFijos(): float
    {
        $fijos = $this->combustible() + $this->peajes + $this->viaticos + $this->otros + $this->desgaste();

        foreach ([$this->equipo?->alquilado ? $this->equipo : null, $this->chofer?->cobraPorViaje() ? $this->chofer : null] as $quien) {
            if ($quien && ! $quien->esPorcentaje()) {
                $fijos += (float) $quien->valor;
            }
        }

        return $fijos;
    }
}
