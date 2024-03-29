<?php

use Domain\Services\Math\Exceptions\MathException;
use Domain\Services\Math\Math;

it('can add', function (array $operands, string $result) {
    expect(Math::add(...$operands))->toBe($result);
})->with([
    'scenario 1' => [['1', '1'], '2'],
    'scenario 2' => [['1', '-1'], '0'],
    'scenario 3' => [['1', '1', '1'], '3'],
    'scenario 4' => [['1.11', '1.11'], '2.22'],
    'scenario 5' => [['-1.11', '1.11'], '0'],
    'scenario 6' => [['1.12345678', '1.123456789'], '2.246913569'],
    'scenario 7' => [['1.11111119', '1.11111111'], '2.2222223'],
]);

it('can throw an exception when adding', function () {
    expect(fn () => Math::add('foo'))->toThrow(MathException::class);
});

it('can subtract', function (array $operands, string $result) {
    expect(Math::sub(...$operands))->toBe($result);
})->with([
    'scenario 1' => [['1', '1'], '0'],
    'scenario 2' => [['1', '-1'], '2'],
    'scenario 4' => [['0', '1'], '-1'],
    'scenario 5' => [['1', '1', '1'], '-1'],
    'scenario 6' => [['3.33', '1.11'], '2.22'],
    'scenario 7' => [['-3.33', '1.11'], '-4.44'],
    'scenario 8' => [['2.246913569', '1.123456789'], '1.12345678'],
    'scenario 9' => [['2.2222223', '1.11111111'], '1.11111119'],
    'scenario 10' => [[''], '0'],
    'scenario 11' => [[], '0'],
]);

it('can throw an exception when subtracting', function () {
    expect(fn () => Math::sub('foo', 'bar'))->toThrow(MathException::class);
});

it('can multiply', function (string $multiplicand, string $multiplier, string $result) {
    expect(Math::mul($multiplicand, $multiplier))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1'],
    'scenario 2' => ['1', '-1', '-1'],
    'scenario 3' => ['2', '2', '4'],
    'scenario 4' => ['1.11', '1.11', '1.2321'],
    'scenario 5' => ['-1.11', '1.11', '-1.2321'],
    'scenario 6' => ['0.000000022', '0.1', '0.0000000022'],
    'scenario 7' => ['0.00000000222351', '0.105473', '0.00000000023452027023'],
]);

it('can throw an exception when multiplying', function () {
    expect(fn () => Math::mul('foo', 'bar'))->toThrow(MathException::class);
});

it('can divide', function (string $dividend, string $divisor, string $result) {
    expect(Math::div($dividend, $divisor))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1'],
    'scenario 2' => ['1', '-1', '-1'],
    'scenario 3' => ['2', '1', '2'],
    'scenario 4' => ['2', '2', '1'],
    'scenario 5' => ['0.0000000022', '0.1', '0.000000022'],
    'scenario 6' => ['-0.0000000022', '0.1', '-0.000000022'],
    'scenario 7' => ['0.00000000222351', '0.105473', '0.00000002108131938979644079527462'],
]);

it('can throw an exception when dividing', function () {
    expect(fn () => Math::div('foo', 'bar'))->toThrow(MathException::class);
});

it('can round', function (string $number, int $precision, string $rounded) {
    expect(Math::rnd($number, $precision))->toBe($rounded);
})->with([
    'scenario 1' => ['1', 0, '1'],
    'scenario 2' => ['-1', 0, '-1'],
    'scenario 3' => ['1.111111', 1, '1.1'],
    'scenario 4' => ['1.111111', 4, '1.1111'],
    'scenario 5' => ['-1.111111', 4, '-1.1111'],
    'scenario 6' => ['0.99', 0, '1'],
    'scenario 7' => ['0.09999', 0, '0'],
    'scenario 8' => ['0.09999', 1, '0.1'],
    'scenario 9' => ['0.09999', 2, '0.10'],
    'scenario 10' => ['0.124', 2, '0.12'],
    'scenario 11' => ['0.126', 2, '0.13'],
    'scenario 12' => ['0.125', 2, '0.13'],
]);

it('can return whether it is equal to', function (string $term1, string $term2, bool $result) {
    expect(Math::eq($term1, $term2))->toBe($result);
})->with([
    'scenario 1' => ['1', '2', false],
    'scenario 2' => ['-1', '2', false],
    'scenario 3' => ['1', '1', true],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['2', '1', false],
    'scenario 6' => ['0.000000023', '0.0000000230', true],
    'scenario 7' => ['0.000000023', '0.0000000231', false],
    'scenario 8' => ['0.0000000231', '0.0000000230', false],
]);

it('can return whether it is greater than or equal to', function (string $term1, string $term2, bool $orEqual, bool $result) {
    expect(Math::gt($term1, $term2, $orEqual))->toBe($result);
})->with([
    'scenario 1' => ['1', '2', false, false],
    'scenario 2' => ['1', '1', false, false],
    'scenario 3' => ['1', '1', true, true],
    'scenario 4' => ['2', '1', false, true],
    'scenario 5' => ['2', '1', true, true],
    'scenario 6' => ['-1', '-1', true, true],
    'scenario 7' => ['-1', '-1', false, false],
    'scenario 8' => ['0', '-1', false, true],
    'scenario 9' => ['0.000000023', '0.0000000230', true, true],
    'scenario 10' => ['0.000000023', '0.0000000230', false, false],
    'scenario 11' => ['0.0000000231', '0.0000000230', false, true],
]);

it('can return whether it is less than or equal to', function (string $term1, string $term2, bool $orEqual, bool $result) {
    expect(Math::lt($term1, $term2, $orEqual))->toBe($result);
})->with([
    'scenario 1' => ['2', '1', false, false],
    'scenario 2' => ['1', '1', false, false],
    'scenario 3' => ['1', '1', true, true],
    'scenario 4' => ['1', '2', false, true],
    'scenario 5' => ['1', '2', true, true],
    'scenario 6' => ['-1', '-1', true, true],
    'scenario 7' => ['-1', '-1', false, false],
    'scenario 8' => ['-1', '0', false, true],
    'scenario 9' => ['0.000000023', '0.0000000230', true, true],
    'scenario 10' => ['0.000000023', '0.0000000230', false, false],
    'scenario 11' => ['0.0000000230', '0.0000000231', false, true],
]);

it('can throw an exception when comparing', function (string $method) {
    expect(fn () => Math::$method('foo', 'bar'))->toThrow(MathException::class);
})->with(['eq', 'gt', 'gte', 'lt', 'lte']);

it('can return the smallest number', function (string $term1, string $term2, string $result) {
    expect(Math::min($term1, $term2))->toBe($result);
})->with([
    'scenario 1' => ['2', '1', '1'],
    'scenario 2' => ['1', '2', '1'],
    'scenario 3' => ['1', '1', '1'],
    'scenario 4' => ['-1', '1', '-1'],
    'scenario 5' => ['1', '-1', '-1'],
]);

it('can return the biggest number', function (string $term1, string $term2, string $result) {
    expect(Math::max($term1, $term2))->toBe($result);
})->with([
    'scenario 1' => ['2', '1', '2'],
    'scenario 2' => ['1', '2', '2'],
    'scenario 3' => ['1', '1', '1'],
    'scenario 4' => ['-1', '1', '1'],
    'scenario 5' => ['1', '-1', '1'],
]);
