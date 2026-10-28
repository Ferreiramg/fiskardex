<?php

declare(strict_types=1);

namespace Fiskardex\Domain\Kardex;

/** Fixed precision decimal arithmetic for SPED quantities (8 fractional digits). */
final class Decimal
{
    private const SCALE = 100000000;

    public static function scaled(string $value): int
    {
        $value = str_replace(',', '.', trim($value));
        if (!preg_match('/^([+-]?)(\d+)(?:\.(\d*))?$/', $value, $m)) {
            throw new \InvalidArgumentException('Valor decimal inválido.');
        }
        $fraction = str_pad(substr($m[3] ?? '', 0, 8), 8, '0');
        $whole = (int) $m[2];
        if ($whole > intdiv(PHP_INT_MAX, self::SCALE)) {
            throw new \InvalidArgumentException('Valor decimal fora do limite suportado.');
        }
        $scaled = $whole * self::SCALE + (int) $fraction;
        return ($m[1] ?? '') === '-' ? -$scaled : $scaled;
    }

    public static function format(int $scaled): string
    {
        $sign = $scaled < 0 ? '-' : '';
        $absolute = abs($scaled);
        $fraction = rtrim(sprintf('%08d', $absolute % self::SCALE), '0');
        return $sign . intdiv($absolute, self::SCALE) . ($fraction === '' ? '' : '.' . $fraction);
    }

    public static function multiply(string $left, string $right): string
    {
        $a = self::scaled($left);
        $b = self::scaled($right);
        if ($a !== 0 && abs($b) > intdiv(PHP_INT_MAX, abs($a))) {
            throw new \InvalidArgumentException('Produto decimal fora do limite suportado.');
        }
        return self::format((int) round(($a * $b) / self::SCALE));
    }
}
