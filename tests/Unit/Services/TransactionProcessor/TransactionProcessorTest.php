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
        'Date' => '21/10/2015',
        'Operation' => 'receive',
        'Market value' => '1000',
        'Sent asset' => '',
        'Sent quantity' => '',
        'Sent asset is non-fungible' => 'FALSE',
        'Received asset' => 'BTC',
        'Received quantity' => '1',
        'Received asset is non-fungible' => 'FALSE',
        'Fee currency' => 'GBP',
        'Fee quantity' => '20',
        'Fee market value' => '20',
        'Income' => 'TRUE',
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
        'Date' => '21/10/2015',
        'Operation' => 'send',
        'Market value' => '1000',
        'Sent asset' => 'BTC',
        'Sent quantity' => '1',
        'Sent asset is non-fungible' => 'FALSE',
        'Received asset' => '',
        'Received quantity' => '',
        'Received asset is non-fungible' => 'FALSE',
        'Fee currency' => 'BTC',
        'Fee quantity' => '0.001',
        'Fee market value' => '10',
        'Income' => 'FALSE',
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
        'Date' => '21/10/2015',
        'Operation' => 'swap',
        'Market value' => '1000',
        'Sent asset' => 'ETH',
        'Sent quantity' => '5',
        'Sent asset is non-fungible' => 'FALSE',
        'Received asset' => 'BTC',
        'Received quantity' => '1',
        'Received asset is non-fungible' => 'FALSE',
        'Fee currency' => '',
        'Fee quantity' => '',
        'Fee market value' => '',
        'Income' => 'FALSE',
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
        'Date' => '21/10/2015',
        'Operation' => 'transfer',
        'Market value' => '',
        'Sent asset' => '0x123456789',
        'Sent quantity' => '1',
        'Sent asset is non-fungible' => 'TRUE',
        'Received asset' => '',
        'Received quantity' => '',
        'Received asset is non-fungible' => 'FALSE',
        'Fee currency' => '',
        'Fee quantity' => '',
        'Fee market value' => '',
        'Income' => 'FALSE',
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
