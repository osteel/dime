<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\Nft\Actions\AcquireNft;
use Domain\Aggregates\Nft\Actions\DisposeOfNft;
use Domain\Aggregates\Nft\Repositories\NftRepository;
use Domain\Aggregates\Nft\Nft;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NftHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\NftHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->nftRepository = Mockery::mock(NftRepository::class);
});

it('can handle a receive operation', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);

    $transaction = Transaction::factory()->receiveNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'costBasis' => new FiatAmount('50', FiatCurrency::GBP),
    ]);

    (new NftHandler($this->nftRepository))->handle($transaction);

    $nft->shouldHaveReceived('acquire')
        ->once()
        ->withArgs(fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->costBasis));
});

it('can handle a send operation', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);

    $transaction = Transaction::factory()->sendNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'costBasis' => new FiatAmount('50', FiatCurrency::GBP),
    ]);

    (new NftHandler($this->nftRepository))->handle($transaction);

    $nft->shouldHaveReceived('disposeOf')
        ->once()
        ->withArgs(fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->costBasis));
});

it('can handle a swap operation where the received asset is a NFT', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);

    $transaction = Transaction::factory()->swapToNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'costBasis' => new FiatAmount('50', FiatCurrency::GBP),
    ]);

    (new NftHandler($this->nftRepository))->handle($transaction);

    $nft->shouldHaveReceived('acquire')
        ->once()
        ->withArgs(fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->costBasis));
});

it('can handle a swap operation where the sent asset is a NFT', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);

    $transaction = Transaction::factory()->swapFromNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'costBasis' => new FiatAmount('50', FiatCurrency::GBP),
    ]);

    (new NftHandler($this->nftRepository))->handle($transaction);

    $nft->shouldHaveReceived('disposeOf')
        ->once()
        ->withArgs(fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->costBasis));
});

it('can handle a swap operation where both assets are NFTs', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->twice()->andReturn($nft);

    $transaction = Transaction::factory()->swapNfts()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'costBasis' => new FiatAmount('50', FiatCurrency::GBP),
    ]);

    (new NftHandler($this->nftRepository))->handle($transaction);

    $nft->shouldHaveReceived('disposeOf')
        ->once()
        ->withArgs(fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->costBasis));

    $nft->shouldHaveReceived('acquire')
        ->once()
        ->withArgs(fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->costBasis));
});

it('cannot handle a transaction because the operation is not supported', function () {
    (new NftHandler($this->nftRepository))->handle(Transaction::factory()->transfer()->make());
})->throws(NftHandlerException::class);

it('cannot handle a transaction because none of the assets is a NFT', function () {
    (new NftHandler($this->nftRepository))->handle(Transaction::factory()->swap()->make());
})->throws(NftHandlerException::class);