<?php

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can tell whether the fee is fiat', function () {
    expect((new Fee(new Asset(FiatCurrency::GBP->value), Quantity::zero(), FiatAmount::GBP('0')))->isFiat())->toBe(true);
    expect((new Fee(new Asset('foo'), Quantity::zero(), FiatAmount::GBP('0')))->isFiat())->toBe(false);
});

it('can return the fee as a string', function () {
    $fee = new Fee(new Asset(FiatCurrency::GBP->value), new Quantity('100'), FiatAmount::GBP('100'));

    expect((string) $fee)->toBe('GBP 100 (market value: £100.00)');
});
