<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\Nft\Actions\AcquireNft;
use Domain\Aggregates\Nft\Actions\DisposeOfNft;
use Domain\Aggregates\Nft\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Nft\Repositories\NftRepository;
use Domain\Aggregates\Nft\Nft;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NftHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\NftHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->nftRepository = Mockery::mock(NftRepository::class);
    $this->nftHandler = new NftHandler($this->nftRepository);
});

it('can handle a receive operation', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    /** @var Transaction */
    $transaction = Transaction::factory()->receiveNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a receive operation with a fee', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    /** @var Transaction */
    $transaction = Transaction::factory()
        ->receiveNft()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a send operation', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()->sendNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a send operation with a fee', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()
        ->sendNft()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('can handle a swap operation where the received asset is a NFT', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()->swapToNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the sent asset is a NFT', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()->swapFromNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where both assets are NFTs', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->twice()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->twice()->with($nft);

    $transaction = Transaction::factory()->swapNfts()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where both assets are NFTs and the received NFT is already acquired', function () {
    $nft = Mockery::mock(Nft::class);

    $this->nftRepository->shouldReceive('get')->twice()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->twice()->with($nft);

    $transaction = Transaction::factory()->swapNfts()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $nft->shouldReceive('isAlreadyAcquired')->andReturn(true)->once();

    $nft->shouldReceive(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $nft->shouldReceive(
        'increaseCostBasis',
        fn (IncreaseNftCostBasis $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasisIncrease->isEqualTo($transaction->marketValue),
    )->once();

    $this->nftHandler->handle($transaction);
});

it('can handle a swap operation with a fee', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->twice()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->twice()->with($nft);

    $transaction = Transaction::factory()
        ->swapNfts()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('45')),
    )->once();

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('55')),
    )->once();
});

it('can handle a swap operation with a fee where the received asset is a NFT and the sent asset is some fiat currency', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()
        ->swapToNft()
        ->swapFromFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a swap operation with a fee where the sent asset is a NFT and the received asset is some fiat currency', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()
        ->swapFromNft()
        ->swapToFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('cannot handle a transaction because the operation is not supported', function () {
    $transaction = Transaction::factory()->transfer()->make();

    expect(fn () => $this->nftHandler->handle($transaction))
        ->toThrow(NftHandlerException::class, NftHandlerException::unsupportedOperation($transaction)->getMessage());
});

it('cannot handle a transaction because none of the assets is a NFT', function () {
    $transaction = Transaction::factory()->swap()->make();

    expect(fn () => $this->nftHandler->handle($transaction))
        ->toThrow(NftHandlerException::class, NftHandlerException::noNft($transaction)->getMessage());
});
