<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Transfer;

it('can tell whether the transferred asset is a non-fungible asset', function () {
    /** @var Transfer */
    $transaction = Transfer::factory()->nonFungibleAsset()->make();

    expect($transaction->hasNonFungibleAsset())->toBeTrue();
    expect($transaction->hasSharePoolingAsset())->toBeFalse();
});

it('can tell whether the transferred asset is a share pooling asset', function () {
    /** @var Transfer */
    $transaction = Transfer::factory()->make(['asset' => new Asset('BTC')]);

    expect($transaction->hasSharePoolingAsset())->toBeTrue();
    expect($transaction->hasNonFungibleAsset())->toBeFalse();
});

it('can return the transfer as a string', function () {
    /** @var Transfer */
    $transaction = Transfer::factory()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'asset' => new Asset('foo'),
        'quantity' => new Quantity('100'),
        'fee' => new Fee(new Asset(FiatCurrency::GBP->value), new Quantity('1'), FiatAmount::GBP('1')),
    ]);

    expect((string) $transaction)->toBe('2015-10-21 | transferred: FOO | non-fungible asset: no | quantity: 100 | Fee: GBP 1 (market value: £1.00)');
});
