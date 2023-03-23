<?php

use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\Services\TransactionDispatcher\Handlers\NftHandler;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingHandler;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\Services\TransactionDispatcher\TransactionDispatcher;
use Domain\Tests\Factories\ValueObjects\Transactions\TransactionFactory;
use Domain\Tests\Factories\ValueObjects\Transactions\TransferFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;
use Domain\ValueObjects\Transactions\Transaction;
use Domain\ValueObjects\Transactions\Transfer;

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
    $transaction = Acquisition::factory()->income()->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    $this->transferHandler->shouldNotHaveReceived('handle');
    $this->nftHandler->shouldNotHaveReceived('handle');
    $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
});

it('can dispatch to the transfer handler', function (bool $isNft) {
    $transaction = Transfer::factory()->when($isNft, fn ($factory) => $factory->nft())->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldNotHaveReceived('handle');
    $this->transferHandler->shouldHaveReceived('handle')->with($transaction)->once();
    $this->nftHandler->shouldNotHaveReceived('handle');
    $this->sharePoolingHandler->shouldNotHaveReceived('handle');
})->with([
    'share pooling asset' => false,
    'NFT' => true,
]);

it('can dispatch to the NFT handler', function (TransactionFactory $factory, string $method, bool $sharePoolingHandler) {
    $transaction = $factory->$method()->make();

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
    'dispose of NFT' => [Disposal::factory(), 'nft', false],
    'acquire NFT' => [Acquisition::factory(), 'nft', false],
    'swap to NFT' => [Swap::factory(), 'toNft', true],
    'swap from NFT' => [Swap::factory(), 'fromNft', true],
    'swap NFTs' => [Swap::factory(), 'nfts', false],
]);

it('can dispatch to the share pooling handler', function (TransactionFactory $factory, ?string $method, bool $nftHandler) {
    $transaction = $factory->when($method, fn ($factory) => $factory->$method())->make();

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
    'dispose of' => [Disposal::factory(), null, false],
    'acquire' => [Acquisition::factory(), null, false],
    'swap' => [Swap::factory(), null, false],
    'income' => [Acquisition::factory(), 'income', false],
    'swap to NFT' => [Swap::factory(), 'toNft', true],
    'swap from NFT' => [Swap::factory(), 'fromNft', true],
]);

it('can dispatch the fee to the share pooling handler', function (TransactionFactory $factory, ?string $method, bool $sharePoolingHandler, bool $nftHandler) {
    /** @var Transaction */
    $transaction = $factory->when($method, fn ($factory) => $factory->$method())->withFee()->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    if ($factory instanceof TransferFactory) {
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
        fn (Transaction $feeTransaction) => $feeTransaction instanceof Disposal
            && $feeTransaction->marketValue->isEqualTo($transaction->fee->marketValue),
    )->once();
})->with([
    'dispose of' => [Disposal::factory(), null, true, false],
    'acquire' => [Acquisition::factory(), null, true, false],
    'swap' => [Swap::factory(), null, true, false],
    'income' => [Acquisition::factory(), 'income', true, false],
    'transfer' => [Transfer::factory(), null, false, false],
    'dispose of NFT' => [Disposal::factory(), 'nft', false, true],
    'acquire NFT' => [Acquisition::factory(), 'nft', false, true],
    'swap to NFT' => [Swap::factory(), 'toNft', false, true],
    'swap from NFT' => [Swap::factory(), 'fromNft', false, true],
]);

it('does not dispatch the fee to the share pooling handler when it is zero', function (TransactionFactory $factory, ?string $method, bool $sharePoolingHandler, bool $nftHandler) {
    /** @var Transaction */
    $transaction = $factory->when($method, fn ($factory) => $factory->$method())->withFee(FiatAmount::GBP('0'))->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    if ($factory instanceof TransferFactory) {
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
        fn (Transaction $feeTransaction) => $feeTransaction instanceof Disposal
            && $feeTransaction->marketValue->isEqualTo($transaction->fee->marketValue),
    );
})->with([
    'dispose of' => [Disposal::factory(), null, true, false],
    'acquire' => [Acquisition::factory(), null, true, false],
    'swap' => [Swap::factory(), null, true, false],
    'income' => [Acquisition::factory(), 'income', true, false],
    'transfer' => [Transfer::factory(), null, false, false],
    'dispose of NFT' => [Disposal::factory(), 'nft', false, true],
    'acquire NFT' => [Acquisition::factory(), 'nft', false, true],
    'swap to NFT' => [Swap::factory(), 'toNft', false, true],
    'swap from NFT' => [Swap::factory(), 'fromNft', false, true],
]);

it('does not dispatch the fee to the share pooling handler when it is fiat', function (TransactionFactory $factory, ?string $method, bool $sharePoolingHandler, bool $nftHandler) {
    /** @var Transaction */
    $transaction = $factory->when($method, fn ($factory) => $factory->$method())->withFeeInFiat()->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    if ($factory instanceof TransferFactory) {
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
        fn (Transaction $feeTransaction) => $feeTransaction instanceof Disposal
            && $feeTransaction->marketValue->isEqualTo($transaction->fee->marketValue),
    );
})->with([
    'dispose of' => [Disposal::factory(), null, true, false],
    'acquire' => [Acquisition::factory(), null, true, false],
    'swap' => [Swap::factory(), null, true, false],
    'income' => [Acquisition::factory(), 'income', true, false],
    'transfer' => [Transfer::factory(), null, false, false],
    'dispose of NFT' => [Disposal::factory(), 'nft', false, true],
    'acquire NFT' => [Acquisition::factory(), 'nft', false, true],
    'swap to NFT' => [Swap::factory(), 'toNft', false, true],
    'swap from NFT' => [Swap::factory(), 'fromNft', false, true],
]);
