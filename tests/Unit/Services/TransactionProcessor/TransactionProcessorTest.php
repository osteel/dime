<?php

use App\Services\TransactionProcessor\TransactionProcessor;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\TransactionDispatcher;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;
use Domain\ValueObjects\Transactions\Transfer;

beforeEach(function () {
    $this->transactionDispatcher = Mockery::spy(TransactionDispatcher::class);
    $this->transactionProcessor = new TransactionProcessor($this->transactionDispatcher);
});

it('can parse a receive transaction and pass it on to the dispatcher', function () {
    $transaction = [
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

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Acquisition $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->asset->symbol === 'BTC'
            && $transaction->quantity->isEqualTo('1')
            && $transaction->asset->isNonFungibleAsset === false
            && (string) $transaction->fee->currency === 'GBP'
            && $transaction->fee->isFiat()
            && $transaction->fee->quantity->isEqualTo('20')
            && $transaction->isIncome === true,
    )->once();
});

it('can parse a send transaction and pass it on to the dispatcher', function () {
    $transaction = [
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

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Disposal $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->asset->symbol === 'BTC'
            && $transaction->quantity->isEqualTo('1')
            && $transaction->asset->isNonFungibleAsset === false
            && (string) $transaction->fee->currency === 'BTC'
            && $transaction->fee->isFiat() === false
            && $transaction->fee->quantity->isEqualTo('0.001'),
    )->once();
});

it('can parse a swap transaction and pass it on to the dispatcher', function () {
    $transaction = [
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

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Swap $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->disposedOfAsset->symbol === 'ETH'
            && $transaction->disposedOfQuantity->isEqualTo('5')
            && $transaction->disposedOfAsset->isNonFungibleAsset === false
            && $transaction->acquiredAsset->symbol === 'BTC'
            && $transaction->acquiredQuantity->isEqualTo('1')
            && $transaction->acquiredAsset->isNonFungibleAsset === false
            && is_null($transaction->fee)
    )->once();
});

it('can parse a transfer transaction and pass it on to the dispatcher', function () {
    $transaction = [
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

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Transfer $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->asset->symbol === '0x123456789'
            && $transaction->quantity->isEqualTo('1')
            && $transaction->asset->isNonFungibleAsset === true
            && is_null($transaction->fee),
    )->once();
});
