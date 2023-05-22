<?php

use Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions\SharePoolingAssetIdException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use Domain\ValueObjects\Asset;

it('can create an aggregate root ID from an asset', function () {
    expect(SharePoolingAssetId::fromAsset(new Asset('foo'))->toString())->toBe('FOO');
});

it('cannot create an aggregate root ID from an asset because it is non-fungible', function () {
    $asset = Asset::nonFungible('foo');

    expect(fn () => SharePoolingAssetId::fromAsset($asset))
        ->toThrow(SharePoolingAssetIdException::class, SharePoolingAssetIdException::assetIsNonFungible($asset)->getMessage());
});

it('can return an asset from an aggregate root ID', function () {
    $asset = new Asset('foo');

    expect(SharePoolingAssetId::fromAsset($asset)->toAsset())->toEqual($asset);
});
