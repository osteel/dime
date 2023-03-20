<?php

use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\TransferHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);
    $this->transferHandler = new TransferHandler($this->taxYearRepository);
});

it('can handle a transfer operation', function () {
    $taxYear = Mockery::spy(TaxYear::class);

    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $transaction = Transaction::factory()
        ->transfer()
        ->withNetworkFee($networkFee = FiatAmount::GBP('5'))
        ->withPlatformFee($platformFee = FiatAmount::GBP('10'))
        ->make();

    $this->transferHandler->handle($transaction);

    $taxYear->shouldHaveReceived(
        'updateNonAttributableAllowableCost',
        fn (UpdateNonAttributableAllowableCost $action) => $action->nonAttributableAllowableCost->isEqualTo($networkFee),
    )->once();

    $taxYear->shouldHaveReceived(
        'updateNonAttributableAllowableCost',
        fn (UpdateNonAttributableAllowableCost $action) => $action->nonAttributableAllowableCost->isEqualTo($platformFee),
    )->once();
});

it('can handle a transfer operation with no fees', function () {
    $this->transferHandler->handle(Transaction::factory()->transfer()->make());

    $this->taxYearRepository->shouldNotHaveReceived('get');
});

it('cannot handle a transaction because the operation is not transfer', function () {
    $transaction = Transaction::factory()->send()->make();

    expect(fn () => $this->transferHandler->handle($transaction))
        ->toThrow(TransferHandlerException::class, TransferHandlerException::notTransfer($transaction)->getMessage());
});
