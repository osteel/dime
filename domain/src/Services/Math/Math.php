<?php

namespace Domain\Services\Math;

use Domain\Services\Math\Exceptions\MathException;
use Throwable;

final class Math
{
    private const SCALE = 32;

    private static function withoutTrailingZeros(string $number): string
    {
        return rtrim(rtrim($number, '0'), '.');
    }

    public static function add(string ...$operands): string
    {
        try {
            return self::withoutTrailingZeros(array_reduce(
                array: $operands,
                callback: fn (string $total, string $operand) => bcadd($operand, $total, self::SCALE),
                initial: '0',
            ));
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    public static function sub(string ...$operands): string
    {
        $initial = array_shift($operands);

        try {
            return self::withoutTrailingZeros(array_reduce(
                array: $operands,
                callback: fn (string $total, string $operand) => bcsub($total, $operand, self::SCALE),
                initial: $initial,
            ));
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    public static function mul(string $multiplicand, string $multiplier): string
    {
        try {
            return self::withoutTrailingZeros(bcmul($multiplicand, $multiplier, self::SCALE));
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    public static function div(string $dividend, string $divisor): string
    {
        try {
            return self::withoutTrailingZeros(bcdiv($dividend, $divisor, self::SCALE));
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }
}
