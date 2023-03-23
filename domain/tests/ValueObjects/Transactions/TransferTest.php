<?php

use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Transfer;

it('can tell whether the transferred asset is a NFT', function () {
    /** @var Transfer */
    $transaction = Transfer::factory()->nft()->make();

    expect($transaction->hasNft())->toBeTrue();
    expect($transaction->hasSharePoolingAsset())->toBeFalse();
});

it('can tell whether the transferred asset is a share pooling asset', function () {
    /** @var Transfer */
    $transaction = Transfer::factory()->make(['asset' => new Asset('BTC')]);

    expect($transaction->hasSharePoolingAsset())->toBeTrue();
    expect($transaction->hasNft())->toBeFalse();
});
