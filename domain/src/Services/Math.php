<?php

namespace Domain\Services;

final class Math
{
    private const SCALE = 32;

    private static function withoutTrailingZeros(string $number): string
    {
        return rtrim(rtrim($number, '0'), '.');
    }

    public static function add(string ...$operands): string
    {
        return self::withoutTrailingZeros(array_reduce(
            array: $operands,
            callback: fn (string $total, string $operand) => bcadd($operand, $total, self::SCALE),
            initial: '0',
        ));
    }
}
