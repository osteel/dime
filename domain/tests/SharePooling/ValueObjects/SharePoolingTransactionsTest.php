<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisitions;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\SharePooling\ValueObjects\SharePoolingTransaction;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can make an empty collection of transactions', function () {
    $sharePoolingTransactions = SharePoolingTransactions::make();

    expect($sharePoolingTransactions->isEmpty())->toBeBool()->toBeTrue();
});

it('can make a collection of one transaction', function () {
    /** @var SharePoolingTokenAcquisition */
    $transaction = SharePoolingTokenAcquisition::factory()->make();

    $sharePoolingTransactions = SharePoolingTransactions::make($transaction);

    expect($sharePoolingTransactions->isEmpty())->toBeBool()->toBeFalse();
    expect($sharePoolingTransactions->count())->toBeInt()->toBe(1);
});

it('can make a collection of transactions', function () {
    /** @var array<int, SharePoolingTransaction> */
    $transactions = [
        SharePoolingTokenAcquisition::factory()->make(),
        SharePoolingTokenDisposal::factory()->make(),
        SharePoolingTokenAcquisition::factory()->make(),
    ];

    $sharePoolingTransactions = SharePoolingTransactions::make(...$transactions);

    expect($sharePoolingTransactions->isEmpty())->toBeBool()->toBeFalse();
    expect($sharePoolingTransactions->count())->toBeInt()->toBe(3);
});

it('can make a copy of a collection of transactions', function () {
    /** @var SharePoolingTokenAcquisition */
    $transaction = SharePoolingTokenAcquisition::factory()->make();

    $sharePoolingTransactions = SharePoolingTransactions::make($transaction);

    $copy = $sharePoolingTransactions->copy();

    expect($copy)->not->toBe($sharePoolingTransactions);
    expect($copy->count())->toBe(1);
});

it('can add a transaction to a collection of transactions', function () {
    /** @var SharePoolingTokenDisposal */
    $transaction = SharePoolingTokenDisposal::factory()->make();

    $sharePoolingTransactions = SharePoolingTransactions::make($transaction);
    $sharePoolingTransactions = $sharePoolingTransactions->add($transaction);

    expect($sharePoolingTransactions->count())->toBeInt()->toBe(2);
});

it('can return the total quantity of a collection of transactions', function (array $quantities, string $total) {
    $transactions = [];

    foreach ($quantities as $quantity) {
        $transactions[] = SharePoolingTokenAcquisition::factory()->make(['quantity' => new Quantity($quantity)]);
    }

    $sharePoolingTransactions = SharePoolingTransactions::make(...$transactions);

    //expect($sharePoolingTransactions->quantity())->toBeString()->toBe($total);
    expect($sharePoolingTransactions->quantity())->toBeInstanceOf(Quantity::class)->toMatchObject(new Quantity($total));
})->with([
    'scenario 1' => [['10'], '10'],
    'scenario 2' => [['10', '30', '40', '20'], '100'],
    'scenario 3' => [['1.12345678', '1.123456789'], '2.246913569'],
]);

it('can return the average cost basis per unit of a collection of transactions', function (array $costBases, string $average) {
    $transactions = [];

    foreach ($costBases as $quantity => $costBasis) {
        $transactions[] = SharePoolingTokenAcquisition::factory()->make([
            'quantity' => new Quantity($quantity),
            'costBasis' => new FiatAmount($costBasis, FiatCurrency::GBP),
        ]);
    }

    $sharePoolingTransactions = SharePoolingTransactions::make(...$transactions);

    expect($sharePoolingTransactions->averageCostBasisPerUnit())
        ->toBeInstanceOf(FiatAmount::class)
        ->toEqual(new FiatAmount($average, FiatCurrency::GBP));
})->with([
    'scenario 1' => [['10' => '10'], '1'],
    'scenario 2' => [['10' => '4', '20' => '10', '20' => '11'], '0.5'],
    'scenario 3' => [['35' => '1.12345678', '65' => '1.123456789'], '0.02246913569'],
]);

it('can return a collection of transactions that happened on a specific day', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $sharePoolingTransactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    );

    $transactions = $sharePoolingTransactions->madeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 2],
    'scenario 2' => ['2015-10-22', 3],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of acquisitions that happened on a specific day', function (string $date, int $count) {
    $sharePoolingTransactions = SharePoolingTransactions::make(
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    );

    $transactions = $sharePoolingTransactions->acquisitionsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 1],
    'scenario 2' => ['2015-10-22', 2],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of disposals that happened on a specific day', function (string $date, int $count) {
    $sharePoolingTransactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    );

    $transactions = $sharePoolingTransactions->disposalsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 1],
    'scenario 2' => ['2015-10-22', 2],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);
