<?php

use Domain\Enums\FiatCurrency;
use Domain\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can make an empty collection of disposals', function () {
    $disposals = SharePoolingTokenDisposals::make();

    expect($disposals->isEmpty())->toBeBool()->toBeTrue();
});

it('can make a collection of one disposal', function () {
    /** @var SharePoolingTokenDisposal */
    $disposal = SharePoolingTokenDisposal::factory()->make();

    $disposals = SharePoolingTokenDisposals::make($disposal);

    expect($disposals->isEmpty())->toBeBool()->toBeFalse();
    expect($disposals->count())->toBeInt()->toBe(1);
});

it('can make a collection of disposals', function () {
    /** @var array<int, SharePoolingTokenDisposal> */
    $items = [
        SharePoolingTokenDisposal::factory()->make(),
        SharePoolingTokenDisposal::factory()->make(),
        SharePoolingTokenDisposal::factory()->make(),
    ];

    $disposals = SharePoolingTokenDisposals::make(...$items);

    expect($disposals->isEmpty())->toBeBool()->toBeFalse();
    expect($disposals->count())->toBeInt()->toBe(3);
});

it('can add a disposal to a collection of disposals', function () {
    /** @var SharePoolingTokenDisposal */
    $disposal = SharePoolingTokenDisposal::factory()->make();

    $disposals = SharePoolingTokenDisposals::make($disposal)->add($disposal);

    expect($disposals->count())->toBeInt()->toBe(2);
});

it('can reverse a collection of disposals', function () {
    /** @var array<int, SharePoolingTokenDisposal> */
    $items = [
        $disposal1 = SharePoolingTokenDisposal::factory()->make(),
        $disposal2 = SharePoolingTokenDisposal::factory()->make(),
        $disposal3 = SharePoolingTokenDisposal::factory()->make(),
    ];

    $disposals = SharePoolingTokenDisposals::make(...$items)->reverse();

    expect($disposals->count())->toBeInt()->toBe(3);
    expect($disposals->getIterator()[0])->toBe($disposal3);
    expect($disposals->getIterator()[1])->toBe($disposal2);
    expect($disposals->getIterator()[2])->toBe($disposal1);
});

it('can return the unprocessed disposals from a collection of disposals', function () {
    /** @var array<int, SharePoolingTokenDisposal> */
    $items = [
        SharePoolingTokenDisposal::factory()->make(),
        $unprocessed1 = SharePoolingTokenDisposal::factory()->unprocessed()->make(),
        SharePoolingTokenDisposal::factory()->make(),
        $unprocessed2 = SharePoolingTokenDisposal::factory()->unprocessed()->make(),
    ];

    $disposals = SharePoolingTokenDisposals::make(...$items)->unprocessed();

    expect($disposals->count())->toBeInt()->toBe(2);
    expect($disposals->getIterator()[0])->toBe($unprocessed1);
    expect($disposals->getIterator()[1])->toBe($unprocessed2);
});

it('can return the disposals with available same-day quantity from a collection of disposals', function () {
    /** @var array<int, SharePoolingTokenDisposal> */
    $items = [
        SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('100')]),
        ]),
        $disposal1 = SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('10'), new Quantity('10')]),
        ]),
        $disposal2 = SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('90')]),
        ]),
        $disposal3 = SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('50')]),
            'thirtyDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('50')]),
        ]),
    ];

    $disposals = SharePoolingTokenDisposals::make(...$items)->withAvailableSameDayQuantity();

    expect($disposals->count())->toBe(3);
    expect($disposals->getIterator()[0])->toBe($disposal1);
    expect($disposals->getIterator()[1])->toBe($disposal2);
    expect($disposals->getIterator()[2])->toBe($disposal3);
});

it('can return the available same-day quantity from a collection of disposals', function () {
    /** @var array<int, SharePoolingTokenDisposal> */
    $items = [
        SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('100')]),
        ]),
        SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('10')]),
        ]),
        SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('80'), new Quantity('10')]),
        ]),
        SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('40'), new Quantity('10')]),
            'thirtyDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('50')]),
        ]),
    ];

    $disposals = SharePoolingTokenDisposals::make(...$items);

    expect($disposals->availableSameDayQuantity())->toBeInstanceOf(Quantity::class)->toEqual(new Quantity('240'));
});

it('can return the acquisitions with available 30-day quantity from a collection of acquisitions', function () {
    /** @var array<int, SharePoolingTokenDisposal> */
    $items = [
        SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('90'), new Quantity('10')]),
        ]),
        $disposal1 = SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('10')]),
        ]),
        $disposal2 = SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'thirtyDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('90')]),
        ]),
        SharePoolingTokenDisposal::factory()->make([
            'costBasis' => new FiatAmount('100', FiatCurrency::GBP),
            'quantity' => new Quantity('100'),
            'sameDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('50')]),
            'thirtyDayQuantityBreakdown' => new QuantityBreakdown([new Quantity('40'), new Quantity('10')]),
        ]),
    ];

    $disposals = SharePoolingTokenDisposals::make(...$items)->withAvailableThirtyDayQuantity();

    expect($disposals->count())->toBe(2);
    expect($disposals->getIterator()[0])->toBe($disposal1);
    expect($disposals->getIterator()[1])->toBe($disposal2);
});
