<?php

use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\Repositories\SharePoolingRepository;
use Domain\Aggregates\SharePooling\SharePooling;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

beforeEach(function () {
    $this->sharePoolingRepository = Mockery::mock(SharePoolingRepository::class);
    $this->sharePoolingHandler = new SharePoolingHandler($this->sharePoolingRepository);
});

it('can handle a receive operation', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Acquisition::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a receive operation with a fee', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Acquisition::factory()
        ->withFee(FiatAmount::GBP('10'))
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

    $transaction = Disposal::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a send operation with a fee', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Disposal::factory()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('can handle a swap operation where the received asset is not a non-fungible asset', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Swap::factory()->fromNonFungibleAsset()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the sent asset is not a non-fungible asset', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Swap::factory()->toNonFungibleAsset()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where neither asset is a non-fungible asset', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->twice()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->twice()->with($sharePooling);

    $transaction = Swap::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

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

    $transaction = Swap::factory()->toFiat()->make(['marketValue' => FiatAmount::GBP('50')]);

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

    $transaction = Swap::factory()->fromFiat()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();

    $sharePooling->shouldNotHaveReceived('disposeOf');
});

it('can handle a swap operation with a fee', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->twice()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->twice()->with($sharePooling);

    $transaction = Swap::factory()
        ->withFee(FiatAmount::GBP('10'))
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

it('can handle a swap operation with a fee where the received asset is some fiat currency', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Swap::factory()
        ->toFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingToken $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();

    $sharePooling->shouldNotHaveReceived('acquire');
});

it('can handle a swap operation with a fee where the sent asset is some fiat currency', function () {
    $sharePooling = Mockery::spy(SharePooling::class);

    $this->sharePoolingRepository->shouldReceive('get')->once()->andReturn($sharePooling);
    $this->sharePoolingRepository->shouldReceive('save')->once()->with($sharePooling);

    $transaction = Swap::factory()
        ->fromFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingHandler->handle($transaction);

    $sharePooling->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingToken $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();

    $sharePooling->shouldNotHaveReceived('disposeOf');
});

it('cannot handle a transaction because one of the assets is a non-fungible asset', function () {
    $transaction = Swap::factory()->nonFungibleAssets()->make();

    expect(fn () => $this->sharePoolingHandler->handle($transaction))
        ->toThrow(SharePoolingHandlerException::class, SharePoolingHandlerException::noSharePoolingAsset($transaction)->getMessage());
});
