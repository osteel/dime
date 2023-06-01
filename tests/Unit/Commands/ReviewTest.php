<?php

use App\Services\Presenter\PresenterContract;
use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use LaravelZero\Framework\Commands\Command;

beforeEach(function () {
    $this->taxYearSummaryRepository = Mockery::mock(TaxYearSummaryRepository::class);

    $this->instance(TaxYearSummaryRepository::class, $this->taxYearSummaryRepository);
});

it('cannot review a tax year because the submitted tax year is invalid', function (mixed $value) {
    $this->taxYearSummaryRepository->shouldReceive('all')->andReturn([['tax_year_id' => TaxYearId::fromString('2021-2022')]]);

    $this->artisan('review', ['taxyear' => $value])
        ->expectsOutputToContain('Tax years must be two consecutive years separated by an hyphen')
        ->assertExitCode(Command::INVALID);
})->with(['foo', true]);

it('cannot review a tax year because none are available', function () {
    $this->taxYearSummaryRepository->shouldReceive('all')->once()->andReturn([]);

    $this->artisan('review')
        ->expectsOutputToContain('No tax year to review')
        ->assertExitCode(Command::SUCCESS);
});

it('can review a tax year', function () {
    $taxYearId = TaxYearId::fromString('2021-2022');

    $this->taxYearSummaryRepository->shouldReceive('all')->once()->andReturn([['tax_year_id' => $taxYearId]]);
    $this->taxYearSummaryRepository->shouldReceive('find')
        ->once()
        ->withArgs(fn (TaxYearId $id) => $id->toString() === $taxYearId->toString())
        ->andReturn(TaxYearSummary::factory()->make([
            'tax_year_id' => $taxYearId,
            'currency' => FiatCurrency::GBP,
            'capital_gain' => new CapitalGain(
                costBasis: FiatAmount::GBP('2'),
                proceeds: FiatAmount::GBP('4'),
            ),
            'income' => FiatAmount::GBP('10'),
            'non_attributable_allowable_cost' => FiatAmount::GBP('1'),
        ]));

    $presenter = Mockery::mock(PresenterContract::class);
    $presenter->shouldReceive('summary')
        ->once()
        ->with($taxYearId->toString(), '£4.00', '£2.00', '£1.00', '£3.00', '£1.00', '£10.00')
        ->andReturn();

    $this->instance(PresenterContract::class, $presenter);

    $this->artisan('review')->assertExitCode(Command::SUCCESS);
});

it('offers to choose a tax year', function () {
    $this->taxYearSummaryRepository->shouldReceive('all')->once()->andReturn([
        ['tax_year_id' => TaxYearId::fromString('2021-2022')],
        ['tax_year_id' => TaxYearId::fromString('2022-2023')],
    ]);

    $this->taxYearSummaryRepository->shouldReceive('find')
        ->once()
        ->withArgs(fn (TaxYearId $id) => $id->toString() === '2022-2023')
        ->andReturn(TaxYearSummary::factory()->make());

    $this->taxYearSummaryRepository->shouldReceive('find')
        ->once()
        ->withArgs(fn (TaxYearId $id) => $id->toString() === '2021-2022')
        ->andReturn(TaxYearSummary::factory()->make());

    $presenter = Mockery::mock(PresenterContract::class);
    $presenter->shouldReceive('choice')->once()->with('Please select a tax year', ['2022-2023', '2021-2022'], '2022-2023')->andReturn('2022-2023');
    $presenter->shouldReceive('choice')->once()->with('Review another tax year?', ['No', '2022-2023', '2021-2022'], 'No')->andReturn('2021-2022');
    $presenter->shouldReceive('choice')->once()->with('Review another tax year?', ['No', '2022-2023', '2021-2022'], 'No')->andReturn('No');
    $presenter->shouldReceive('summary')->twice()->andReturn();

    $this->instance(PresenterContract::class, $presenter);

    $this->artisan('review')->assertExitCode(Command::SUCCESS);
});
