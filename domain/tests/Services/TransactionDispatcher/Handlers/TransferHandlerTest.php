<?php

use Domain\Aggregates\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\TransferHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

it('can handle a non-attributable allowable cost', function () {
    $taxYear = Mockery::spy(TaxYear::class);

    $taxYearRepository = Mockery::mock(TaxYearRepository::class)
        ->shouldReceive('get')
        ->once()
        ->andReturn($taxYear)
        ->getMock();

    $transaction = Transaction::factory()
        ->transfer()
        ->withTransactionFee($transactionFee = new FiatAmount('5', FiatCurrency::GBP))
        ->withExchangeFee($exchangeFee = new FiatAmount('10', FiatCurrency::GBP))
        ->make();

    (new TransferHandler($taxYearRepository))->handle($transaction);

    $taxYear->shouldHaveReceived('recordNonAttributableAllowableCost')
        ->withArgs(fn (RecordNonAttributableAllowableCost $action) => $action->amount->isEqualTo($transactionFee))
        ->withArgs(fn (RecordNonAttributableAllowableCost $action) => $action->amount->isEqualTo($exchangeFee));
});

it('can handle a zero non-attributable allowable cost', function () {
    $taxYearRepository = Mockery::spy(TaxYearRepository::class);

    (new TransferHandler($taxYearRepository))->handle(Transaction::factory()->transfer()->make());

    $taxYearRepository->shouldNotHaveReceived('get');
});

it('cannot handle a non-attributable allowable cost because the operation is not transfer', function () {
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    (new TransferHandler($taxYearRepository))->handle(Transaction::factory()->send()->make());
})->throws(TransferHandlerException::class);
