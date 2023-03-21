<?php

use App\Services\TransactionProcessor\TransactionProcessor;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\TransactionDispatcher;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

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
        'Sent asset is NFT' => 'FALSE',
        'Received asset' => 'BTC',
        'Received quantity' => '1',
        'Received asset is NFT' => 'FALSE',
        'Fee currency' => 'GBP',
        'Fee quantity' => '20',
        'Fee market value' => '20',
        'Income' => 'TRUE',
    ];

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Transaction $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->isReceive()
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->receivedAsset === 'BTC'
            && $transaction->receivedQuantity->isEqualTo('1')
            && $transaction->receivedAssetIsNft === false
            && $transaction->feeCurrency === 'GBP'
            && $transaction->feeIsFiat()
            && $transaction->feeQuantity->isEqualTo('20')
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
        'Sent asset is NFT' => 'FALSE',
        'Received asset' => '',
        'Received quantity' => '',
        'Received asset is NFT' => 'FALSE',
        'Fee currency' => 'BTC',
        'Fee quantity' => '0.001',
        'Fee market value' => '10',
        'Income' => 'FALSE',
    ];

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Transaction $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->isSend()
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->sentAsset === 'BTC'
            && $transaction->sentQuantity->isEqualTo('1')
            && $transaction->sentAssetIsNft === false
            && $transaction->feeCurrency === 'BTC'
            && $transaction->feeIsFiat() === false
            && $transaction->feeQuantity->isEqualTo('0.001')
            && $transaction->isIncome === false,
    )->once();
});

it('can parse a swap transaction and pass it on to the dispatcher', function () {
    $transaction = [
        'Date' => '21/10/2015',
        'Operation' => 'swap',
        'Market value' => '1000',
        'Sent asset' => 'ETH',
        'Sent quantity' => '5',
        'Sent asset is NFT' => 'FALSE',
        'Received asset' => 'BTC',
        'Received quantity' => '1',
        'Received asset is NFT' => 'FALSE',
        'Fee currency' => '',
        'Fee quantity' => '',
        'Fee market value' => '',
        'Income' => 'FALSE',
    ];

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Transaction $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->isSwap()
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->sentAsset === 'ETH'
            && $transaction->sentQuantity->isEqualTo('5')
            && $transaction->sentAssetIsNft === false
            && $transaction->receivedAsset === 'BTC'
            && $transaction->receivedQuantity->isEqualTo('1')
            && $transaction->receivedAssetIsNft === false
            && is_null($transaction->feeCurrency)
            && $transaction->isIncome === false,
    )->once();
});

it('can parse a transfer transaction and pass it on to the dispatcher', function () {
    $transaction = [
        'Date' => '21/10/2015',
        'Operation' => 'transfer',
        'Market value' => '1000',
        'Sent asset' => '0x123456789',
        'Sent quantity' => '1',
        'Sent asset is NFT' => 'TRUE',
        'Received asset' => '',
        'Received quantity' => '',
        'Received asset is NFT' => 'FALSE',
        'Fee currency' => '',
        'Fee quantity' => '',
        'Fee market value' => '',
        'Income' => 'FALSE',
    ];

    $this->transactionProcessor->process($transaction);

    $this->transactionDispatcher->shouldHaveReceived(
        'dispatch',
        fn (Transaction $transaction) => $transaction->date->__toString() === '2015-10-21'
            && $transaction->isTransfer()
            && $transaction->marketValue->isEqualTo(new FiatAmount('1000', FiatCurrency::GBP))
            && $transaction->sentAsset === '0X123456789'
            && $transaction->sentQuantity->isEqualTo('1')
            && $transaction->sentAssetIsNft === true
            && is_null($transaction->feeCurrency)
            && $transaction->isIncome === false,
    )->once();
});
