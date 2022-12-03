<?php

use Domain\Aggregates\TaxYear\Actions\RecordIncome;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Enums\FiatCurrency;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

it('can handle some income', function () {
    $taxYear = Mockery::spy(TaxYear::class);

    $taxYearRepository = Mockery::mock(TaxYearRepository::class)
        ->shouldReceive('get')
        ->once()
        ->andReturn($taxYear)
        ->getMock();

    $amount = new FiatAmount('50', FiatCurrency::GBP);

    (new IncomeHandler($taxYearRepository))->handle(Transaction::factory()->income()->make(['costBasis' => $amount]));

    $taxYear->shouldHaveReceived('recordIncome')
        ->once()
        ->withArgs(fn (RecordIncome $action) => $action->amount->isEqualTo($amount));
});

it('cannot handle some income because the operation is not receive', function () {
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    (new IncomeHandler($taxYearRepository))->handle(Transaction::factory()->send()->make());
})->throws(IncomeHandlerException::class);

it('cannot handle some income because the transaction is not income', function () {
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    (new IncomeHandler($taxYearRepository))->handle(Transaction::factory()->receive()->make());
})->throws(IncomeHandlerException::class);
