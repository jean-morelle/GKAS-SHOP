<?php

namespace App\Support;

/**
 * Calculs et affichage monétaires en précision décimale (bcmath),
 * jamais en nombres flottants.
 */
final class Money
{
    public static function normalize(string|int|float|null $amount): string
    {
        return bcadd((string) ($amount ?? '0'), '0', 2);
    }

    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, 2);
    }

    public static function multiply(string $amount, int $quantity): string
    {
        return bcmul($amount, (string) $quantity, 2);
    }

    /** 8000 → « 8 000 FCFA » */
    public static function format(string|int|float|null $amount, bool $withCurrency = true): string
    {
        $normalized = self::normalize($amount);
        [$int, $dec] = explode('.', $normalized);
        $negative = str_starts_with($int, '-');
        $int = ltrim($int, '-');
        $grouped = strrev(implode(' ', str_split(strrev($int), 3)));
        $result = ($negative ? '-' : '').$grouped.($dec !== '00' ? ','.$dec : '');

        return $withCurrency ? $result.' '.config('gkas.currency', 'FCFA') : $result;
    }
}
