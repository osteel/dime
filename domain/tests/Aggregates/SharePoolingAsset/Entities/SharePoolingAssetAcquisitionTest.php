<?php

use Brick\DateTime\LocalDate;
use Brick\DateTime\TimeZone;
use Domain\Aggregates\SharePoolingAsset\Entities\Exceptions\SharePoolingAssetAcquisitionException;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('cannot instantiate an acquisition because the allocated quantity is greater than the available quantity', function () {
    expect(fn () => SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity('100'),
        'sameDayQuantity' => new Quantity('50'),
        'thirtyDayQuantity' => new Quantity('51'),
    ]))->toThrow(
        SharePoolingAssetAcquisitionException::class,
        SharePoolingAssetAcquisitionException::excessiveQuantityAllocated(new Quantity('100'), new Quantity('101'))->getMessage()
    );
});

it('can instantiate an acquisition', function () {
    $acquisition = new SharePoolingAssetAcquisition(
        asset: new Asset('FOO'),
        date: LocalDate::now(TimeZone::utc()),
        quantity: Quantity::zero(),
        costBasis: FiatAmount::GBP('100'),
    );

    expect($acquisition->id)->toBeInstanceOf(SharePoolingAssetTransactionId::class);
    expect($acquisition->sameDayQuantity()->isZero())->toBeTrue();
    expect($acquisition->thirtyDayQuantity()->isZero())->toBeTrue();
});

it('can return the average cost basis per unit', function (string $quantity, string $costBasis, string $average) {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity($quantity),
        'costBasis' => FiatAmount::GBP($costBasis),
    ]);

    expect($acquisition->averageCostBasisPerUnit()->isEqualTo(FiatAmount::GBP($average)))->toBeTrue();
})->with([
    'scenario 1' => ['100', '100', '1'],
    'scenario 2' => ['100', '200', '2'],
]);

it('can return the section 104 pool cost basis', function (string $quantity, string $costBasis, string $result) {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity($quantity),
        'costBasis' => FiatAmount::GBP($costBasis),
        'sameDayQuantity' => new Quantity('30'),
        'thirtyDayQuantity' => new Quantity('60'),
    ]);

    expect($acquisition->section104PoolCostBasis()->isEqualTo(FiatAmount::GBP($result)))->toBeTrue();
})->with([
    'scenario 1' => ['100', '100', '10'],
    'scenario 2' => ['100', '200', '20'],
]);

it('can return the various quantities', function () {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'sameDayQuantity' => new Quantity('30'),
        'thirtyDayQuantity' => new Quantity('60'),
    ]);

    expect((string) $acquisition->sameDayQuantity())->toBe('30');
    expect($acquisition->hasThirtyDayQuantity())->toBeTrue();
    expect((string) $acquisition->thirtyDayQuantity())->toBe('60');
    expect($acquisition->hasSection104PoolQuantity())->toBeTrue();
    expect((string) $acquisition->section104PoolQuantity())->toBe('10');
    expect($acquisition->hasAvailableSameDayQuantity())->toBeTrue();
    expect((string) $acquisition->availableSameDayQuantity())->toBe('70');
    expect($acquisition->hasAvailableThirtyDayQuantity())->toBeTrue();
    expect((string) $acquisition->availableThirtyDayQuantity())->toBe('10');
});

it('can increase the same-day quantity', function (string $increase, string $sameDayQuantity, string $thirtyDayQuantity) {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity('100'),
        'sameDayQuantity' => new Quantity('30'),
        'thirtyDayQuantity' => new Quantity('60'),
    ]);

    $acquisition->increaseSameDayQuantity(new Quantity($increase));

    expect((string) $acquisition->sameDayQuantity())->toBe($sameDayQuantity);
    expect((string) $acquisition->thirtyDayQuantity())->toBe($thirtyDayQuantity);
})->with([
    'scenario 1' => ['5', '35', '55'],
    'scenario 2' => ['10', '40', '50'],
    'scenario 3' => ['70', '100', '0'],
    'scenario 4' => ['71', '100', '0'],
]);

it('cannot decrease the same-day quantity because the quantity is too great', function () {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity('100'),
        'sameDayQuantity' => new Quantity('30'),
        'thirtyDayQuantity' => new Quantity('60'),
    ]);

    expect(fn () => $acquisition->decreaseSameDayQuantity(new Quantity('31')))->toThrow(
        SharePoolingAssetAcquisitionException::class,
        SharePoolingAssetAcquisitionException::insufficientSameDayQuantity(new Quantity('31'), new Quantity('30'))->getMessage(),
    );
});

it('can decrease the same-day quantity', function () {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity('100'),
        'sameDayQuantity' => new Quantity('30'),
        'thirtyDayQuantity' => new Quantity('60'),
    ]);

    $acquisition->decreaseSameDayQuantity(new Quantity('10'));

    expect((string) $acquisition->sameDayQuantity())->toBe('20');
});

it('can increase the 30-day quantity', function (string $increase, string $sameDayQuantity, string $thirtyDayQuantity) {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity('100'),
        'sameDayQuantity' => new Quantity('30'),
        'thirtyDayQuantity' => new Quantity('60'),
    ]);

    $acquisition->increaseThirtyDayQuantity(new Quantity($increase));

    expect((string) $acquisition->sameDayQuantity())->toBe($sameDayQuantity);
    expect((string) $acquisition->thirtyDayQuantity())->toBe($thirtyDayQuantity);
})->with([
    'scenario 1' => ['5', '30', '65'],
    'scenario 2' => ['10', '30', '70'],
    'scenario 3' => ['15', '30', '70'],
]);

it('cannot decrease the 30-day quantity because the quantity is too great', function () {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity('100'),
        'sameDayQuantity' => new Quantity('30'),
        'thirtyDayQuantity' => new Quantity('60'),
    ]);

    expect(fn () => $acquisition->decreaseThirtyDayQuantity(new Quantity('61')))->toThrow(
        SharePoolingAssetAcquisitionException::class,
        SharePoolingAssetAcquisitionException::insufficientThirtyDayQuantity(new Quantity('61'), new Quantity('60'))->getMessage(),
    );
});

it('can decrease the 30-day quantity', function () {
    /** @var SharePoolingAssetAcquisition */
    $acquisition = SharePoolingAssetAcquisition::factory()->make([
        'quantity' => new Quantity('100'),
        'sameDayQuantity' => new Quantity('30'),
        'thirtyDayQuantity' => new Quantity('60'),
    ]);

    $acquisition->decreaseThirtyDayQuantity(new Quantity('10'));

    expect((string) $acquisition->thirtyDayQuantity())->toBe('50');
});
