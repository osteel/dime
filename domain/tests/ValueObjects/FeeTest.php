<?php

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can tell whether the fee is in fiat', function () {
    expect((new Fee(FiatCurrency::GBP, Quantity::zero(), FiatAmount::GBP('0')))->isFiat())->toBe(true);
    expect((new Fee(new Asset('foo'), Quantity::zero(), FiatAmount::GBP('0')))->isFiat())->toBe(false);
});
