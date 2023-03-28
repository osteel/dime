<?php

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Exceptions\DisposalException;

it('cannot instantiate a disposal because the asset is fiat', function () {
    expect(fn () => Disposal::factory()->make(['asset' => new Asset(FiatCurrency::GBP->value)]))
        ->toThrow(DisposalException::class);
});

it('can tell whether the disposed of asset is a non-fungible asset', function () {
    /** @var Disposal */
    $transaction = Disposal::factory()->nonFungibleAsset()->make();

    expect($transaction->hasNonFungibleAsset())->toBeTrue();
    expect($transaction->hasSharePoolingAsset())->toBeFalse();
});

it('can tell whether the disposed of asset is a share pooling asset', function () {
    /** @var Disposal */
    $transaction = Disposal::factory()->make(['asset' => new Asset('BTC')]);

    expect($transaction->hasSharePoolingAsset())->toBeTrue();
    expect($transaction->hasNonFungibleAsset())->toBeFalse();
});
