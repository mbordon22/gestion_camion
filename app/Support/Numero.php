<?php

namespace App\Support;

/**
 * Números escritos a mano, como los escribe cualquiera: "150.000", "27,7",
 * "$ 1.250.000,50". El formulario de viaje, el de camión y el simulador los
 * leen igual; en el navegador rige la misma regla (leerNumero en el JS).
 */
class Numero
{
    /**
     * El texto pasado a lo que entiende la validación.
     *
     * Con coma se lee en criollo: la coma es el decimal y los puntos separan
     * los miles ("1.500,50"). Sin coma, el punto es decimal ("27.7"), salvo
     * que agrupe de a tres cifras ("150.000"), que es como se escribe un
     * monto redondo. Lo que no se pueda leer se deja como vino, para que la
     * validación lo rechace.
     */
    public static function leer(mixed $valor): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        $texto = str_replace(['$', ' '], '', trim($valor));

        if ($texto === '') {
            return null;
        }

        if (str_contains($texto, ',')) {
            return str_replace(['.', ','], ['', '.'], $texto);
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $texto)) {
            return str_replace('.', '', $texto);
        }

        return $texto;
    }

    /**
     * Un número en criollo, sin decimales si es entero: "235.450", "27,7"
     * (28,50 -> "28,5"). Es lo que se muestra y lo que va en los campos de
     * los formularios, porque leer() lo sabe volver a leer.
     */
    public static function texto($numero): string
    {
        if ($numero === null || $numero === '') {
            return '';
        }

        $numero = (float) $numero;

        return fmod($numero, 1) === 0.0
            ? number_format($numero, 0, ',', '.')
            : rtrim(number_format($numero, 2, ',', '.'), '0');
    }

    /** El número, o null si está vacío o no se entiende. */
    public static function aFloat(mixed $valor): ?float
    {
        $leido = self::leer($valor);

        return is_numeric($leido) ? (float) $leido : null;
    }
}
