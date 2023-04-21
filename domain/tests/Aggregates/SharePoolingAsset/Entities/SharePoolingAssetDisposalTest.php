<?php

use Brick\DateTime\LocalDate;
use Brick\DateTime\TimeZone;
use Domain\Aggregates\SharePoolingAsset\Entities\Exceptions\SharePoolingAssetDisposalException;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('cannot instantiate a disposal because the currencies don\'t match', function () {
    expect(fn () => SharePoolingAssetDisposal::factory()->make([
        'costBasis' => FiatAmount::GBP('100'),
        'proceeds' => new FiatAmount('150', FiatCurrency::EUR),
    ]))->toThrow(
        SharePoolingAssetDisposalException::class,
        SharePoolingAssetDisposalException::currencyMismatch(FiatCurrency::GBP, FiatCurrency::EUR)->getMessage(),
    );
});

it('cannot instantiate a disposal because the allocated quantity is greater than the available quantity', function () {
    expect(fn () => SharePoolingAssetDisposal::factory()->make([
        'quantity' => new Quantity('100'),
        'sameDayQuantityAllocation' => new QuantityAllocation(['foo' => new Quantity('50')]),
        'thirtyDayQuantityAllocation' => new QuantityAllocation(['bar' => new Quantity('51')]),
    ]))->toThrow(
        SharePoolingAssetDisposalException::class,
        SharePoolingAssetDisposalException::excessiveQuantityAllocated(new Quantity('100'), new Quantity('101'))->getMessage()
    );
});

it('can instantiate a disposal', function () {
    $disposal = new SharePoolingAssetDisposal(
        date: LocalDate::now(TimeZone::utc()),
        quantity: Quantity::zero(),
        costBasis: FiatAmount::GBP('100'),
        proceeds: FiatAmount::GBP('150'),
    );

    expect($disposal->id)->toBeInstanceOf(SharePoolingAssetTransactionId::class);
});

it('can copy a disposal as unprocessed', function () {
    /** @var SharePoolingAssetDisposal */
    $disposal = SharePoolingAssetDisposal::factory()->make(['processed' => true]);

    expect($disposal->processed)->toBeTrue();

    /** @var SharePoolingAssetDisposal */
    $copy = $disposal->copyAsUnprocessed();

    expect($copy->processed)->toBeFalse();
    expect(
        (string) $copy->id === (string) $disposal->id
        && $copy->date->isEqualTo($disposal->date)
        && $copy->quantity->isEqualTo($disposal->quantity)
        && $copy->costBasis->isZero()
        && $copy->proceeds->isEqualTo($disposal->proceeds)
    )->toBeTrue();
});

it('can return the various quantities', function () {
    /** @var SharePoolingAssetDisposal */
    $disposal = SharePoolingAssetDisposal::factory()->make([
        'sameDayQuantityAllocation' => new QuantityAllocation([
            'foo' => new Quantity('5'),
            'bar' => new Quantity('10'),
            'baz' => new Quantity('15'),
        ]),
        'thirtyDayQuantityAllocation' => new QuantityAllocation([
            'foo' => new Quantity('10'),
            'bar' => new Quantity('20'),
            'baz' => new Quantity('30'),
        ]),
    ]);

    expect((string) $disposal->sameDayQuantity())->toBe('30');
    expect($disposal->hasThirtyDayQuantity())->toBeTrue();
    expect((string) $disposal->thirtyDayQuantity())->toBe('60');
    expect($disposal->hasSection104PoolQuantity())->toBeTrue();
    expect((string) $disposal->section104PoolQuantity())->toBe('10');
    expect($disposal->hasAvailableSameDayQuantity())->toBeTrue();
    expect((string) $disposal->availableSameDayQuantity())->toBe('70');
    expect($disposal->hasAvailableThirtyDayQuantity())->toBeTrue();
    expect((string) $disposal->availableThirtyDayQuantity())->toBe('10');
});

it('can return the 30-day quantity allocated to an acquisition', function () {
    /** @var SharePoolingAssetDisposal */
    $disposal = SharePoolingAssetDisposal::factory()->make([
        'thirtyDayQuantityAllocation' => new QuantityAllocation([
            'foo' => new Quantity('10'),
            'bar' => new Quantity('20'),
            'baz' => Quantity::zero(),
        ]),
    ]);

    /** @var SharePoolingAssetAcquisition */
    $foo = SharePoolingAssetAcquisition::factory()->make(['id' => SharePoolingAssetTransactionId::fromString('foo')]);

    /** @var SharePoolingAssetAcquisition */
    $bar = SharePoolingAssetAcquisition::factory()->make(['id' => SharePoolingAssetTransactionId::fromString('bar')]);

    /** @var SharePoolingAssetAcquisition */
    $baz = SharePoolingAssetAcquisition::factory()->make(['id' => SharePoolingAssetTransactionId::fromString('baz')]);

    /** @var SharePoolingAssetAcquisition */
    $qux = SharePoolingAssetAcquisition::factory()->make(['id' => SharePoolingAssetTransactionId::fromString('qux')]);

    expect($disposal->hasThirtyDayQuantityAllocatedTo($foo))->toBeTrue();
    expect((string) $disposal->thirtyDayQuantityAllocatedTo($foo))->toBe('10');
    expect($disposal->hasThirtyDayQuantityAllocatedTo($bar))->toBeTrue();
    expect((string) $disposal->thirtyDayQuantityAllocatedTo($bar))->toBe('20');
    expect($disposal->hasThirtyDayQuantityAllocatedTo($baz))->toBeFalse();
    expect((string) $disposal->thirtyDayQuantityAllocatedTo($baz))->toBe('0');
    expect($disposal->hasThirtyDayQuantityAllocatedTo($qux))->toBeFalse();
    expect((string) $disposal->thirtyDayQuantityAllocatedTo($qux))->toBe('0');
});
