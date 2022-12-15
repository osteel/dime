<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\Nft\Actions\AcquireNft;
use Domain\Aggregates\Nft\Actions\DisposeOfNft;
use Domain\Aggregates\Nft\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Nft\Repositories\NftRepository;
use Domain\Aggregates\Nft\Nft;
use Domain\Enums\FiatCurrency;
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
        'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
    ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a receive operation with fees', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    /** @var Transaction */
    $transaction = Transaction::factory()
        ->receiveNft()
        ->withNetworkFee(new FiatAmount('4', FiatCurrency::GBP))
        ->withPlatformFee(new FiatAmount('6', FiatCurrency::GBP))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(new FiatAmount('60', FiatCurrency::GBP)),
    )->once();
});

it('can handle a send operation', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()->sendNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
    ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a send operation with fees', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()
        ->sendNft()
        ->withNetworkFee(new FiatAmount('4', FiatCurrency::GBP))
        ->withPlatformFee(new FiatAmount('6', FiatCurrency::GBP))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(new FiatAmount('40', FiatCurrency::GBP)),
    )->once();
});

it('can handle a swap operation where the received asset is a NFT', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()->swapToNft()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
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
        'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
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
        'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
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
        'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
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

it('can handle a swap operation with fees', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->twice()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->twice()->with($nft);

    $transaction = Transaction::factory()
        ->swapNfts()
        ->withNetworkFee(new FiatAmount('4', FiatCurrency::GBP))
        ->withPlatformFee(new FiatAmount('6', FiatCurrency::GBP))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(new FiatAmount('45', FiatCurrency::GBP)),
    )->once();

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(new FiatAmount('55', FiatCurrency::GBP)),
    )->once();
});

it('can handle a swap operation with fees where the received asset is a NFT and the sent asset is some fiat currency', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()
        ->swapToNft()
        ->swapFromFiat()
        ->withNetworkFee(new FiatAmount('4', FiatCurrency::GBP))
        ->withPlatformFee(new FiatAmount('6', FiatCurrency::GBP))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'acquire',
        fn (AcquireNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(new FiatAmount('60', FiatCurrency::GBP)),
    )->once();
});

it('can handle a swap operation with fees where the sent asset is a NFT and the received asset is some fiat currency', function () {
    $nft = Mockery::spy(Nft::class);

    $this->nftRepository->shouldReceive('get')->once()->andReturn($nft);
    $this->nftRepository->shouldReceive('save')->once()->with($nft);

    $transaction = Transaction::factory()
        ->swapFromNft()
        ->swapToFiat()
        ->withNetworkFee(new FiatAmount('4', FiatCurrency::GBP))
        ->withPlatformFee(new FiatAmount('6', FiatCurrency::GBP))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => new FiatAmount('50', FiatCurrency::GBP),
        ]);

    $this->nftHandler->handle($transaction);

    $nft->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNft $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(new FiatAmount('40', FiatCurrency::GBP)),
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
