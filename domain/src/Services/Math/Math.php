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

    /** @throws MathException */
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

    /** @throws MathException */
    public static function sub(string ...$operands): string
    {
        if (empty($initial = array_shift($operands))) {
            return '0';
        }

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

    /** @throws MathException */
    public static function mul(string $multiplicand, string $multiplier): string
    {
        try {
            return self::withoutTrailingZeros(bcmul($multiplicand, $multiplier, self::SCALE));
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /** @throws MathException */
    public static function div(string $dividend, string $divisor): string
    {
        try {
            return self::withoutTrailingZeros(bcdiv($dividend, $divisor, self::SCALE));
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /** @throws MathException */
    public static function gt(string $term1, string $term2, bool $orEqual = false): bool
    {
        try {
            $result = bccomp($term1, $term2, self::SCALE);
            return $orEqual ? $result >= 0 : $result === 1;
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /** @throws MathException */
    public static function gte(string $term1, string $term2): bool
    {
        return self::gt($term1, $term2, orEqual: true);
    }

    /** @throws MathException */
    public static function lt(string $term1, string $term2, bool $orEqual = false): bool
    {
        try {
            $result = bccomp($term1, $term2, self::SCALE);
            return $orEqual ? $result <= 0 : $result === -1;
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /** @throws MathException */
    public static function lte(string $term1, string $term2): bool
    {
        return self::lt($term1, $term2, orEqual: true);
    }

    /** @throws MathException */
    public static function min(string $term1, string $term2): string
    {
        return self::lt($term1, $term2) ? $term1 : $term2;
    }

    /** @throws MathException */
    public static function max(string $term1, string $term2): string
    {
        return self::gt($term1, $term2) ? $term1 : $term2;
    }
}
