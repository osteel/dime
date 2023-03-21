<?php

use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\Services\TransactionDispatcher\Handlers\NftHandler;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingHandler;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\Services\TransactionDispatcher\TransactionDispatcher;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->incomeHandler = Mockery::spy(IncomeHandler::class);
    $this->transferHandler = Mockery::spy(TransferHandler::class);
    $this->nftHandler = Mockery::spy(NftHandler::class);
    $this->sharePoolingHandler = Mockery::spy(SharePoolingHandler::class);

    $this->transactionDispatcher = new TransactionDispatcher(
        $this->incomeHandler,
        $this->transferHandler,
        $this->nftHandler,
        $this->sharePoolingHandler,
    );
});

it('can dispatch to the income handler', function () {
    $transaction = Transaction::factory()->income()->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    $this->transferHandler->shouldNotHaveReceived('handle');
    $this->nftHandler->shouldNotHaveReceived('handle');
    $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
});

it('can dispatch to the transfer handler', function (string $method) {
    $transaction = Transaction::factory()->$method()->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldNotHaveReceived('handle');
    $this->transferHandler->shouldHaveReceived('handle')->with($transaction)->once();
    $this->nftHandler->shouldNotHaveReceived('handle');
    $this->sharePoolingHandler->shouldNotHaveReceived('handle');
})->with([
    'transfer' => ['transfer'],
    'transfer NFT' => ['transferNft'],
]);

it('can dispatch to the NFT handler', function (string $method, bool $sharePoolingHandler) {
    $transaction = Transaction::factory()->$method()->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldNotHaveReceived('handle');
    $this->transferHandler->shouldNotHaveReceived('handle');
    $this->nftHandler->shouldHaveReceived('handle')->with($transaction)->once();

    if ($sharePoolingHandler) {
        $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->sharePoolingHandler->shouldNotHaveReceived('handle');
    }
})->with([
    'send NFT' => ['sendNft', false],
    'receive NFT' => ['receiveNft', false],
    'acquire NFT' => ['swapToNft', true],
    'dispose of NFT' => ['swapFromNft', true],
    'swap NFTs' => ['swapNfts', false],
]);

it('can dispatch to the share pooling handler', function (string $method, bool $nftHandler) {
    $transaction = Transaction::factory()->$method()->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    $this->transferHandler->shouldNotHaveReceived('handle');

    if ($nftHandler) {
        $this->nftHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->nftHandler->shouldNotHaveReceived('handle');
    }

    $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
})->with([
    'send' => ['send', false],
    'receive' => ['receive', false],
    'swap' => ['swap', false],
    'income' => ['income', false],
    'acquire NFT' => ['swapToNft', true],
    'dispose of NFT' => ['swapFromNft', true],
]);

it('can dispatch the fee to the share pooling handler', function (string $method, bool $sharePoolingHandler, bool $nftHandler) {
    /** @var Transaction */
    $transaction = Transaction::factory()->$method()->withFee()->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    if ($method === 'transfer') {
        $this->transferHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->transferHandler->shouldNotHaveReceived('handle');
    }

    if ($nftHandler) {
        $this->nftHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->nftHandler->shouldNotHaveReceived('handle');
    }

    if ($sharePoolingHandler) {
        $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
    }

    $this->sharePoolingHandler->shouldHaveReceived(
        'handle',
        fn (Transaction $feeTransaction) => $feeTransaction->isSend()
            && $feeTransaction->marketValue->isEqualTo($transaction->feeMarketValue),
    )->once();
})->with([
    'send' => ['send', true, false],
    'receive' => ['receive', true, false],
    'swap' => ['swap', true, false],
    'income' => ['income', true, false],
    'transfer' => ['transfer', false, false],
    'send NFT' => ['sendNft', false, true],
    'receive NFT' => ['receiveNft', false, true],
    'acquire NFT' => ['swapToNft', false, true],
    'dispose of NFT' => ['swapFromNft', false, true],
]);

it('does not dispatch the fee to the share pooling handler when it is zero', function (string $method, bool $sharePoolingHandler, bool $nftHandler) {
    /** @var Transaction */
    $transaction = Transaction::factory()->$method()->withFee(FiatAmount::GBP('0'))->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    if ($method === 'transfer') {
        $this->transferHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->transferHandler->shouldNotHaveReceived('handle');
    }

    if ($nftHandler) {
        $this->nftHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->nftHandler->shouldNotHaveReceived('handle');
    }

    if ($sharePoolingHandler) {
        $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
    }

    $this->sharePoolingHandler->shouldNotHaveReceived(
        'handle',
        fn (Transaction $feeTransaction) => $feeTransaction->isSend()
            && $feeTransaction->marketValue->isEqualTo($transaction->feeMarketValue),
    );
})->with([
    'send' => ['send', true, false],
    'receive' => ['receive', true, false],
    'swap' => ['swap', true, false],
    'income' => ['income', true, false],
    'transfer' => ['transfer', false, false],
    'send NFT' => ['sendNft', false, true],
    'receive NFT' => ['receiveNft', false, true],
    'acquire NFT' => ['swapToNft', false, true],
    'dispose of NFT' => ['swapFromNft', false, true],
]);

it('does not dispatch the fee to the share pooling handler when it is in fiat', function (string $method, bool $sharePoolingHandler, bool $nftHandler) {
    /** @var Transaction */
    $transaction = Transaction::factory()->$method()->withFeeInFiat()->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    if ($method === 'transfer') {
        $this->transferHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->transferHandler->shouldNotHaveReceived('handle');
    }

    if ($nftHandler) {
        $this->nftHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->nftHandler->shouldNotHaveReceived('handle');
    }

    if ($sharePoolingHandler) {
        $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
    }

    $this->sharePoolingHandler->shouldNotHaveReceived(
        'handle',
        fn (Transaction $feeTransaction) => $feeTransaction->isSend()
            && $feeTransaction->marketValue->isEqualTo($transaction->feeMarketValue),
    );
})->with([
    'send' => ['send', true, false],
    'receive' => ['receive', true, false],
    'swap' => ['swap', true, false],
    'income' => ['income', true, false],
    'transfer' => ['transfer', false, false],
    'send NFT' => ['sendNft', false, true],
    'receive NFT' => ['receiveNft', false, true],
    'acquire NFT' => ['swapToNft', false, true],
    'dispose of NFT' => ['swapFromNft', false, true],
]);
