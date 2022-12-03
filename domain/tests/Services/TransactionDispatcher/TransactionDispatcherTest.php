<?php

use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\Services\TransactionDispatcher\Handlers\NftHandler;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingHandler;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\Services\TransactionDispatcher\TransactionDispatcher;
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

    $this->incomeHandler->shouldHaveReceived('handle')->once()->with($transaction);
    $this->transferHandler->shouldNotHaveReceived('handle');
    $this->nftHandler->shouldNotHaveReceived('handle');
    $this->sharePoolingHandler->shouldHaveReceived('handle')->once()->with($transaction);
});

it('can dispatch to the transfer handler', function () {
    $transaction = Transaction::factory()->transfer()->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldNotHaveReceived('handle');
    $this->transferHandler->shouldHaveReceived('handle')->once()->with($transaction);
    $this->nftHandler->shouldNotHaveReceived('handle');
    $this->sharePoolingHandler->shouldNotHaveReceived('handle');
});

it('can dispatch to the NFT handler', function (string $method, bool $sharePoolingHandler) {
    $transaction = Transaction::factory()->$method()->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldNotHaveReceived('handle');
    $this->transferHandler->shouldNotHaveReceived('handle');
    $this->nftHandler->shouldHaveReceived('handle')->once()->with($transaction);

    if ($sharePoolingHandler) {
        $this->sharePoolingHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->sharePoolingHandler->shouldNotHaveReceived('handle');
    }
})->with([
    'send NFT' => ['sendNft', true],
    'receive NFT' => ['receiveNft', true],
    'acquire NFT' => ['swapToNft', true],
    'dispose of NFT' => ['swapFromNft', true],
    'swap NFTs' => ['swapNfts', false],
]);

it('can dispatch to the share pooling handler', function (string $method, bool $nftHandler) {
    $transaction = Transaction::factory()->$method()->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    $this->transferHandler->shouldNotHaveReceived('handle');

    if ($nftHandler) {
        $this->nftHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->nftHandler->shouldNotHaveReceived('handle');
    }

    $this->sharePoolingHandler->shouldHaveReceived('handle')->once()->with($transaction);
})->with([
    'send' => ['send', false],
    'receive' => ['receive', false],
    'swap' => ['swap', false],
    'income' => ['income', false],
    'send NFT' => ['sendNft', true],
    'receive NFT' => ['receiveNft', true],
    'acquire NFT' => ['swapToNft', true],
    'dispose of NFT' => ['swapFromNft', true],
]);

it('can dispatch the transaction fee to the share pooling handler', function (string $method, bool $sharePoolingHandler, bool $nftHandler) {
    /** @var Transaction */
    $transaction = Transaction::factory()->$method()->withTransactionFee()->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    if ($method === 'transfer') {
        $this->transferHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->transferHandler->shouldNotHaveReceived('handle');
    }

    if ($nftHandler) {
        $this->nftHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->nftHandler->shouldNotHaveReceived('handle');
    }

    if ($sharePoolingHandler) {
        $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction);
    }

    $this->sharePoolingHandler->shouldHaveReceived('handle')
        ->withArgs(function (Transaction $feeTransaction) use ($transaction) {
            return $feeTransaction->isSend()
                && $feeTransaction->costBasis->isEqualTo($transaction->transactionFeeCostBasis);
        });
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

it('can dispatch the exchange fee to the share pooling handler', function (string $method, bool $sharePoolingHandler, bool $nftHandler) {
    /** @var Transaction */
    $transaction = Transaction::factory()->$method()->withExchangeFee()->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    if ($method === 'transfer') {
        $this->transferHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->transferHandler->shouldNotHaveReceived('handle');
    }

    if ($nftHandler) {
        $this->nftHandler->shouldHaveReceived('handle')->once()->with($transaction);
    } else {
        $this->nftHandler->shouldNotHaveReceived('handle');
    }

    if ($sharePoolingHandler) {
        $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction);
    }

    $this->sharePoolingHandler->shouldHaveReceived('handle')
        ->withArgs(function (Transaction $feeTransaction) use ($transaction) {
            return $feeTransaction->isSend()
                && $feeTransaction->costBasis->isEqualTo($transaction->exchangeFeeCostBasis);
        });
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
