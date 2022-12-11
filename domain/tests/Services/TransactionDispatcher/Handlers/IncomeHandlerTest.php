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
    $this->incomeHandler = new IncomeHandler($this->taxYearRepository);
});

it('can handle an income transaction', function () {
    $taxYear = Mockery::spy(TaxYear::class);

    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);

    $transaction = Transaction::factory()->income()->make(['marketValue' => new FiatAmount('50', FiatCurrency::GBP)]);

    $this->incomeHandler->handle($transaction);

    $taxYear->shouldHaveReceived(
        'recordIncome',
        fn (RecordIncome $action) => $action->amount->isEqualTo($transaction->marketValue),
    )->once();
});

it('cannot handle a transaction because the operation is not receive', function () {
    $transaction = Transaction::factory()->send()->make();

    expect(fn () => $this->incomeHandler->handle($transaction))
        ->toThrow(IncomeHandlerException::class, IncomeHandlerException::operationIsNotReceive($transaction)->getMessage());
});

it('cannot handle a transaction because it is not income', function () {
    $transaction = Transaction::factory()->receive()->make();

    expect(fn () => $this->incomeHandler->handle($transaction))
        ->toThrow(IncomeHandlerException::class, IncomeHandlerException::notIncome($transaction)->getMessage());
});
