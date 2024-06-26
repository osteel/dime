<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYearContract;
use Domain\ValueObjects\FiatAmount;

it('can update the income', function () {
    $taxYear = Mockery::spy(TaxYearContract::class);
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    $updateIncome = new UpdateIncome(
        date: LocalDate::parse('2015-10-21'),
        incomeUpdate: FiatAmount::GBP('1'),
    );

    $taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $updateIncome($taxYearRepository);

    $taxYear->shouldHaveReceived('updateIncome')->once()->with($updateIncome);
});
