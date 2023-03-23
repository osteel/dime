<?php

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Exceptions\AcquisitionException;

it('cannot instantiate an acquisition because the asset is fiat', function () {
    expect(fn () => Acquisition::factory()->make(['asset' => new Asset(FiatCurrency::GBP->value)]))
        ->toThrow(AcquisitionException::class);
});

it('can tell whether the acquired asset is a NFT', function () {
    /** @var Acquisition */
    $transaction = Acquisition::factory()->nft()->make();

    expect($transaction->hasNft())->toBeTrue();
    expect($transaction->hasSharePoolingAsset())->toBeFalse();
});

it('can tell whether the acquired asset is a share pooling asset', function () {
    /** @var Acquisition */
    $transaction = Acquisition::factory()->make(['asset' => new Asset('BTC')]);

    expect($transaction->hasSharePoolingAsset())->toBeTrue();
    expect($transaction->hasNft())->toBeFalse();
});
