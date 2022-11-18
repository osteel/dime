<?php

use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;

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
