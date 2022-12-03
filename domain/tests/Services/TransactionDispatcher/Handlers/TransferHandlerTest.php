<?php

use Domain\Aggregates\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\TransferHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);
});

it('can handle a transfer operation', function () {
    $taxYear = Mockery::spy(TaxYear::class);

    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);

    $transaction = Transaction::factory()
        ->transfer()
        ->withTransactionFee($transactionFee = new FiatAmount('5', FiatCurrency::GBP))
        ->withExchangeFee($exchangeFee = new FiatAmount('10', FiatCurrency::GBP))
        ->make();

    (new TransferHandler($this->taxYearRepository))->handle($transaction);

    $taxYear->shouldHaveReceived('recordNonAttributableAllowableCost')
        ->withArgs(fn (RecordNonAttributableAllowableCost $action) => $action->amount->isEqualTo($transactionFee))
        ->withArgs(fn (RecordNonAttributableAllowableCost $action) => $action->amount->isEqualTo($exchangeFee));
});

it('can handle a transfer operation with no fees', function () {
    (new TransferHandler($this->taxYearRepository))->handle(Transaction::factory()->transfer()->make());

    $this->taxYearRepository->shouldNotHaveReceived('get');
});

it('cannot handle a transaction because the operation is not transfer', function () {
    (new TransferHandler($this->taxYearRepository))->handle(Transaction::factory()->send()->make());
})->throws(TransferHandlerException::class);
