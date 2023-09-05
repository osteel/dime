<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisitions;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposals;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransaction;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\ValueObjects\Quantity;

it('can make an empty collection of transactions', function () {
    $transactions = SharePoolingAssetTransactions::make();

    expect($transactions->isEmpty())->toBeBool()->toBeTrue();
});

it('can make a collection of one transaction', function () {
    /** @var SharePoolingAssetAcquisition */
    $transaction = SharePoolingAssetAcquisition::factory()->make();

    $transactions = SharePoolingAssetTransactions::make($transaction);

    expect($transactions->isEmpty())->toBeBool()->toBeFalse();
    expect($transactions->count())->toBeInt()->toBe(1);
});

it('can make a collection of transactions', function () {
    /** @var list<SharePoolingAssetTransaction> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make(),
        SharePoolingAssetDisposal::factory()->make(),
        SharePoolingAssetAcquisition::factory()->make(),
    ];

    $transactions = SharePoolingAssetTransactions::make(...$items);

    expect($transactions->isEmpty())->toBeBool()->toBeFalse();
    expect($transactions->count())->toBeInt()->toBe(3);
});

it('can return the first transaction of a collection', function () {
    /** @var list<SharePoolingAssetTransaction> */
    $items = [
        $first = SharePoolingAssetAcquisition::factory()->make(),
        SharePoolingAssetDisposal::factory()->make(),
        SharePoolingAssetAcquisition::factory()->make(),
    ];

    $transactions = SharePoolingAssetTransactions::make(...$items);

    expect($transactions->first())->toBe($first);
});

it('can return a transaction at an index from the collection', function () {
    /** @var list<SharePoolingAssetTransaction> */
    $items = [
        SharePoolingAssetAcquisition::factory()->make(),
        $second = SharePoolingAssetDisposal::factory()->make(),
        SharePoolingAssetAcquisition::factory()->make(),
    ];

    $transactions = SharePoolingAssetTransactions::make(...$items);

    expect($transactions->get(1))->toBe($second);
});

it('can add a transaction to a collection of transactions', function () {
    /** @var SharePoolingAssetDisposal */
    $transaction1 = SharePoolingAssetDisposal::factory()->make();

    /** @var SharePoolingAssetAcquisition */
    $transaction2 = SharePoolingAssetAcquisition::factory()->make();

    $transactions = SharePoolingAssetTransactions::make($transaction1)->add($transaction2);

    expect($transactions->count())->toBeInt()->toBe(2);

    // Adding the same transaction again should just replace it in the same spot
    $transactions->add($transaction2);

    expect($transactions->count())->toBeInt()->toBe(2);
});

it('can get a transaction from the collection by its ID', function () {
    /** @var SharePoolingAssetDisposal */
    $transaction1 = SharePoolingAssetDisposal::factory()->make();

    /** @var SharePoolingAssetAcquisition */
    $transaction2 = SharePoolingAssetAcquisition::factory()->make();

    $transactions = SharePoolingAssetTransactions::make($transaction1)->add($transaction2);

    expect($transactions->getForId($transaction2->id))->toBe($transaction2);
});

it('can return the processed transactions from the collection', function () {
    /** @var list<SharePoolingAssetTransaction> */
    $items = [
        $processed1 = SharePoolingAssetAcquisition::factory()->make(),
        SharePoolingAssetDisposal::factory()->unprocessed()->make(),
        $processed2 = SharePoolingAssetDisposal::factory()->processed()->make(),
    ];

    $transactions = SharePoolingAssetTransactions::make(...$items)->processed();

    expect($transactions->count())->toBe(2);
    expect($transactions->get(0))->toBe($processed1);
    expect($transactions->get(1))->toBe($processed2);
});

it('can return the total quantity of a collection of transactions', function (array $quantities, string $total) {
    $items = [];

    foreach ($quantities as $quantity) {
        $items[] = SharePoolingAssetAcquisition::factory()->make(['quantity' => new Quantity($quantity)]);
    }

    $transactions = SharePoolingAssetTransactions::make(...$items);

    expect($transactions->quantity())->toBeInstanceOf(Quantity::class)->toEqual(new Quantity($total));
})->with([
    'scenario 1' => [['10'], '10'],
    'scenario 2' => [['10', '30', '40', '20'], '100'],
    'scenario 3' => [['1.12345678', '1.123456789'], '2.246913569'],
]);

it('can return a collection of transactions that happened on a specific day', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    )->madeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 2],
    'scenario 2' => ['2015-10-22', 3],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of acquisitions that happened on a specific day', function (string $date, int $count) {
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    )->acquisitionsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 1],
    'scenario 2' => ['2015-10-22', 2],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of disposals that happened on a specific day', function (string $date, int $count) {
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
    )->disposalsMadeOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 1],
    'scenario 2' => ['2015-10-22', 2],
    'scenario 3' => ['2015-10-23', 0],
    'scenario 4' => ['2015-10-24', 1],
]);

it('can return a collection of transactions that happened between two dates', function (string $date1, string $date2, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeBetween(Localdate::parse($date1), Localdate::parse($date2));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', '2015-10-25', 6],
    'scenario 2' => ['2015-10-21', '2015-10-23', 4],
    'scenario 3' => ['2015-10-23', '2015-10-25', 3],
    'scenario 4' => ['2015-10-24', '2015-10-25', 2],
    'scenario 5' => ['2015-10-25', '2015-10-21', 6],
    'scenario 6' => ['2015-10-26', '2015-10-27', 0],
    'scenario 7' => ['2015-10-21', '2015-10-21', 2],
]);

it('can return a collection of acquisitions that happened between two dates', function (string $date1, string $date2, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeBetween(Localdate::parse($date1), Localdate::parse($date2));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetAcquisitions::class);
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
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeBetween(Localdate::parse($date1), Localdate::parse($date2));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetDisposals::class);
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
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeBefore(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 5],
    'scenario 2' => ['2015-10-21', 0],
    'scenario 3' => ['2015-10-22', 2],
]);

it('can return a collection of acquisitions that happened before a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeBefore(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 3],
    'scenario 2' => ['2015-10-21', 0],
    'scenario 3' => ['2015-10-22', 1],
]);

it('can return a collection of disposals that happened before a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeBefore(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 2],
    'scenario 2' => ['2015-10-21', 0],
    'scenario 3' => ['2015-10-22', 1],
]);

it('can return a collection of transactions that happened before a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeBeforeOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 6],
    'scenario 2' => ['2015-10-21', 2],
    'scenario 3' => ['2015-10-22', 3],
    'scenario 4' => ['2015-10-20', 0],
]);

it('can return a collection of acquisitions that happened before a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeBeforeOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 3],
    'scenario 2' => ['2015-10-21', 1],
    'scenario 3' => ['2015-10-22', 2],
    'scenario 4' => ['2015-10-20', 0],
]);

it('can return a collection of disposals that happened before a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeBeforeOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-25', 3],
    'scenario 2' => ['2015-10-21', 1],
    'scenario 3' => ['2015-10-22', 1],
    'scenario 4' => ['2015-10-20', 0],
]);

it('can return a collection of transactions that happened after a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeAfter(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 4],
    'scenario 2' => ['2015-10-25', 0],
    'scenario 3' => ['2015-10-22', 3],
]);

it('can return a collection of acquisitions that happened after a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeAfter(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 2],
    'scenario 2' => ['2015-10-25', 0],
    'scenario 3' => ['2015-10-22', 1],
]);

it('can return a collection of disposals that happened after a date (exclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeAfter(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 2],
    'scenario 2' => ['2015-10-25', 0],
    'scenario 3' => ['2015-10-22', 2],
]);

it('can return a collection of transactions that happened after a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->madeAfterOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetTransactions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 6],
    'scenario 2' => ['2015-10-25', 1],
    'scenario 3' => ['2015-10-22', 4],
    'scenario 4' => ['2015-10-26', 0],
]);

it('can return a collection of acquisitions that happened after a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->acquisitionsMadeAfterOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetAcquisitions::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 3],
    'scenario 2' => ['2015-10-24', 1],
    'scenario 3' => ['2015-10-22', 2],
    'scenario 4' => ['2015-10-26', 0],
]);

it('can return a collection of disposals that happened after a date (inclusive)', function (string $date, int $count) {
    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-21')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-22')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-23')]),
        SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2015-10-24')]),
        SharePoolingAssetDisposal::factory()->make(['date' => LocalDate::parse('2015-10-25')]),
    )->disposalsMadeAfterOrOn(Localdate::parse($date));

    expect($transactions)->toBeInstanceOf(SharePoolingAssetDisposals::class);
    expect($transactions->count())->toEqual($count);
})->with([
    'scenario 1' => ['2015-10-21', 3],
    'scenario 2' => ['2015-10-25', 1],
    'scenario 3' => ['2015-10-22', 2],
    'scenario 4' => ['2015-10-26', 0],
]);

it('can return a collection of disposals with 30-day quantity allocated to an acquisition', function () {
    /** @var SharePoolingAssetAcquisition */
    $acquisition1 = SharePoolingAssetAcquisition::factory()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'quantity' => new Quantity('110'),
        'sameDayQuantity' => new Quantity('10'),
        'thirtyDayQuantity' => new Quantity('100'),
    ]);

    /** @var SharePoolingAssetTransactions */
    $transactions = SharePoolingAssetTransactions::make(
        $acquisition1,
        $disposal1 = SharePoolingAssetDisposal::factory()->withThirtyDayQuantity(new Quantity('30'), id: $acquisition1->id)->make(),
        SharePoolingAssetDisposal::factory()->withSameDayQuantity(new Quantity('10'), id: $acquisition1->id)->make(),
        $acquisition2 = SharePoolingAssetAcquisition::factory()->make(),
        SharePoolingAssetDisposal::factory()->withThirtyDayQuantity(new Quantity('20'), id: $acquisition2->id)->make(),
        $disposal2 = SharePoolingAssetDisposal::factory()->withThirtyDayQuantity(new Quantity('70'), id: $acquisition1->id)->make(),
        SharePoolingAssetDisposal::factory()->make(),
    )->disposalsWithThirtyDayQuantityAllocatedTo($acquisition1);

    expect($transactions)->toBeInstanceOf(SharePoolingAssetDisposals::class);
    expect($transactions->count())->toEqual(2);
    expect($transactions->getIterator()[0])->toBe($disposal1);
    expect($transactions->getIterator()[1])->toBe($disposal2);
});
