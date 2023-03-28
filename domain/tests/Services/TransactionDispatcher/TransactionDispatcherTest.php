<?php

use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\Services\TransactionDispatcher\Handlers\NonFungibleAssetHandler;
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
    $this->nonFungibleAssetHandler = Mockery::spy(NonFungibleAssetHandler::class);
    $this->sharePoolingHandler = Mockery::spy(SharePoolingHandler::class);

    $this->transactionDispatcher = new TransactionDispatcher(
        $this->incomeHandler,
        $this->transferHandler,
        $this->nonFungibleAssetHandler,
        $this->sharePoolingHandler,
    );
});

it('can dispatch to the income handler', function () {
    $transaction = Acquisition::factory()->income()->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    $this->transferHandler->shouldNotHaveReceived('handle');
    $this->nonFungibleAssetHandler->shouldNotHaveReceived('handle');
    $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
});

it('can dispatch to the transfer handler', function (bool $isNonFungibleAsset) {
    $transaction = Transfer::factory()->when($isNonFungibleAsset, fn ($factory) => $factory->nonFungibleAsset())->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldNotHaveReceived('handle');
    $this->transferHandler->shouldHaveReceived('handle')->with($transaction)->once();
    $this->nonFungibleAssetHandler->shouldNotHaveReceived('handle');
    $this->sharePoolingHandler->shouldNotHaveReceived('handle');
})->with([
    'share pooling asset' => false,
    'non-fungible asset' => true,
]);

it('can dispatch to the non-fungible asset handler', function (TransactionFactory $factory, string $method, bool $sharePoolingHandler) {
    $transaction = $factory->$method()->make();

    $this->transactionDispatcher->dispatch($transaction);

    $this->incomeHandler->shouldNotHaveReceived('handle');
    $this->transferHandler->shouldNotHaveReceived('handle');
    $this->nonFungibleAssetHandler->shouldHaveReceived('handle')->with($transaction)->once();

    if ($sharePoolingHandler) {
        $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->sharePoolingHandler->shouldNotHaveReceived('handle');
    }
})->with([
    'dispose of non-fungible asset' => [Disposal::factory(), 'nonFungibleAsset', false],
    'acquire non-fungible asset' => [Acquisition::factory(), 'nonFungibleAsset', false],
    'swap to non-fungible asset' => [Swap::factory(), 'toNonFungibleAsset', true],
    'swap from non-fungible asset' => [Swap::factory(), 'fromNonFungibleAsset', true],
    'swap non-fungible assets' => [Swap::factory(), 'nonFungibleAssets', false],
]);

it('can dispatch to the share pooling handler', function (TransactionFactory $factory, ?string $method, bool $nonFungibleAssetHandler) {
    $transaction = $factory->when($method, fn ($factory) => $factory->$method())->make();

    $this->transactionDispatcher->dispatch($transaction);

    if ($method === 'income') {
        $this->incomeHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->incomeHandler->shouldNotHaveReceived('handle');
    }

    $this->transferHandler->shouldNotHaveReceived('handle');

    if ($nonFungibleAssetHandler) {
        $this->nonFungibleAssetHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->nonFungibleAssetHandler->shouldNotHaveReceived('handle');
    }

    $this->sharePoolingHandler->shouldHaveReceived('handle')->with($transaction)->once();
})->with([
    'dispose of' => [Disposal::factory(), null, false],
    'acquire' => [Acquisition::factory(), null, false],
    'swap' => [Swap::factory(), null, false],
    'income' => [Acquisition::factory(), 'income', false],
    'swap to non-fungible asset' => [Swap::factory(), 'toNonFungibleAsset', true],
    'swap from non-fungible asset' => [Swap::factory(), 'fromNonFungibleAsset', true],
]);

it('can dispatch the fee to the share pooling handler', function (TransactionFactory $factory, ?string $method, bool $sharePoolingHandler, bool $nonFungibleAssetHandler) {
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

    if ($nonFungibleAssetHandler) {
        $this->nonFungibleAssetHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->nonFungibleAssetHandler->shouldNotHaveReceived('handle');
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
    'dispose of non-fungible asset' => [Disposal::factory(), 'nonFungibleAsset', false, true],
    'acquire non-fungible asset' => [Acquisition::factory(), 'nonFungibleAsset', false, true],
    'swap to non-fungible asset' => [Swap::factory(), 'toNonFungibleAsset', false, true],
    'swap from non-fungible asset' => [Swap::factory(), 'fromNonFungibleAsset', false, true],
]);

it('does not dispatch the fee to the share pooling handler when it is zero', function (TransactionFactory $factory, ?string $method, bool $sharePoolingHandler, bool $nonFungibleAssetHandler) {
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

    if ($nonFungibleAssetHandler) {
        $this->nonFungibleAssetHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->nonFungibleAssetHandler->shouldNotHaveReceived('handle');
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
    'dispose of non-fungible asset' => [Disposal::factory(), 'nonFungibleAsset', false, true],
    'acquire non-fungible asset' => [Acquisition::factory(), 'nonFungibleAsset', false, true],
    'swap to non-fungible asset' => [Swap::factory(), 'toNonFungibleAsset', false, true],
    'swap from non-fungible asset' => [Swap::factory(), 'fromNonFungibleAsset', false, true],
]);

it('does not dispatch the fee to the share pooling handler when it is fiat', function (TransactionFactory $factory, ?string $method, bool $sharePoolingHandler, bool $nonFungibleAssetHandler) {
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

    if ($nonFungibleAssetHandler) {
        $this->nonFungibleAssetHandler->shouldHaveReceived('handle')->with($transaction)->once();
    } else {
        $this->nonFungibleAssetHandler->shouldNotHaveReceived('handle');
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
    'dispose of non-fungible asset' => [Disposal::factory(), 'nonFungibleAsset', false, true],
    'acquire non-fungible asset' => [Acquisition::factory(), 'nonFungibleAsset', false, true],
    'swap to non-fungible asset' => [Swap::factory(), 'toNonFungibleAsset', false, true],
    'swap from non-fungible asset' => [Swap::factory(), 'fromNonFungibleAsset', false, true],
]);
