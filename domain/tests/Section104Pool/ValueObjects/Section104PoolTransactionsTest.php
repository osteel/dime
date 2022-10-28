<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Section104Pool\ValueObjects\Section104PoolAcquisition;
use Domain\Section104Pool\ValueObjects\Section104PoolDisposal;
use Domain\Section104Pool\ValueObjects\Section104PoolTransaction;
use Domain\Section104Pool\ValueObjects\Section104PoolTransactions;
use Domain\ValueObjects\FiatAmount;

it('can make an empty collection of transactions', function () {
    $section104PoolTransactions = Section104PoolTransactions::make();

    expect($section104PoolTransactions->isEmpty())->toBeBool()->toBeTrue();
});

it('can make a collection of one transaction', function () {
    /** @var Section104PoolAcquisition */
    $transaction = Section104PoolAcquisition::factory()->make();

    $section104PoolTransactions = Section104PoolTransactions::make($transaction);

    expect($section104PoolTransactions->isEmpty())->toBeBool()->toBeFalse();
    expect($section104PoolTransactions->count())->toBeInt()->toBe(1);
});

it('can make a collection of transactions', function () {
    /** @var array<int, Section104PoolTransaction> */
    $transactions = [
        Section104PoolAcquisition::factory()->make(),
        Section104PoolDisposal::factory()->make(),
        Section104PoolAcquisition::factory()->make(),
    ];

    $section104PoolTransactions = Section104PoolTransactions::make(...$transactions);

    expect($section104PoolTransactions->isEmpty())->toBeBool()->toBeFalse();
    expect($section104PoolTransactions->count())->toBeInt()->toBe(3);
});

it('can make a copy of a collection of transactions', function () {
    /** @var Section104PoolAcquisition */
    $transaction = Section104PoolAcquisition::factory()->make();

    $section104PoolTransactions = Section104PoolTransactions::make($transaction);

    $copy = $section104PoolTransactions->copy();

    expect($copy)->not->toBe($section104PoolTransactions);
    expect($copy->count())->toBe(1);
});

it('can add a transaction to a collection of transactions', function () {
    /** @var Section104PoolDisposal */
    $transaction = Section104PoolDisposal::factory()->make();

    $section104PoolTransactions = Section104PoolTransactions::make($transaction);
    $section104PoolTransactions = $section104PoolTransactions->add($transaction);

    expect($section104PoolTransactions->count())->toBeInt()->toBe(2);
});

it('can return the total quantity of a collection of transactions', function (array $quantities, string $total) {
    $transactions = [];

    foreach ($quantities as $quantity) {
        $transactions[] = Section104PoolDisposal::factory()->make(['quantity' => $quantity]);
    }

    $section104PoolTransactions = Section104PoolTransactions::make(...$transactions);

    expect($section104PoolTransactions->quantity())->toBeString()->toBe($total);
})->with([
    'scenario 1' => [['10'], '10'],
    'scenario 2' => [['10', '30', '40', '20'], '100'],
    'scenario 3' => [['1.12345678', '1.123456789'], '2.246913569'],
]);

it('can return the average cost basis per unit of a collection of transactions', function (array $costBases, string $average) {
    $transactions = [];

    foreach ($costBases as $quantity => $costBasis) {
        $transactions[] = Section104PoolAcquisition::factory()->make([
            'quantity' => $quantity,
            'costBasis' => new FiatAmount($costBasis, FiatCurrency::GBP),
        ]);
    }

    $section104PoolTransactions = Section104PoolTransactions::make(...$transactions);

    expect($section104PoolTransactions->averageCostBasisPerUnit())
        ->toBeInstanceOf(FiatAmount::class)
        ->toEqual(new FiatAmount($average, FiatCurrency::GBP));
})->with([
    'scenario 1' => [['10' => '10'], '1'],
    'scenario 2' => [['10' => '4', '20' => '10', '20' => '11'], '0.5'],
    'scenario 3' => [['35' => '1.12345678', '65' => '1.123456789'], '0.02246913569'],
]);

it('can return a collection of transactions that happened on a specific day', function (string $date, int $count) {
    /** @var Section104PoolTransactions */
    $section104PoolTransactions = Section104PoolTransactions::make(
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    );

    $transactions = $section104PoolTransactions->transactionsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(Section104PoolTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 2],
    'scenario 2' => ['2015-10-22', 3],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of acquisitions that happened on a specific day', function (string $date, int $count) {
    $section104PoolTransactions = Section104PoolTransactions::make(
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    );

    $transactions = $section104PoolTransactions->AcquisitionsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(Section104PoolTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 1],
    'scenario 2' => ['2015-10-22', 2],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of disposals that happened on a specific day', function (string $date, int $count) {
    $section104PoolTransactions = Section104PoolTransactions::make(
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        Section104PoolAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        Section104PoolDisposal::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    );

    $transactions = $section104PoolTransactions->disposalsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(Section104PoolTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 1],
    'scenario 2' => ['2015-10-22', 2],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);
