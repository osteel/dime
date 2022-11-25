<?php

use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisitions;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposals;
use Domain\SharePooling\ValueObjects\SharePoolingTransaction;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use Domain\ValueObjects\Quantity;

it('can make an empty collection of transactions', function () {
    $transactions = SharePoolingTransactions::make();

    expect($transactions->isEmpty())->toBeBool()->toBeTrue();
});

it('can make a collection of one transaction', function () {
    /** @var SharePoolingTokenAcquisition */
    $transaction = SharePoolingTokenAcquisition::factory()->make();

    $transactions = SharePoolingTransactions::make($transaction);

    expect($transactions->isEmpty())->toBeBool()->toBeFalse();
    expect($transactions->count())->toBeInt()->toBe(1);
});

it('can make a collection of transactions', function () {
    /** @var array<int, SharePoolingTransaction> */
    $items = [
        SharePoolingTokenAcquisition::factory()->make(),
        SharePoolingTokenDisposal::factory()->make(),
        SharePoolingTokenAcquisition::factory()->make(),
    ];

    $transactions = SharePoolingTransactions::make(...$items);

    expect($transactions->isEmpty())->toBeBool()->toBeFalse();
    expect($transactions->count())->toBeInt()->toBe(3);
});

it('can return the first transaction of a collection', function () {
    /** @var array<int, SharePoolingTransaction> */
    $items = [
        $first = SharePoolingTokenAcquisition::factory()->make(),
        SharePoolingTokenDisposal::factory()->make(),
        SharePoolingTokenAcquisition::factory()->make(),
    ];

    $transactions = SharePoolingTransactions::make(...$items);

    expect($transactions->first())->toBe($first);
});

it('can return a transaction at a position from the collection', function () {
    /** @var array<int, SharePoolingTransaction> */
    $items = [
        SharePoolingTokenAcquisition::factory()->make(),
        $second = SharePoolingTokenDisposal::factory()->make(),
        SharePoolingTokenAcquisition::factory()->make(),
    ];

    $transactions = SharePoolingTransactions::make(...$items);

    expect($transactions->get(1))->toBe($second);
});

it('can add a transaction to a collection of transactions', function () {
    /** @var SharePoolingTokenDisposal */
    $transaction = SharePoolingTokenDisposal::factory()->make();

    $transactions = SharePoolingTransactions::make($transaction)->add($transaction);

    expect($transactions->count())->toBeInt()->toBe(2);
});

it('can return the processed transactions from the collection', function () {
    /** @var array<int, SharePoolingTransaction> */
    $items = [
        $processed1 = SharePoolingTokenAcquisition::factory()->make(),
        SharePoolingTokenDisposal::factory()->unprocessed()->make(),
        $processed2 = SharePoolingTokenDisposal::factory()->processed()->make(),
    ];

    $transactions = SharePoolingTransactions::make(...$items)->processed();

    expect($transactions->count())->toBe(2);
    expect($transactions->get(0))->toBe($processed1);
    expect($transactions->get(1))->toBe($processed2);
});

it('can return the total quantity of a collection of transactions', function (array $quantities, string $total) {
    $items = [];

    foreach ($quantities as $quantity) {
        $items[] = SharePoolingTokenAcquisition::factory()->make(['quantity' => new Quantity($quantity)]);
    }

    $transactions = SharePoolingTransactions::make(...$items);

    expect($transactions->quantity())->toBeInstanceOf(Quantity::class)->toMatchObject(new Quantity($total));
})->with([
    'scenario 1' => [['10'], '10'],
    'scenario 2' => [['10', '30', '40', '20'], '100'],
    'scenario 3' => [['1.12345678', '1.123456789'], '2.246913569'],
]);

it('can return a collection of transactions that happened on a specific day', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    )->madeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 2],
    'scenario 2' => ['2015-10-22', 3],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of acquisitions that happened on a specific day', function (string $date, int $count) {
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    )->acquisitionsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 1],
    'scenario 2' => ['2015-10-22', 2],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of disposals that happened on a specific day', function (string $date, int $count) {
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    )->disposalsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 1],
    'scenario 2' => ['2015-10-22', 2],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of transactions that happened between two dates', function (string $date1, string $date2, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeBetween(Localdate::parse($date1), Localdate::parse($date2));

    expect($transactions)->toBeInstanceOf(SharePoolingTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', '2015-10-25', 6],
    'scenario 2' => ['2015-10-21', '2015-10-23', 4],
    'scenario 3' => ['2015-10-23', '2015-10-25', 3],
    'scenario 4' => ['2015-10-24', '2015-10-25', 2],
    'scenario 5' => ['2015-10-25', '2015-10-21', 6],
    'scenario 6' => ['2015-10-26', '2015-10-27', 0],
]);

it('can return a collection of acquisitions that happened between two dates', function (string $date1, string $date2, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeBetween(Localdate::parse($date1), Localdate::parse($date2));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', '2015-10-25', 3],
    'scenario 2' => ['2015-10-21', '2015-10-23', 2],
    'scenario 3' => ['2015-10-23', '2015-10-25', 1],
    'scenario 4' => ['2015-10-24', '2015-10-25', 1],
    'scenario 5' => ['2015-10-25', '2015-10-21', 3],
    'scenario 6' => ['2015-10-26', '2015-10-27', 0],
]);

it('can return a collection of disposals that happened between two dates', function (string $date1, string $date2, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeBetween(Localdate::parse($date1), Localdate::parse($date2));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', '2015-10-25', 3],
    'scenario 2' => ['2015-10-21', '2015-10-23', 2],
    'scenario 3' => ['2015-10-23', '2015-10-25', 2],
    'scenario 4' => ['2015-10-24', '2015-10-25', 1],
    'scenario 5' => ['2015-10-25', '2015-10-21', 3],
    'scenario 6' => ['2015-10-26', '2015-10-27', 0],
]);

it('can return a collection of transactions that happened before a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeBefore(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 5],
    'scenario 2' => ['2015-10-21', 0],
    'scenario 3' => ['2015-10-22', 2],
]);

it('can return a collection of acquisitions that happened before a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeBefore(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 3],
    'scenario 2' => ['2015-10-21', 0],
    'scenario 3' => ['2015-10-22', 1],
]);

it('can return a collection of disposals that happened before a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeBefore(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 2],
    'scenario 2' => ['2015-10-21', 0],
    'scenario 3' => ['2015-10-22', 1],
]);

it('can return a collection of transactions that happened before a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeBeforeOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 6],
    'scenario 2' => ['2015-10-21', 2],
    'scenario 3' => ['2015-10-22', 3],
    'scenario 4' => ['2015-10-20', 0],
]);

it('can return a collection of acquisitions that happened before a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeBeforeOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 3],
    'scenario 2' => ['2015-10-21', 1],
    'scenario 3' => ['2015-10-22', 2],
    'scenario 4' => ['2015-10-20', 0],
]);

it('can return a collection of disposals that happened before a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeBeforeOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 3],
    'scenario 2' => ['2015-10-21', 1],
    'scenario 3' => ['2015-10-22', 1],
    'scenario 4' => ['2015-10-20', 0],
]);

it('can return a collection of transactions that happened after a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeAfter(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 4],
    'scenario 2' => ['2015-10-25', 0],
    'scenario 3' => ['2015-10-22', 3],
]);

it('can return a collection of acquisitions that happened after a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeAfter(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 2],
    'scenario 2' => ['2015-10-25', 0],
    'scenario 3' => ['2015-10-22', 1],
]);

it('can return a collection of disposals that happened after a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeAfter(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 2],
    'scenario 2' => ['2015-10-25', 0],
    'scenario 3' => ['2015-10-22', 2],
]);

it('can return a collection of transactions that happened after a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeAfterOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 6],
    'scenario 2' => ['2015-10-25', 1],
    'scenario 3' => ['2015-10-22', 4],
    'scenario 4' => ['2015-10-26', 0],
]);

it('can return a collection of acquisitions that happened after a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeAfterOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 3],
    'scenario 2' => ['2015-10-24', 1],
    'scenario 3' => ['2015-10-22', 2],
    'scenario 4' => ['2015-10-26', 0],
]);

it('can return a collection of disposals that happened after a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingTokenAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingTokenDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeAfterOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingTokenDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 3],
    'scenario 2' => ['2015-10-25', 1],
    'scenario 3' => ['2015-10-22', 2],
    'scenario 4' => ['2015-10-26', 0],
]);

it('can return a collection of disposals with 30-day quantity matched with an acquisition', function () {
    /** @var SharePoolingTokenAcquisition */
    $acquisition = SharePoolingTokenAcquisition::factory()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'sameDayQuantity' => new Quantity('10'),
        'thirtyDayQuantity' => new Quantity('100'),
    ])->setPosition(0);

    /** @var SharePoolingTransactions */
    $transactions = SharePoolingTransactions::make(
        $acquisition,
        $disposal1 = SharePoolingTokenDisposal::factory()->withThirtyDayQuantity(new Quantity('30'), position: 0)->make(),
        SharePoolingTokenDisposal::factory()->withSameDayQuantity(new Quantity('10'), position: 0)->make(),
        SharePoolingTokenAcquisition::factory()->make(),
        SharePoolingTokenDisposal::factory()->withThirtyDayQuantity(new Quantity('20'), position: 3)->make(),
        $disposal2 = SharePoolingTokenDisposal::factory()->withThirtyDayQuantity(new Quantity('70'), position: 0)->make(),
        SharePoolingTokenDisposal::factory()->make(),
    )->disposalsWithThirtyDayQuantityMatchedWith($acquisition);

    expect($transactions)->toBeInstanceOf(SharePoolingTokenDisposals::class);
    expect($transactions->count())->toEqual(2);
    expect($transactions->getIterator()[0])->toBe($disposal1);
    expect($transactions->getIterator()[1])->toBe($disposal2);
});
