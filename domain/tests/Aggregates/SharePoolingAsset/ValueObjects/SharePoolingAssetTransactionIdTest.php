<?php

use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Ramsey\Uuid\Uuid;

it('can generate a new ID', function () {
    $id = SharePoolingAssetTransactionId::generate();

    expect($id->__toString())->toBeString()->not()->toBeEmpty();
    expect(Uuid::isValid((string) $id))->toBeTrue();
});

it('can generate an ID from a string', function () {
    $id = SharePoolingAssetTransactionId::fromString('foo');

    expect((string) $id)->toBe('foo');
});
