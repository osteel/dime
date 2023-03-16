<?php

use Domain\ValueObjects\Exceptions\QuantityException;
use Domain\ValueObjects\Quantity;

it('cannot instantiate a quantity', function (string $quantity) {
    new Quantity($quantity);
})->with([
    'not a number' => 'foo',
    'mixed number and string 1' => '12345foo',
    'mixed number and string 2' => 'foo12345',
    'invalid format' => '.01234',
    'invalid sign' => '~1234',
])->throws(QuantityException::class);

it('can instantiate a quantity', function (string $quantity) {
    expect((new Quantity($quantity))->quantity)->toBe($quantity);
})->with([
    '1',
    '0',
    '-1',
    '-0',
    '1.2345',
    '-1.2345',
    '-0.000000000000012345',
]);

it('can instantiate a zero quantity', function () {
    expect(Quantity::zero()->quantity)->toBe('0');
});

it('can return the greatest value', function (string $quantity1, string $quantity2, string $result) {
    expect(Quantity::maximum(new Quantity($quantity1), new Quantity($quantity2))->quantity)->toBe($result);
})->with([
    'scenario 1' => ['1', '2', '2'],
    'scenario 2' => ['2', '1', '2'],
    'scenario 3' => ['1', '1', '1'],
    'scenario 4' => ['1', '-2', '1'],
]);

it('can return the lowest value', function (string $quantity1, string $quantity2, string $result) {
    expect(Quantity::minimum(new Quantity($quantity1), new Quantity($quantity2))->quantity)->toBe($result);
})->with([
    'scenario 1' => ['1', '2', '1'],
    'scenario 2' => ['2', '1', '1'],
    'scenario 3' => ['1', '1', '1'],
    'scenario 4' => ['1', '-2', '-2'],
]);

it('can copy a quantity', function (string $quantity) {
    $quantity1 = new Quantity($quantity);
    $quantity2 = $quantity1->copy();

    expect($quantity2->quantity)->toBe($quantity1->quantity);
    expect($quantity2)->not->toBe($quantity1);
})->with(['1', '-1']);

it('can tell whether a quantity is positive', function (string $quantity, bool $result) {
    expect((new Quantity($quantity))->isPositive())->toBe($result);
})->with([
    'scenario 1' => ['1', true],
    'scenario 2' => ['-1', false],
    'scenario 3' => ['0', true],
    'scenario 4' => ['-0', true],
]);

it('can tell whether a quantity is negative', function (string $quantity, bool $result) {
    expect((new Quantity($quantity))->isNegative())->toBe($result);
})->with([
    'scenario 1' => ['1', false],
    'scenario 2' => ['-1', true],
    'scenario 3' => ['0', false],
    'scenario 4' => ['-0', false],
]);

it('can tell whether a quantity is zero', function (string $quantity, bool $result) {
    expect((new Quantity($quantity))->isZero())->toBe($result);
})->with([
    'scenario 1' => ['0', true],
    'scenario 2' => ['1', false],
    'scenario 3' => ['-1', false],
    'scenario 4' => ['-0', true],
]);

it('can tell whether two quantities are equal', function (string $quantity1, string $quantity2, bool $result) {
    expect((new Quantity($quantity1))->isEqualTo($quantity2))->toBe($result);
    expect((new Quantity($quantity1))->isEqualTo(new Quantity($quantity2)))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can tell whether a quantity is greater than another one', function (string $quantity1, string $quantity2, bool $result) {
    expect((new Quantity($quantity1))->isGreaterThan($quantity2))->toBe($result);
    expect((new Quantity($quantity1))->isGreaterThan(new Quantity($quantity2)))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', false],
    'scenario 2' => ['1', '0', true],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', false],
    'scenario 5' => ['0', '-0', false],
]);

it('can tell whether a quantity is greater than or equal to another one', function (string $quantity1, string $quantity2, bool $result) {
    expect((new Quantity($quantity1))->isGreaterThanOrEqualTo($quantity2))->toBe($result);
    expect((new Quantity($quantity1))->isGreaterThanOrEqualTo(new Quantity($quantity2)))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', true],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can tell whether a quantity is less than another one', function (string $quantity1, string $quantity2, bool $result) {
    expect((new Quantity($quantity1))->isLessThan($quantity2))->toBe($result);
    expect((new Quantity($quantity1))->isLessThan(new Quantity($quantity2)))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', false],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', true],
    'scenario 4' => ['-1', '-1', false],
    'scenario 5' => ['0', '-0', false],
]);

it('can tell whether a quantity is less than or equal to another one', function (string $quantity1, string $quantity2, bool $result) {
    expect((new Quantity($quantity1))->isLessThanOrEqualTo($quantity2))->toBe($result);
    expect((new Quantity($quantity1))->isLessThanOrEqualTo(new Quantity($quantity2)))->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', true],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can add a quantity to another one', function (string $quantity1, string $quantity2, string $result) {
    expect((new Quantity($quantity1))->plus($quantity2)->quantity)->toBe($result);
    expect((new Quantity($quantity1))->plus(new Quantity($quantity2))->quantity)->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '2'],
    'scenario 2' => ['-1', '-1', '-2'],
    'scenario 3' => ['0', '0', '0'],
    'scenario 4' => ['-0', '-0', '0'],
]);

it('can subtract a quantity from another one', function (string $quantity1, string $quantity2, string $result) {
    expect((new Quantity($quantity1))->minus($quantity2)->quantity)->toBe($result);
    expect((new Quantity($quantity1))->minus(new Quantity($quantity2))->quantity)->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '0'],
    'scenario 2' => ['-1', '1', '-2'],
    'scenario 3' => ['-1', '-1', '0'],
    'scenario 4' => ['0', '0', '0'],
    'scenario 5' => ['-0', '-0', '0'],
]);

it('can multiply a quantity by another one', function (string $quantity1, string $quantity2, string $result) {
    expect((new Quantity($quantity1))->multipliedBy($quantity2)->quantity)->toBe($result);
    expect((new Quantity($quantity1))->multipliedBy(new Quantity($quantity2))->quantity)->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1'],
    'scenario 2' => ['2', '2', '4'],
    'scenario 3' => ['-1', '2', '-2'],
    'scenario 4' => ['-1', '-1', '1'],
    'scenario 5' => ['0', '0', '0'],
    'scenario 6' => ['-0', '-0', '0'],
]);

it('can divide a quantity by another one', function (string $quantity1, string $quantity2, string $result) {
    expect((new Quantity($quantity1))->dividedBy($quantity2)->quantity)->toBe($result);
    expect((new Quantity($quantity1))->dividedBy(new Quantity($quantity2))->quantity)->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1'],
    'scenario 2' => ['2', '2', '1'],
    'scenario 3' => ['-2', '2', '-1'],
    'scenario 4' => ['-1', '-1', '1'],
]);
