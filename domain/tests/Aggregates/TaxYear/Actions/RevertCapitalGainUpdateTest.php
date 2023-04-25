<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\ValueObjects\FiatAmount;

it('can update the aggregate', function () {
    $taxYear = Mockery::mock(TaxYear::class);
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    $revertCapitalGainUpdate = new RevertCapitalGainUpdate(
        date: LocalDate::parse('2015-10-21'),
        capitalGain: new CapitalGain(
            costBasis: FiatAmount::GBP('1'),
            proceeds: FiatAmount::GBP('2'),
        )
    );

    $taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $taxYear->shouldReceive('revertCapitalGainUpdate')->once()->with($revertCapitalGainUpdate);
    $taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $revertCapitalGainUpdate->handle($taxYearRepository);
});
