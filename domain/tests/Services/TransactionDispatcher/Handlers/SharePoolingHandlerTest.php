<?php

use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\Repositories\SharePoolingRepository;
use Domain\Aggregates\SharePooling\SharePooling;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->sharePoolingRepository = Mockery::mock(SharePoolingRepository::class);
    $this->sharePoolingHandler = new SharePoolingHandler($this->sharePoolingRepository);
});

it('can handle a receive operation', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()->receive()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a receive operation with fees', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()
        ->receive()
        ->withNetworkFee(FiatAmount::GBP('4'))
        ->withPlatformFee(FiatAmount::GBP('6'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a send operation', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()->send()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a send operation with fees', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()
        ->send()
        ->withNetworkFee(FiatAmount::GBP('4'))
        ->withPlatformFee(FiatAmount::GBP('6'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('can handle a swap operation where the received asset is not a NFT', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()->swapFromNft()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the sent asset is not a NFT', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()->swapToNft()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where neither asset is a NFT', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->twice()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->twice()->with($sharePooling);

    $transaction = Transaction::factory()->swap()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the received asset is some fiat currency', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()->swapToFiat()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $sharePooling->shouldNotHaveReceived('acquire');
});

it('can handle a swap operation where the sent asset is some fiat currency', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()->swapFromFiat()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();

    $sharePooling->shouldNotHaveReceived('disposeOf');
});

it('can handle a swap operation with fees', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->twice()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->twice()->with($sharePooling);

    $transaction = Transaction::factory()
        ->swap()
        ->withNetworkFee(FiatAmount::GBP('4'))
        ->withPlatformFee(FiatAmount::GBP('6'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('45')),
    )->once();

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('55')),
    )->once();
});

it('can handle a swap operation with fees where the received asset is some fiat currency', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()
        ->swapToFiat()
        ->withNetworkFee(FiatAmount::GBP('4'))
        ->withPlatformFee(FiatAmount::GBP('6'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();

    $sharePooling->shouldNotHaveReceived('acquire');
});

it('can handle a swap operation with fees where the sent asset is some fiat currency', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Transaction::factory()
        ->swapFromFiat()
        ->withNetworkFee(FiatAmount::GBP('4'))
        ->withPlatformFee(FiatAmount::GBP('6'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();

    $sharePooling->shouldNotHaveReceived('disposeOf');
});

it('cannot handle a transaction because the operation is not supported', function () {
    $transaction = Transaction::factory()->transfer()->make();

    expect(fn () => $this->sharePoolingHandler->handle($transaction))
        ->toThrow(SharePoolingHandlerException::class, SharePoolingHandlerException::unsupportedOperation($transaction)->getMessage());
});

it('cannot handle a transaction because one of the assets is a NFT', function () {
    $transaction = Transaction::factory()->swapNfts()->make();

    expect(fn () => $this->sharePoolingHandler->handle($transaction))
        ->toThrow(SharePoolingHandlerException::class, SharePoolingHandlerException::bothNfts($transaction)->getMessage());
});
