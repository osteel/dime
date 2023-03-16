<?php

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Exceptions\FiatAmountException;
use Domain\ValueObjects\Exceptions\QuantityException;
use Domain\ValueObjects\FiatAmount;

it('cannot instantiate a fiat amount', function () {
    expect(fn () => new FiatAmount('foo', FiatCurrency::GBP))
        ->toThrow(QuantityException::class, QuantityException::invalidQuantity('foo')->getMessage());
});

it('can instantiate a fiat amount', function () {
    expect((string) (new FiatAmount('10', FiatCurrency::GBP))->quantity)->toBe('10');
});

it('can instantiate a fiat amount with a zero quantity', function () {
    $amount = (new FiatAmount('10', FiatCurrency::GBP))->zero();

    expect($amount->quantity->isZero())->toBeTrue();
    $this->assertEquals($amount->currency, FiatCurrency::GBP);
});

it('can tell whether a fiat amount is positive', function (string $quantity, bool $result) {
    expect((new FiatAmount($quantity, FiatCurrency::GBP))->isPositive())->toBe($result);
})->with([
    'scenario 1' => ['1', true],
    'scenario 2' => ['-1', false],
    'scenario 3' => ['0', true],
    'scenario 4' => ['-0', true],
]);

it('can tell whether a fiat amount is negative', function (string $quantity, bool $result) {
    expect((new FiatAmount($quantity, FiatCurrency::GBP))->isNegative())->toBe($result);
})->with([
    'scenario 1' => ['1', false],
    'scenario 2' => ['-1', true],
    'scenario 3' => ['0', false],
    'scenario 4' => ['-0', false],
]);

it('can tell whether a fiat amount is zero', function (string $quantity, bool $result) {
    expect((new FiatAmount($quantity, FiatCurrency::GBP))->isZero())->toBe($result);
})->with([
    'scenario 1' => ['0', true],
    'scenario 2' => ['1', false],
    'scenario 3' => ['-1', false],
    'scenario 4' => ['-0', true],
]);

it('can tell whether two quantities are equal', function (string $quantity1, string $quantity2, bool $result) {
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))->isEqualTo($quantity2))->toBe($result);
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))
        ->isEqualTo(new FiatAmount($quantity2, FiatCurrency::GBP)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can tell whether a fiat amount is greater than another one', function (string $quantity1, string $quantity2, bool $result) {
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))->isGreaterThan($quantity2))->toBe($result);
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))
        ->isGreaterThan(new FiatAmount($quantity2, FiatCurrency::GBP)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', false],
    'scenario 2' => ['1', '0', true],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', false],
    'scenario 5' => ['0', '-0', false],
]);

it('can tell whether a fiat amount is greater than or equal to another one', function (string $quantity1, string $quantity2, bool $result) {
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))->isGreaterThanOrEqualTo($quantity2))->toBe($result);
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))
        ->isGreaterThanOrEqualTo(new FiatAmount($quantity2, FiatCurrency::GBP)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', true],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can tell whether a fiat amount is less than another one', function (string $quantity1, string $quantity2, bool $result) {
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))->isLessThan($quantity2))->toBe($result);
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))
        ->isLessThan(new FiatAmount($quantity2, FiatCurrency::GBP)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', false],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', true],
    'scenario 4' => ['-1', '-1', false],
    'scenario 5' => ['0', '-0', false],
]);

it('can tell whether a fiat amount is less than or equal to another one', function (string $quantity1, string $quantity2, bool $result) {
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))->isLessThanOrEqualTo($quantity2))->toBe($result);
    expect((new FiatAmount($quantity1, FiatCurrency::GBP))
        ->isLessThanOrEqualTo(new FiatAmount($quantity2, FiatCurrency::GBP)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', true],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can add a fiat amount to another one', function (string $quantity1, string $quantity2, string $result) {
    expect((string) (new FiatAmount($quantity1, FiatCurrency::GBP))->plus($quantity2)->quantity)->toBe($result);
    expect((string) (new FiatAmount($quantity1, FiatCurrency::GBP))
        ->plus(new FiatAmount($quantity2, FiatCurrency::GBP))->quantity)
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '2'],
    'scenario 2' => ['-1', '-1', '-2'],
    'scenario 3' => ['0', '0', '0'],
    'scenario 4' => ['-0', '-0', '0'],
]);

it('can subtract a fiat amount from another one', function (string $quantity1, string $quantity2, string $result) {
    expect((string) (new FiatAmount($quantity1, FiatCurrency::GBP))->minus($quantity2)->quantity)->toBe($result);
    expect((string) (new FiatAmount($quantity1, FiatCurrency::GBP))
        ->minus(new FiatAmount($quantity2, FiatCurrency::GBP))->quantity)
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '0'],
    'scenario 2' => ['-1', '1', '-2'],
    'scenario 3' => ['-1', '-1', '0'],
    'scenario 4' => ['0', '0', '0'],
    'scenario 5' => ['-0', '-0', '0'],
]);

it('can multiply a fiat amount by another one', function (string $quantity1, string $quantity2, string $result) {
    expect((string) (new FiatAmount($quantity1, FiatCurrency::GBP))->multipliedBy($quantity2)->quantity)->toBe($result);
    expect((string) (new FiatAmount($quantity1, FiatCurrency::GBP))
        ->multipliedBy(new FiatAmount($quantity2, FiatCurrency::GBP))->quantity)
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1'],
    'scenario 2' => ['2', '2', '4'],
    'scenario 3' => ['-1', '2', '-2'],
    'scenario 4' => ['-1', '-1', '1'],
    'scenario 5' => ['0', '0', '0'],
    'scenario 6' => ['-0', '-0', '0'],
]);

it('can divide a fiat amount by another one', function (string $quantity1, string $quantity2, string $result) {
    expect((string) (new FiatAmount($quantity1, FiatCurrency::GBP))->dividedBy($quantity2)->quantity)->toBe($result);
    expect((string) (new FiatAmount($quantity1, FiatCurrency::GBP))
        ->dividedBy(new FiatAmount($quantity2, FiatCurrency::GBP))->quantity)
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1'],
    'scenario 2' => ['2', '2', '1'],
    'scenario 3' => ['-2', '2', '-1'],
    'scenario 4' => ['-1', '-1', '1'],
]);

it('cannot perform a fiat amount operation because the currencies do not match', function (string $operation) {
    expect(fn () => (new FiatAmount('10', FiatCurrency::GBP))->$operation(new FiatAmount('10', FiatCurrency::EUR)))->toThrow(
        FiatAmountException::class,
        FiatAmountException::fiatCurrenciesDoNotMatch(FiatCurrency::GBP->value, FiatCurrency::EUR->value)->getMessage(),
    );
})->with([
    'isEqualTo',
    'isGreaterThan',
    'isGreaterThanOrEqualTo',
    'isLessThan',
    'isLessThanOrEqualTo',
    'plus',
    'minus',
    'multipliedBy',
    'dividedBy',
]);

it('can express a fiat amount as a string', function () {
    expect((string) (new FiatAmount('10', FiatCurrency::GBP)))->toBe('£10');
});
