<?php

use App\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository;
use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;

beforeEach(function () {
    $this->taxYearId = TaxYearId::fromDate(LocalDate::parse('2015-10-21'));
    $this->taxYearSummaryRepository = new TaxYearSummaryRepository();
});

it('can create a tax year summary and update its capital gain', function () {
    $this->assertDatabaseCount('tax_year_summaries', 0);

    $capitalGain = new CapitalGain(costBasis: FiatAmount::GBP('100'), proceeds: FiatAmount::GBP('150'));

    $this->taxYearSummaryRepository->updateCapitalGain($this->taxYearId, $capitalGain);

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $this->taxYearId->toString(),
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '100',
            'proceeds' => '150',
            'difference' => '50',
        ]),
    ]);

    expect(TaxYearSummary::first()->capital_gain)->toEqual($capitalGain);
});

it('can create a tax year summary and update its income', function () {
    $this->assertDatabaseCount('tax_year_summaries', 0);

    $this->taxYearSummaryRepository->updateIncome($this->taxYearId, FiatAmount::GBP('100'));

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $this->taxYearId->toString(),
        'currency' => FiatCurrency::GBP->value,
        'income' => '100',
    ]);
});

it('can create a tax year summary and update its non-attributable allowed cost', function () {
    $this->assertDatabaseCount('tax_year_summaries', 0);

    $this->taxYearSummaryRepository->updateNonAttributableAllowableCost($this->taxYearId, FiatAmount::GBP('100'));

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $this->taxYearId->toString(),
        'currency' => FiatCurrency::GBP->value,
        'non_attributable_allowable_cost' => '100',
    ]);
});

it('can retrieve an existing tax year summary and update its capital gain', function () {
    $this->assertDatabaseCount('tax_year_summaries', 0);

    $taxYearSummary = TaxYearSummary::factory()->create([
        'tax_year_id' => $this->taxYearId,
        'currency' => FiatCurrency::GBP,
        'capital_gain' => new CapitalGain(
            costBasis: FiatAmount::GBP('100'),
            proceeds: FiatAmount::GBP('100'),
        ),
    ]);

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $taxYearSummary->tax_year_id->toString(),
        'currency' => $taxYearSummary->currency->value,
        'capital_gain' => json_encode([
            'cost_basis' => '100',
            'proceeds' => '100',
            'difference' => '0',
        ]),
    ]);

    $capitalGain = new CapitalGain(costBasis: FiatAmount::GBP('200'), proceeds: FiatAmount::GBP('250'));

    $this->taxYearSummaryRepository->updateCapitalGain($this->taxYearId, $capitalGain);

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $taxYearSummary->tax_year_id->toString(),
        'currency' => $taxYearSummary->currency->value,
        'capital_gain' => json_encode([
            'cost_basis' => '200',
            'proceeds' => '250',
            'difference' => '50',
        ]),
    ]);
});

it('can retrieve an existing tax year summary and update its income', function () {
    $this->assertDatabaseCount('tax_year_summaries', 0);

    $taxYearSummary = TaxYearSummary::factory()->create([
        'tax_year_id' => $this->taxYearId,
        'currency' => FiatCurrency::GBP,
        'income' => FiatAmount::GBP('100'),
    ]);

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $taxYearSummary->tax_year_id->toString(),
        'currency' => $taxYearSummary->currency->value,
        'income' => '100',
    ]);

    $this->taxYearSummaryRepository->updateIncome($this->taxYearId, FiatAmount::GBP('200'));

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $taxYearSummary->tax_year_id->toString(),
        'currency' => $taxYearSummary->currency->value,
        'income' => '200',
    ]);
});

it('can retrieve an existing tax year summary and update its non-attributable allowable cost', function () {
    $this->assertDatabaseCount('tax_year_summaries', 0);

    $taxYearSummary = TaxYearSummary::factory()->create([
        'tax_year_id' => $this->taxYearId,
        'currency' => FiatCurrency::GBP,
        'non_attributable_allowable_cost' => FiatAmount::GBP('100'),
    ]);

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $taxYearSummary->tax_year_id->toString(),
        'currency' => $taxYearSummary->currency->value,
        'non_attributable_allowable_cost' => '100',
    ]);

    $this->taxYearSummaryRepository->updateNonAttributableAllowableCost($this->taxYearId, FiatAmount::GBP('200'));

    $this->assertDatabaseCount('tax_year_summaries', 1);
    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => $taxYearSummary->tax_year_id->toString(),
        'currency' => $taxYearSummary->currency->value,
        'non_attributable_allowable_cost' => '200',
    ]);
});
