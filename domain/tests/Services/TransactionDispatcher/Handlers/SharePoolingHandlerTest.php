<?php

use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\Repositories\SharePoolingRepository;
use Domain\Aggregates\SharePooling\SharePooling;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->sharePoolingRepository = Mockery::mock(SharePoolingRepository::class);
});

it('can handle a receive operation', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);

    $transaction = Transaction::factory()->receive()->make(['costBasis' => new FiatAmount('50', FiatCurrency::GBP)]);

    (new SharePoolingHandler($this->sharePoolingRepository))->handle($transaction);

    $sharePooling->shouldHaveReceived('acquire')
        ->once()
        ->withArgs(fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->costBasis));
});

it('can handle a send operation', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);

    $transaction = Transaction::factory()->send()->make(['costBasis' => new FiatAmount('50', FiatCurrency::GBP)]);

    (new SharePoolingHandler($this->sharePoolingRepository))->handle($transaction);

    $sharePooling->shouldHaveReceived('disposeOf')
        ->once()
        ->withArgs(fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->costBasis));
});

it('can handle a swap operation where the received asset is not a NFT', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);

    $transaction = Transaction::factory()->swapFromNft()->make(['costBasis' => new FiatAmount('50', FiatCurrency::GBP)]);

    (new SharePoolingHandler($this->sharePoolingRepository))->handle($transaction);

    $sharePooling->shouldHaveReceived('acquire')
        ->once()
        ->withArgs(fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->costBasis));
});

it('can handle a swap operation where the sent asset is not a NFT', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);

    $transaction = Transaction::factory()->swapToNft()->make(['costBasis' => new FiatAmount('50', FiatCurrency::GBP)]);

    (new SharePoolingHandler($this->sharePoolingRepository))->handle($transaction);

    $sharePooling->shouldHaveReceived('disposeOf')
        ->once()
        ->withArgs(fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->costBasis));
});

it('can handle a swap operation where neither asset is a NFT', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->twice()->andReturn($sharePooling);

    $transaction = Transaction::factory()->swap()->make(['costBasis' => new FiatAmount('50', FiatCurrency::GBP)]);

    (new SharePoolingHandler($this->sharePoolingRepository))->handle($transaction);

    $sharePooling->shouldHaveReceived('disposeOf')
        ->once()
        ->withArgs(fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->costBasis));

    $sharePooling->shouldHaveReceived('acquire')
        ->once()
        ->withArgs(fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->costBasis));
});

it('cannot handle a transaction because the operation is not supported', function () {
    (new SharePoolingHandler($this->sharePoolingRepository))->handle(Transaction::factory()->transfer()->make());
})->throws(SharePoolingHandlerException::class);

it('cannot handle a transaction because one of the assets is a NFT', function () {
    (new SharePoolingHandler($this->sharePoolingRepository))->handle(Transaction::factory()->swapNfts()->make());
})->throws(SharePoolingHandlerException::class);
