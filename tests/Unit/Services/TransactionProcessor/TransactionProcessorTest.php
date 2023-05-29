<?php

use App\Services\TransactionProcessor\Exceptions\TransactionProcessorException;
use App\Services\TransactionProcessor\TransactionProcessor;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\TransactionDispatcherContract;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;
use Domain\ValueObjects\Transactions\Transfer;

beforeEach(function () {
    $this->transactionDispatcher = Mockery::spy(TransactionDispatcherContract::class);
    $this->transactionProcessor = new TransactionProcessor($this->transactionDispatcher);
});

function makeReceive(): array
{
    return [
        'date' => '21/10/2015',
        'operation' => 'receive',
        'market_value' => '1000',
        'sent_asset' => '',
        'sent_quantity' => '',
        'sent_asset_is_non_fungible' => 'FALSE',
        'received_asset' => 'BTC',
        'received_quantity' => '1',
        'received_asset_is_non_fungible' => 'FALSE',
        'fee_currency' => 'GBP',
        'fee_quantity' => '20',
        'fee_market_value' => '20',
        'income' => 'TRUE',
    ];
}

function makeSend(): array
{
    return [
        'date' => '21/10/2015',
        'operation' => 'send',
        'market_value' => '1000',
        'sent_asset' => 'BTC',
        'sent_quantity' => '1',
        'sent_asset_is_non_fungible' => 'FALSE',
        'received_asset' => '',
        'received_quantity' => '',
        'received_asset_is_non_fungible' => 'FALSE',
        'fee_currency' => 'BTC',
        'fee_quantity' => '0.001',
        'fee_market_value' => '10',
        'income' => 'FALSE',
    ];
}

function makeSwap(): array
{
    return [
        'date' => '21/10/2015',
        'operation' => 'swap',
        'market_value' => '1000',
        'sent_asset' => 'ETH',
        'sent_quantity' => '5',
        'sent_asset_is_non_fungible' => 'FALSE',
        'received_asset' => 'BTC',
        'received_quantity' => '1',
        'received_asset_is_non_fungible' => 'FALSE',
        'fee_currency' => '',
        'fee_quantity' => '',
        'fee_market_value' => '',
        'income' => 'FALSE',
    ];
}

function makeTransfer(): array
{
    return [
        'date' => '21/10/2015',
        'operation' => 'transfer',
        'market_value' => '',
        'sent_asset' => '0x123456789',
        'sent_quantity' => '1',
        'sent_asset_is_non_fungible' => 'TRUE',
        'received_asset' => '',
        'received_quantity' => '',
        'received_asset_is_non_fungible' => 'FALSE',
        'fee_currency' => '',
        'fee_quantity' => '',
        'fee_market_value' => '',
        'income' => 'FALSE',
    ];
}

it('can parse a receive transaction and pass it on to the dispatcher', function () {
    $transaction = makeReceive();

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Acquisition $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->asset->symbol === 'BTC'
            && $transaction->quantity->isEqualTo('1')
            && $transaction->asset->isNonFungible === false
            && (string) $transaction->fee->currency === 'GBP'
            && $transaction->fee->isFiat()
            && $transaction->fee->quantity->isEqualTo('20')
            && $transaction->isIncome === true,
    )->once();
});

it('can parse a send transaction and pass it on to the dispatcher', function () {
    $transaction = makeSend();

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Disposal $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->asset->symbol === 'BTC'
            && $transaction->quantity->isEqualTo('1')
            && $transaction->asset->isNonFungible === false
            && (string) $transaction->fee->currency === 'BTC'
            && $transaction->fee->isFiat() === false
            && $transaction->fee->quantity->isEqualTo('0.001'),
    )->once();
});

it('can parse a swap transaction and pass it on to the dispatcher', function () {
    $transaction = makeSwap();

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Swap $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->disposedOfAsset->symbol === 'ETH'
            && $transaction->disposedOfQuantity->isEqualTo('5')
            && $transaction->disposedOfAsset->isNonFungible === false
            && $transaction->acquiredAsset->symbol === 'BTC'
            && $transaction->acquiredQuantity->isEqualTo('1')
            && $transaction->acquiredAsset->isNonFungible === false
            && is_null($transaction->fee)
    )->once();
});

it('can parse a transfer transaction and pass it on to the dispatcher', function () {
    $transaction = makeTransfer();

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Transfer $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->asset->symbol === '0x123456789'
            && $transaction->quantity->isEqualTo('1')
            && $transaction->asset->isNonFungible === true
            && is_null($transaction->fee),
    )->once();
});

it('cannot parse a transaction because the date is invalid', function (string $operation) {
    $transaction = array_merge($operation(), ['date' => 'foo']);

    expect(fn () => $this->transactionProcessor->process($transaction))
        ->toThrow(TransactionProcessorException::class, TransactionProcessorException::cannotParseDate('foo')->getMessage());
})->with([
    'receive' => 'makeReceive',
    'send' => 'makeSend',
    'swap' => 'makeSwap',
    'transfer' => 'makeTransfer',
]);

it('cannot parse a transaction because the asset is invalid', function (string $operation, string $property) {
    $transaction = array_merge($operation(), [$property => '']);

    expect(fn () => $this->transactionProcessor->process($transaction))
        ->toThrow(TransactionProcessorException::class, TransactionProcessorException::invalidAsset('')->getMessage());
})->with([
    'receive' => ['makeReceive', 'received_asset'],
    'send' => ['makeSend', 'sent_asset'],
    'swap 1' => ['makeSwap', 'received_asset'],
    'swap 2' => ['makeSwap', 'sent_asset'],
    'transfer' => ['makeTransfer', 'sent_asset'],
]);

it('cannot parse a transaction because the fiat amount is invalid', function (string $operation) {
    $transaction = array_merge($operation(), ['market_value' => '']);

    expect(fn () => $this->transactionProcessor->process($transaction))
        ->toThrow(TransactionProcessorException::class, TransactionProcessorException::invalidAmount('')->getMessage());
})->with([
    'receive' => 'makeReceive',
    'send' => 'makeSend',
    'swap' => 'makeSwap',
]);
