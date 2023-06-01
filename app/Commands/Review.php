<?php

namespace App\Commands;

use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository;
use Domain\Aggregates\TaxYear\ValueObjects\Exceptions\TaxYearIdException;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;

final class Review extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'review
        {taxyear? : The tax year whose summary to display (e.g. 2015-2016)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Display a tax year\'s summary';

    /** Execute the console command. */
    public function handle(TaxYearSummaryRepository $repository): int
    {
        $taxYear = $this->argument('taxyear');

        if (! is_null($taxYear) && ! is_string($taxYear)) {
            $this->presenter->error(TaxYearIdException::invalidTaxYear()->getMessage());

            return self::INVALID;
        }

        $availableTaxYears = array_filter(array_map(
            fn (mixed $taxYearId) => $taxYearId instanceof TaxYearId ? $taxYearId->toString() : null,
            array_column($repository->all(), 'tax_year_id'),
        ));

        if (empty($availableTaxYears)) {
            $this->presenter->warning('No tax year to review. Please submit transactions first, using the `process` command');

            return self::SUCCESS;
        }

        // Order tax years from more recent to older
        rsort($availableTaxYears);

        $taxYear ??= count($availableTaxYears) === 1
            ? $availableTaxYears[0]
            : $this->presenter->choice('Please select a tax year', $availableTaxYears, $availableTaxYears[0]);

        return $this->summary($repository, $taxYear, $availableTaxYears);
    }

    /** @param list<string> $availableTaxYears */
    private function summary(TaxYearSummaryRepository $repository, string $taxYear, array $availableTaxYears): int
    {
        try {
            $taxYearId = TaxYearId::fromString($taxYear);
        } catch (TaxYearIdException $exception) {
            $this->presenter->error($exception->getMessage());

            return self::INVALID;
        }

        $taxYearSummary = $repository->find($taxYearId);

        assert($taxYearSummary instanceof TaxYearSummary);

        $this->presenter->summary(
            taxYear: $taxYear,
            proceeds: (string) $taxYearSummary->capital_gain->proceeds,
            costBasis: (string) $taxYearSummary->capital_gain->costBasis,
            nonAttributableAllowableCost: (string) $taxYearSummary->non_attributable_allowable_cost,
            totalCostBasis: (string) $taxYearSummary->capital_gain->costBasis->plus($taxYearSummary->non_attributable_allowable_cost),
            capitalGain: (string) $taxYearSummary->capital_gain->difference->minus($taxYearSummary->non_attributable_allowable_cost),
            income: (string) $taxYearSummary->income,
        );

        // If there is only one tax year available, we are done
        if (count($availableTaxYears) === 1) {
            return self::SUCCESS;
        }

        $taxYear = $this->presenter->choice('Review another tax year?', array_merge(['No'], $availableTaxYears), 'No');

        if ($taxYear === 'No') {
            return self::SUCCESS;
        }

        return $this->summary($repository, $taxYear, $availableTaxYears);
    }
}
