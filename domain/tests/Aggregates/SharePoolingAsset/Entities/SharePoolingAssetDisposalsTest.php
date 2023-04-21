<?php

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposals;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can make an empty collection of disposals', function () {
    $disposals = SharePoolingAssetDisposals::make();

    expect($disposals->isEmpty())->toBeBool()->toBeTrue();
});

it('can make a collection of one disposal', function () {
    /** @var SharePoolingAssetDisposal */
    $disposal = SharePoolingAssetDisposal::factory()->make();

    $disposals = SharePoolingAssetDisposals::make($disposal);

    expect($disposals->isEmpty())->toBeBool()->toBeFalse();
    expect($disposals->count())->toBeInt()->toBe(1);
});

it('can make a collection of disposals', function () {
    /** @var list<SharePoolingAssetDisposal> */
    $items = [
        SharePoolingAssetDisposal::factory()->make(),
        SharePoolingAssetDisposal::factory()->make(),
        SharePoolingAssetDisposal::factory()->make(),
    ];

    $disposals = SharePoolingAssetDisposals::make(...$items);

    expect($disposals->isEmpty())->toBeBool()->toBeFalse();
    expect($disposals->count())->toBeInt()->toBe(3);
});

it('can add a disposal to a collection of disposals', function () {
    /** @var SharePoolingAssetDisposal */
    $disposal1 = SharePoolingAssetDisposal::factory()->make();

    /** @var SharePoolingAssetDisposal */
    $disposal2 = SharePoolingAssetDisposal::factory()->make();

    $disposals = SharePoolingAssetDisposals::make($disposal1)->add($disposal2);

    expect($disposals->count())->toBeInt()->toBe(2);

    // Adding the same transaction again should just replace it in the same spot
    $disposals->add($disposal2);

    expect($disposals->count())->toBeInt()->toBe(2);
});

it('can reverse a collection of disposals', function () {
    /** @var list<SharePoolingAssetDisposal> */
    $items = [
        $disposal1 = SharePoolingAssetDisposal::factory()->make(),
        $disposal2 = SharePoolingAssetDisposal::factory()->make(),
        $disposal3 = SharePoolingAssetDisposal::factory()->make(),
    ];

    $disposals = SharePoolingAssetDisposals::make(...$items)->reverse();

    expect($disposals->count())->toBeInt()->toBe(3);
    expect($disposals->getIterator()[0])->toBe($disposal3);
    expect($disposals->getIterator()[1])->toBe($disposal2);
    expect($disposals->getIterator()[2])->toBe($disposal1);
});

it('can return the unprocessed disposals from a collection of disposals', function () {
    /** @var list<SharePoolingAssetDisposal> */
    $items = [
        SharePoolingAssetDisposal::factory()->make(),
        $unprocessed1 = SharePoolingAssetDisposal::factory()->unprocessed()->make(),
        SharePoolingAssetDisposal::factory()->make(),
        $unprocessed2 = SharePoolingAssetDisposal::factory()->unprocessed()->make(),
    ];

    $disposals = SharePoolingAssetDisposals::make(...$items)->unprocessed();

    expect($disposals->count())->toBeInt()->toBe(2);
    expect($disposals->getIterator()[0])->toBe($unprocessed1);
    expect($disposals->getIterator()[1])->toBe($unprocessed2);
});

it('can return the disposals with available same-day quantity from a collection of disposals', function () {
    /** @var list<SharePoolingAssetDisposal> */
    $items = [
        SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('100')]),
        ]),
        $disposal1 = SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('10'), new Quantity('10')]),
        ]),
        $disposal2 = SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantityAllocation' => new QuantityAllocation([new Quantity('90')]),
        ]),
        $disposal3 = SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('50')]),
            'thirtyDayQuantityAllocation' => new QuantityAllocation([new Quantity('50')]),
        ]),
    ];

    $disposals = SharePoolingAssetDisposals::make(...$items)->withAvailableSameDayQuantity();

    expect($disposals->count())->toBe(3);
    expect($disposals->getIterator()[0])->toBe($disposal1);
    expect($disposals->getIterator()[1])->toBe($disposal2);
    expect($disposals->getIterator()[2])->toBe($disposal3);
});

it('can return the available same-day quantity from a collection of disposals', function () {
    /** @var list<SharePoolingAssetDisposal> */
    $items = [
        SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('100')]),
        ]),
        SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('10')]),
        ]),
        SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantityAllocation' => new QuantityAllocation([new Quantity('80'), new Quantity('10')]),
        ]),
        SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('40'), new Quantity('10')]),
            'thirtyDayQuantityAllocation' => new QuantityAllocation([new Quantity('50')]),
        ]),
    ];

    $disposals = SharePoolingAssetDisposals::make(...$items);

    expect($disposals->availableSameDayQuantity())->toBeInstanceOf(Quantity::class)->toEqual(new Quantity('240'));
});

it('can return the acquisitions with available 30-day quantity from a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetDisposal> */
    $items = [
        SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('90'), new Quantity('10')]),
        ]),
        $disposal1 = SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('10')]),
        ]),
        $disposal2 = SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantityAllocation' => new QuantityAllocation([new Quantity('90')]),
        ]),
        SharePoolingAssetDisposal::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantityAllocation' => new QuantityAllocation([new Quantity('50')]),
            'thirtyDayQuantityAllocation' => new QuantityAllocation([new Quantity('40'), new Quantity('10')]),
        ]),
    ];

    $disposals = SharePoolingAssetDisposals::make(...$items)->withAvailableThirtyDayQuantity();

    expect($disposals->count())->toBe(2);
    expect($disposals->getIterator()[0])->toBe($disposal1);
    expect($disposals->getIterator()[1])->toBe($disposal2);
});
