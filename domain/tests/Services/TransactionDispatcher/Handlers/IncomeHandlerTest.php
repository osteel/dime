<?php

use Domain\Aggregates\TaxYear\Actions\RecordIncome;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);
});

it('can handle an income transaction', function () {
    $taxYear = Mockery::spy(TaxYear::class);

    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);

    $transaction = Transaction::factory()->income()->make(['costBasis' => new FiatAmount('50', FiatCurrency::GBP)]);

    (new IncomeHandler($this->taxYearRepository))->handle($transaction);

    $taxYear->shouldHaveReceived('recordIncome')
        ->once()
        ->withArgs(fn (RecordIncome $action) => $action->amount->isEqualTo($transaction->costBasis));
});

it('cannot handle a transaction because the operation is not receive', function () {
    $transaction = Transaction::factory()->send()->make();

    expect(fn () => (new IncomeHandler($this->taxYearRepository))->handle($transaction))
        ->toThrow(IncomeHandlerException::class, IncomeHandlerException::operationIsNotReceive($transaction)->getMessage());
});

it('cannot handle a transaction because it is not income', function () {
    $transaction = Transaction::factory()->receive()->make();

    expect(fn () => (new IncomeHandler($this->taxYearRepository))->handle($transaction))
        ->toThrow(IncomeHandlerException::class, IncomeHandlerException::notIncome($transaction)->getMessage());
});
