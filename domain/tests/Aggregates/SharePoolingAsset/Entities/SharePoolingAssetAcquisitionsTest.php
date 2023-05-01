<?php

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisitions;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can make an empty collection of acquisitions', function () {
    $acquisitions = SharePoolingAssetAcquisitions::make();

    expect($acquisitions->isEmpty())->toBeBool()->toBeTrue();
});

it('can make a collection of one acquisition', function () {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make();

    $acquisitions = SharePoolingAssetAcquisitions::make($acquisition);

    expect($acquisitions->isEmpty())->toBeBool()->toBeFalse();
    expect($acquisitions->count())->toBeInt()->toBe(1);
});

it('can make a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetAcquisition> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make(),
        SharePoolingAssetAcquisition::factory()->make(),
        SharePoolingAssetAcquisition::factory()->make(),
    ];

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items);

    expect($acquisitions->isEmpty())->toBeBool()->toBeFalse();
    expect($acquisitions->count())->toBeInt()->toBe(3);
});

it('can add an acquisition to a collection of acquisitions', function () {
    /** @var SharePoolingAssetAcquisition */
    $acquisition1 = SharePoolingAssetAcquisition::factory()->make();

    /** @var SharePoolingAssetAcquisition */
    $acquisition2 = SharePoolingAssetAcquisition::factory()->make();

    $acquisitions = SharePoolingAssetAcquisitions::make($acquisition1)->add($acquisition2);

    expect($acquisitions->count())->toBeInt()->toBe(2);

    // Adding the same transaction again should just replace it in the same spot
    $acquisitions->add($acquisition2);

    expect($acquisitions->count())->toBeInt()->toBe(2);
});

it('can return the total quantity of a collection of acquisitions', function (array $quantities, string $total) {
    $items = [];

    foreach ($quantities as $quantity) {
        $items[] = SharePoolingAssetAcquisition::factory()->make(['quantity' => new Quantity($quantity)]);
    }

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items);

    expect($acquisitions->quantity())->toBeInstanceOf(Quantity::class)->toEqual(new Quantity($total));
})->with([
    'scenario 1' => [['10'], '10'],
    'scenario 2' => [['10', '30', '40', '20'], '100'],
    'scenario 3' => [['1.12345678', '1.123456789'], '2.246913569'],
]);

it('can return the cost basis of a collection of acquisitions', function (array $costBases, string $total) {
    $acquisitions = [];

    foreach ($costBases as $costBasis) {
        $acquisitions[] = SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP($costBasis),
        ]);
    }

    $acquisitions = SharePoolingAssetAcquisitions::make(...$acquisitions);

    expect($acquisitions->costBasis())
        ->toBeInstanceOf(FiatAmount::class)
        ->toEqual(FiatAmount::GBP($total));
})->with([
    'scenario 1' => [['10'], '10'],
    'scenario 2' => [['4', '10', '11'], '25'],
    'scenario 3' => [['1.12345678', '1.123456789'], '2.246913569'],
]);

it('can return the average cost basis per unit of a collection of acquisitions', function (array $costBases, string $average) {
    $acquisitions = [];

    foreach ($costBases as $quantity => $costBasis) {
        $acquisitions[] = SharePoolingAssetAcquisition::factory()->make([
            'quantity' => new Quantity($quantity),
            'costBasis' => FiatAmount::GBP($costBasis),
        ]);
    }

    $acquisitions = SharePoolingAssetAcquisitions::make(...$acquisitions);

    expect($acquisitions->averageCostBasisPerUnit())
        ->toBeInstanceOf(FiatAmount::class)
        ->toEqual(FiatAmount::GBP($average));
})->with([
    'scenario 1' => [['10' => '10'], '1'],
    'scenario 2' => [['10' => '4', '20' => '10', '20' => '11'], '0.5'],
    'scenario 3' => [['35' => '1.12345678', '65' => '1.123456789'], '0.02246913569'],
]);

it('can return the section 104 pool quantity of a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetAcquisition> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make([
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('100'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('10'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'quantity' => new Quantity('100'),
            'thirtyDayQuantity' => new Quantity('90'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('20'),
            'thirtyDayQuantity' => new Quantity('20'),
        ]),
    ];

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items);

    expect($acquisitions->section104PoolQuantity())
        ->toBeInstanceOf(Quantity::class)
        ->toEqual(new Quantity('160'));
});

it('can return the section 104 pool cost basis of a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetAcquisition> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('100'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('10'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantity' => new Quantity('90'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('20'),
            'thirtyDayQuantity' => new Quantity('20'),
        ]),
    ];

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items);

    expect($acquisitions->section104PoolCostBasis())
        ->toBeInstanceOf(FiatAmount::class)
        ->toEqual(FiatAmount::GBP('160'));
});

it('can return the average section 104 pool cost basis per unit of a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetAcquisition> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('100'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('10'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantity' => new Quantity('90'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('20'),
            'thirtyDayQuantity' => new Quantity('20'),
        ]),
    ];

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items);

    expect($acquisitions->averageSection104PoolCostBasisPerUnit())
        ->toBeInstanceOf(FiatAmount::class)
        ->toEqual(FiatAmount::GBP('1'));
});

it('can return the acquisitions with available same-day quantity from a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetAcquisition> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('100'),
        ]),
        $acquisition1 = SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('10'),
        ]),
        $acquisition2 = SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantity' => new Quantity('90'),
        ]),
        $acquisition3 = SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('50'),
            'thirtyDayQuantity' => new Quantity('50'),
        ]),
    ];

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items)->withAvailableSameDayQuantity();

    expect($acquisitions->count())->toBe(3);
    expect($acquisitions->getIterator()[0])->toBe($acquisition1);
    expect($acquisitions->getIterator()[1])->toBe($acquisition2);
    expect($acquisitions->getIterator()[2])->toBe($acquisition3);
});

it('can return the available same-day quantity from a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetAcquisition> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('100'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('10'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantity' => new Quantity('90'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('50'),
            'thirtyDayQuantity' => new Quantity('50'),
        ]),
    ];

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items);

    expect($acquisitions->availableSameDayQuantity())->toBeInstanceOf(Quantity::class)->toEqual(new Quantity('240'));
});

it('can return the acquisitions with available 30-day quantity from a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetAcquisition> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('100'),
        ]),
        $acquisition1 = SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('10'),
        ]),
        $acquisition2 = SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantity' => new Quantity('90'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('50'),
            'thirtyDayQuantity' => new Quantity('50'),
        ]),
    ];

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items)->withAvailableThirtyDayQuantity();

    expect($acquisitions->count())->toBe(2);
    expect($acquisitions->getIterator()[0])->toBe($acquisition1);
    expect($acquisitions->getIterator()[1])->toBe($acquisition2);
});

it('can return the acquisitions with 30-day quantity from a collection of acquisitions', function () {
    /** @var list<SharePoolingAssetAcquisition> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('100'),
        ]),
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('10'),
        ]),
        $acquisition1 = SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantity' => new Quantity('90'),
        ]),
        $acquisition2 = SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('100'),
            'quantity' => new Quantity('100'),
            'sameDayQuantity' => new Quantity('50'),
            'thirtyDayQuantity' => new Quantity('50'),
        ]),
    ];

    $acquisitions = SharePoolingAssetAcquisitions::make(...$items)->withThirtyDayQuantity();

    expect($acquisitions->count())->toBe(2);
    expect($acquisitions->getIterator()[0])->toBe($acquisition1);
    expect($acquisitions->getIterator()[1])->toBe($acquisition2);
});
