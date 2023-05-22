<?php

use Domain\Aggregates\NonFungibleAsset\ValueObjects\Exceptions\NonFungibleAssetIdException;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\ValueObjects\Asset;

it('can create an aggregate root ID from an asset', function () {
    expect(NonFungibleAssetId::fromAsset(Asset::nonFungible('foo'))->toString())->toBe('foo');
});

it('cannot create an aggregate root ID from an asset because it is fungible', function () {
    $asset = new Asset('foo');

    expect(fn () => NonFungibleAssetId::fromAsset($asset))
        ->toThrow(NonFungibleAssetIdException::class, NonFungibleAssetIdException::assetIsFungible($asset)->getMessage());
});

it('can return an asset from an aggregate root ID', function () {
    $asset = Asset::nonFungible('foo');

    expect(NonFungibleAssetId::fromAsset($asset)->toAsset())->toEqual($asset);
});
