<?php

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Exceptions\FiatAmountException;
use Domain\ValueObjects\Exceptions\QuantityException;
use Domain\ValueObjects\FiatAmount;

it('cannot instantiate a fiat amount', function () {
    expect(fn () => FiatAmount::GBP('foo'))
        ->toThrow(QuantityException::class, QuantityException::invalidQuantity('foo')->getMessage());
});

it('can instantiate a fiat amount', function () {
    expect((string) FiatAmount::GBP('10')->quantity)->toBe('10');
});

it('can return a fiat amount with a zero quantity', function () {
    $amount = FiatAmount::GBP('10')->zero();

    expect($amount->quantity->isZero())->toBeTrue();
    $this->assertEquals($amount->currency, FiatCurrency::GBP);
});

it('can tell whether a fiat amount is positive', function (string $quantity, bool $result) {
    expect(FiatAmount::GBP($quantity)->isPositive())->toBe($result);
})->with([
    'scenario 1' => ['1', true],
    'scenario 2' => ['-1', false],
    'scenario 3' => ['0', true],
    'scenario 4' => ['-0', true],
]);

it('can tell whether a fiat amount is negative', function (string $quantity, bool $result) {
    expect(FiatAmount::GBP($quantity)->isNegative())->toBe($result);
})->with([
    'scenario 1' => ['1', false],
    'scenario 2' => ['-1', true],
    'scenario 3' => ['0', false],
    'scenario 4' => ['-0', false],
]);

it('can tell whether a fiat amount is zero', function (string $quantity, bool $result) {
    expect(FiatAmount::GBP($quantity)->isZero())->toBe($result);
})->with([
    'scenario 1' => ['0', true],
    'scenario 2' => ['1', false],
    'scenario 3' => ['-1', false],
    'scenario 4' => ['-0', true],
]);

it('can tell whether two fiat amounts are equal', function (string $quantity1, string $quantity2, bool $result) {
    expect(FiatAmount::GBP($quantity1)->isEqualTo($quantity2))->toBe($result);
    expect(FiatAmount::GBP($quantity1)
        ->isEqualTo(FiatAmount::GBP($quantity2)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can tell whether a fiat amount is greater than another one', function (string $quantity1, string $quantity2, bool $result) {
    expect(FiatAmount::GBP($quantity1)->isGreaterThan($quantity2))->toBe($result);
    expect(FiatAmount::GBP($quantity1)
        ->isGreaterThan(FiatAmount::GBP($quantity2)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', false],
    'scenario 2' => ['1', '0', true],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', false],
    'scenario 5' => ['0', '-0', false],
]);

it('can tell whether a fiat amount is greater than or equal to another one', function (string $quantity1, string $quantity2, bool $result) {
    expect(FiatAmount::GBP($quantity1)->isGreaterThanOrEqualTo($quantity2))->toBe($result);
    expect(FiatAmount::GBP($quantity1)
        ->isGreaterThanOrEqualTo(FiatAmount::GBP($quantity2)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', true],
    'scenario 3' => ['0', '1', false],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can tell whether a fiat amount is less than another one', function (string $quantity1, string $quantity2, bool $result) {
    expect(FiatAmount::GBP($quantity1)->isLessThan($quantity2))->toBe($result);
    expect(FiatAmount::GBP($quantity1)
        ->isLessThan(FiatAmount::GBP($quantity2)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', false],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', true],
    'scenario 4' => ['-1', '-1', false],
    'scenario 5' => ['0', '-0', false],
]);

it('can tell whether a fiat amount is less than or equal to another one', function (string $quantity1, string $quantity2, bool $result) {
    expect(FiatAmount::GBP($quantity1)->isLessThanOrEqualTo($quantity2))->toBe($result);
    expect(FiatAmount::GBP($quantity1)
        ->isLessThanOrEqualTo(FiatAmount::GBP($quantity2)))
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', true],
    'scenario 2' => ['1', '0', false],
    'scenario 3' => ['0', '1', true],
    'scenario 4' => ['-1', '-1', true],
    'scenario 5' => ['0', '-0', true],
]);

it('can add a fiat amount to another one', function (string $quantity1, string $quantity2, string $result) {
    expect((string) FiatAmount::GBP($quantity1)->plus($quantity2)->quantity)->toBe($result);
    expect((string) FiatAmount::GBP($quantity1)
        ->plus(FiatAmount::GBP($quantity2))->quantity)
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '2'],
    'scenario 2' => ['-1', '-1', '-2'],
    'scenario 3' => ['0', '0', '0'],
    'scenario 4' => ['-0', '-0', '0'],
]);

it('can subtract a fiat amount from another one', function (string $quantity1, string $quantity2, string $result) {
    expect((string) FiatAmount::GBP($quantity1)->minus($quantity2)->quantity)->toBe($result);
    expect((string) FiatAmount::GBP($quantity1)
        ->minus(FiatAmount::GBP($quantity2))->quantity)
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '0'],
    'scenario 2' => ['-1', '1', '-2'],
    'scenario 3' => ['-1', '-1', '0'],
    'scenario 4' => ['0', '0', '0'],
    'scenario 5' => ['-0', '-0', '0'],
]);

it('can multiply a fiat amount by another one', function (string $quantity1, string $quantity2, string $result) {
    expect((string) FiatAmount::GBP($quantity1)->multipliedBy($quantity2)->quantity)->toBe($result);
    expect((string) FiatAmount::GBP($quantity1)
        ->multipliedBy(FiatAmount::GBP($quantity2))->quantity)
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
    expect((string) FiatAmount::GBP($quantity1)->dividedBy($quantity2)->quantity)->toBe($result);
    expect((string) FiatAmount::GBP($quantity1)
        ->dividedBy(FiatAmount::GBP($quantity2))->quantity)
        ->toBe($result);
})->with([
    'scenario 1' => ['1', '1', '1'],
    'scenario 2' => ['2', '2', '1'],
    'scenario 3' => ['-2', '2', '-1'],
    'scenario 4' => ['-1', '-1', '1'],
]);

it('cannot perform a fiat amount operation because the currencies do not match', function (string $operation) {
    expect(fn () => (FiatAmount::GBP('10'))->{$operation}(new FiatAmount('10', FiatCurrency::EUR)))->toThrow(
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
    expect((string) FiatAmount::GBP('10'))->toBe('£10');
});
