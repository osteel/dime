<?php

use Domain\Aggregates\SharePoolingAsset\Entities\Exceptions\SharePoolingAssetAcquisitionException;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\Exceptions\QuantityAdjusterException;
use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\QuantityAdjuster;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\Quantity;

it('cannot process a disposal because an acquisition cannot be found', function (string $method) {
    $disposal = SharePoolingAssetDisposal::factory()
        ->withSameDayQuantity(Quantity::zero(), $id = SharePoolingAssetTransactionId::fromString('foo'))
        ->make();

    expect(fn () => QuantityAdjuster::$method($disposal, SharePoolingAssetTransactions::make()))
        ->toThrow(QuantityAdjusterException::class, QuantityAdjusterException::transactionNotFound($id)->getMessage());
})->with(['applyDisposal', 'revertDisposal']);

it('cannot process a disposal because a transaction is not an acquisition', function (string $method) {
    $disposal = SharePoolingAssetDisposal::factory()
        ->withSameDayQuantity(Quantity::zero(), $id = SharePoolingAssetTransactionId::fromString('foo'))
        ->make();

    $transactions = SharePoolingAssetTransactions::make(SharePoolingAssetDisposal::factory()->make(['id' => $id]));

    expect(fn () => QuantityAdjuster::$method($disposal, $transactions))
        ->toThrow(QuantityAdjusterException::class, QuantityAdjusterException::notAnAcquisition($id)->getMessage());
})->with(['applyDisposal', 'revertDisposal']);

it('cannot apply a disposal because of insufficient available quantity to increase', function (
    string $method,
    string $quantity,
    string $exception
) {
    $disposal = SharePoolingAssetDisposal::factory()
        ->{$method}(new Quantity($quantity), $id = SharePoolingAssetTransactionId::fromString('foo'))
        ->make();

    $transactions = SharePoolingAssetTransactions::make(SharePoolingAssetAcquisition::factory()->make([
        'id' => $id,
        'quantity' => $available = new Quantity('10'),
    ]));

    expect(fn () => QuantityAdjuster::applyDisposal($disposal, $transactions))->toThrow(
        SharePoolingAssetAcquisitionException::class,
        SharePoolingAssetAcquisitionException::$exception(new Quantity($quantity), $available)->getMessage(),
    );
})->with([
    'same-day' => ['withSameDayQuantity', '11', 'insufficientSameDayQuantityToIncrease'],
    '30-day' => ['withThirtyDayQuantity', '11', 'insufficientThirtyDayQuantityToIncrease'],
]);

it('can apply a disposal', function (string $factoryMethod, string $method) {
    $disposal = SharePoolingAssetDisposal::factory()
        ->{$factoryMethod}(new Quantity('10'), $id = SharePoolingAssetTransactionId::fromString('foo'))
        ->make();

    $transactions = SharePoolingAssetTransactions::make(SharePoolingAssetAcquisition::factory()->make([
        'id' => $id,
        'quantity' => new Quantity('10'),
    ]));

    expect((string) $transactions->first()->{$method}())->toBe('0');

    QuantityAdjuster::applyDisposal($disposal, $transactions);

    expect((string) $transactions->first()->{$method}())->toBe('10');
})->with([
    'same-day' => ['withSameDayQuantity', 'sameDayQuantity'],
    '30-day' => ['withThirtyDayQuantity', 'thirtyDayQuantity'],
]);

it('cannot revert a disposal because of insufficient available quantity to decrease', function (
    string $method,
    string $quantity,
    string $exception
) {
    $disposal = SharePoolingAssetDisposal::factory()
        ->{$method}(new Quantity($quantity), $id = SharePoolingAssetTransactionId::fromString('foo'))
        ->make();

    $transactions = SharePoolingAssetTransactions::make(SharePoolingAssetAcquisition::factory()->make(['id' => $id]));

    expect(fn () => QuantityAdjuster::revertDisposal($disposal, $transactions))->toThrow(
        SharePoolingAssetAcquisitionException::class,
        SharePoolingAssetAcquisitionException::$exception(new Quantity($quantity), Quantity::zero())->getMessage(),
    );
})->with([
    'same-day' => ['withSameDayQuantity', '1', 'insufficientSameDayQuantityToDecrease'],
    '30-day' => ['withThirtyDayQuantity', '1', 'insufficientThirtyDayQuantityToDecrease'],
]);

it('can revert a disposal', function (string $factoryMethod, string $method) {
    $disposal = SharePoolingAssetDisposal::factory()
        ->{$factoryMethod}(new Quantity('10'), $id = SharePoolingAssetTransactionId::fromString('foo'))
        ->make();

    $transactions = SharePoolingAssetTransactions::make(SharePoolingAssetAcquisition::factory()->make([
        'id' => $id,
        'sameDayQuantity' => new Quantity('10'),
        'thirtyDayQuantity' => new Quantity('10'),
    ]));

    expect((string) $transactions->first()->{$method}())->toBe('10');

    QuantityAdjuster::revertDisposal($disposal, $transactions);

    expect((string) $transactions->first()->{$method}())->toBe('0');
})->with([
    'same-day' => ['withSameDayQuantity', 'sameDayQuantity'],
    '30-day' => ['withThirtyDayQuantity', 'thirtyDayQuantity'],
]);
