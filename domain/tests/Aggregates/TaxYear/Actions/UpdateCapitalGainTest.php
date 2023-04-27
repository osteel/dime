<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\ValueObjects\FiatAmount;

it('can update the capital gain', function () {
    $taxYear = Mockery::spy(TaxYear::class);
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    $updateCapitalGain = new UpdateCapitalGain(
        date: LocalDate::parse('2015-10-21'),
        capitalGain: new CapitalGain(
            costBasis: FiatAmount::GBP('1'),
            proceeds: FiatAmount::GBP('2'),
        )
    );

    $taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $updateCapitalGain->handle($taxYearRepository);

    $taxYear->shouldHaveReceived('updateCapitalGain')->once()->with($updateCapitalGain);
});
