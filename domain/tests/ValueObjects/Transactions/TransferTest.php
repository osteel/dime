<?php

use Domain\ValueObjects\Asset;
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
