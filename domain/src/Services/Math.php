<?php

namespace Domain\Services;

use Domain\Services\Exceptions\MathException;
use Throwable;

final class Math
{
    private const SCALE = 32;

    private static function withoutTrailingZeros(string $number): string
    {
        return rtrim(rtrim($number, '0'), '.');
    }

    /**
     * Add operands and return the result.
     *
     * @throws MathException
     */
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

    /**
     * Subtract operands and return the result.
     *
     * @throws MathException
     */
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

    /**
     * Multiply the multiplicand by the multiplier and return the result.
     *
     * @throws MathException
     */
    public static function mul(string $multiplicand, string $multiplier): string
    {
        try {
            return self::withoutTrailingZeros(bcmul($multiplicand, $multiplier, self::SCALE));
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /**
     * Divide the dividend by the divisor and return the result.
     *
     * @throws MathException
     */
    public static function div(string $dividend, string $divisor): string
    {
        try {
            return self::withoutTrailingZeros(bcdiv($dividend, $divisor, self::SCALE));
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /**
     * Whether the terms are equal.
     *
     * @throws MathException
     */
    public static function eq(string $term1, string $term2): bool
    {
        try {
            $result = bccomp($term1, $term2, self::SCALE);
            return $result === 0;
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /**
     * Whether the first term is greater than (or equal to if the flag is true) the second term.
     *
     * @throws MathException
     */
    public static function gt(string $term1, string $term2, bool $orEqual = false): bool
    {
        try {
            $result = bccomp($term1, $term2, self::SCALE);
            return $orEqual ? $result >= 0 : $result === 1;
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /**
     * Whether the first term is greater than or equal to the second term.
     *
     * @throws MathException
     */
    public static function gte(string $term1, string $term2): bool
    {
        return self::gt($term1, $term2, orEqual: true);
    }

    /**
     * Whether the first term is less than (or equal to if the flag is true) the second term.
     *
     * @throws MathException
     */
    public static function lt(string $term1, string $term2, bool $orEqual = false): bool
    {
        try {
            $result = bccomp($term1, $term2, self::SCALE);
            return $orEqual ? $result <= 0 : $result === -1;
        } catch (Throwable $exception) {
            throw MathException::fromThrowable($exception);
        }
    }

    /**
     * Whether the first term is less than or equal to the second term.
     *
     * @throws MathException
     */
    public static function lte(string $term1, string $term2): bool
    {
        return self::lt($term1, $term2, orEqual: true);
    }

    /**
     * Return the greatest term.
     *
     * @throws MathException
     */
    public static function max(string $term1, string $term2): string
    {
        return self::gt($term1, $term2) ? $term1 : $term2;
    }

    /**
     * Return the smallest term.
     *
     * @throws MathException
     */
    public static function min(string $term1, string $term2): string
    {
        return self::lt($term1, $term2) ? $term1 : $term2;
    }
}
