<?php

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Exceptions\SwapException;
use Domain\ValueObjects\Transactions\Swap;

it('cannot instantiate a swap because both assets are fiat', function () {
    $asset = new Asset(FiatCurrency::GBP->value);

    expect(fn () => Swap::factory()->make(['disposedOfAsset' => $asset, 'acquiredAsset' => $asset]))
        ->toThrow(SwapException::class);
});

it('can tell whether one of the assets is a non-fungible asset', function (?string $method, bool $result) {
    /** @var Swap */
    $transaction = Swap::factory()->when($method, fn ($factory) => $factory->$method())->make();

    expect($transaction->hasNonFungibleAsset())->toBe($result);
})->with([
    'disposed of asset' => ['fromNonFungibleAsset', true],
    'acquired asset' => ['toNonFungibleAsset', true],
    'both assets' => ['nonFungibleAssets', true],
    'none' => [null, false],
]);

it('can tell whether the disposed of asset is a share pooling asset', function (?string $method, bool $result) {
    /** @var Swap */
    $transaction = Swap::factory()->when($method, fn ($factory) => $factory->$method())->make();

    expect($transaction->disposedOfAssetIsSharePoolingAsset())->toBe($result);
})->with([
    'nonFungibleAsset' => ['fromNonFungibleAsset', false],
    'fiat' => ['fromFiat', false],
    'share pooling asset' => [null, true],
]);

it('can tell whether the acquired asset is a share pooling asset', function (?string $method, bool $result) {
    /** @var Swap */
    $transaction = Swap::factory()->when($method, fn ($factory) => $factory->$method())->make();

    expect($transaction->acquiredAssetIsSharePoolingAsset())->toBe($result);
})->with([
    'nonFungibleAsset' => ['toNonFungibleAsset', false],
    'fiat' => ['toFiat', false],
    'share pooling asset' => [null, true],
]);

it('can tell whether one of the assets is a share pooling asset', function (?string $method, bool $result) {
    /** @var Swap */
    $transaction = Swap::factory()->when($method, fn ($factory) => $factory->$method())->make();

    expect($transaction->hasSharePoolingAsset())->toBe($result);
})->with([
    'acquired asset' => ['fromFiat', true],
    'disposed of asset' => ['toFiat', true],
    'both' => [null, true],
]);

it('can tell whether one of the assets is fiat', function (?string $method, bool $result) {
    /** @var Swap */
    $transaction = Swap::factory()->when($method, fn ($factory) => $factory->$method())->make();

    expect($transaction->hasFiat())->toBe($result);
})->with([
    'disposed of asset' => ['fromFiat', true],
    'acquired asset' => ['toFiat', true],
    'none' => [null, false],
]);
