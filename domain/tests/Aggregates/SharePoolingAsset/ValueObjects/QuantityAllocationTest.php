<?php

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\Quantity;

it('can return the total quantity', function () {
    $quantityAllocation = new QuantityAllocation([
        'foo' => new Quantity('1'),
        'bar' => new Quantity('2'),
        'baz' => Quantity::zero(),
    ]);

    expect($quantityAllocation->quantity()->isEqualTo('3'))->toBeTrue();
});

it('can return whether a quantity is allocated', function (string $transactionId, bool $hasQuantityAllocated) {
    $quantityAllocation = new QuantityAllocation(['foo' => new Quantity('1')]);

    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'id' => SharePoolingAssetTransactionId::fromString($transactionId),
    ]);

    expect($quantityAllocation->hasQuantityAllocatedTo($acquisition))->toBe($hasQuantityAllocated);
})->with([
    'yes' => ['foo', true],
    'no' => ['bar', false],
]);

it('can return the allocated quantity', function (string $quantity, bool $hasQuantityAllocated) {
    $quantityAllocation = new QuantityAllocation(['foo' => new Quantity($quantity)]);

    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'id' => SharePoolingAssetTransactionId::fromString('foo'),
    ]);

    expect($quantityAllocation->hasQuantityAllocatedTo($acquisition))->toBe($hasQuantityAllocated);
    expect($quantityAllocation->quantityAllocatedTo($acquisition)->isEqualTo($quantity))->toBeTrue();
})->with([
    'scenario 1' => ['1', true],
    'scenario 2' => ['2', true],
    'scenario 3' => ['0', false],
]);

it('can allocate a quantity', function (string $quantity, bool $hasQuantityAllocated) {
    $quantityAllocation = new QuantityAllocation();

    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'id' => SharePoolingAssetTransactionId::fromString('foo'),
    ]);

    expect($quantityAllocation->hasQuantityAllocatedTo($acquisition))->toBeFalse();

    $quantityAllocation->allocateQuantity(new Quantity($quantity), $acquisition);

    expect($quantityAllocation->hasQuantityAllocatedTo($acquisition))->toBe($hasQuantityAllocated);
    expect($quantityAllocation->quantityAllocatedTo($acquisition)->isEqualTo($quantity))->toBeTrue();
})->with([
    'scenario 1' => ['1', true],
    'scenario 2' => ['2', true],
    'scenario 3' => ['0', false],
]);

it('can return the transaction IDs', function () {
    $quantityAllocation = new QuantityAllocation([
        'foo' => new Quantity('1'),
        'bar' => new Quantity('2'),
        'baz' => Quantity::zero(),
    ]);

    $transactionIds = $quantityAllocation->transactionIds();

    expect($transactionIds)->toHaveCount(3);
    expect(array_map(fn (SharePoolingAssetTransactionId $id) => (string) $id, $transactionIds))->toBe(['foo', 'bar', 'baz']);
});
