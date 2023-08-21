<?php

namespace App\Commands;

use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository;
use Domain\Aggregates\TaxYear\ValueObjects\Exceptions\TaxYearIdException;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Repositories\SummaryRepository;
use Illuminate\Database\QueryException;

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
    public function handle(SummaryRepository $summaryRepository, TaxYearSummaryRepository $taxYearRepository): int
    {
        $taxYear = $this->argument('taxyear');

        try {
            $this->validateTaxYear($taxYear);
        } catch (TaxYearIdException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        try {
            $availableTaxYears = array_filter(array_map(
                fn (mixed $taxYearId) => $taxYearId instanceof TaxYearId ? $taxYearId->toString() : null,
                array_column($taxYearRepository->all(), 'tax_year_id'),
            ));
        } catch (QueryException) {
            $availableTaxYears = [];
        }

        if (empty($availableTaxYears)) {
            $this->warning('No tax year to review. Please submit transactions first, using the `process` command');

            return self::SUCCESS;
        }

        if (! is_null($taxYear) && ! in_array($taxYear, $availableTaxYears)) {
            $this->warning('This tax year is not available');
            $taxYear = null;
        }

        if (is_null($taxYear)) {
            $this->info(sprintf('Current fiat balance: %s', $summaryRepository->get()?->fiat_balance ?? ''));
        }

        // Order tax years from more recent to older
        rsort($availableTaxYears);

        $taxYear ??= count($availableTaxYears) === 1
            ? $availableTaxYears[0]
            : $this->choice('Please select a tax year for details', $availableTaxYears, $availableTaxYears[0]);

        assert(is_string($taxYear));

        return $this->summary($taxYearRepository, $taxYear, $availableTaxYears);
    }

    /** @param list<string> $availableTaxYears */
    private function summary(TaxYearSummaryRepository $repository, string $taxYear, array $availableTaxYears): int
    {
        $taxYearId = TaxYearId::fromString($taxYear);
        $taxYearSummary = $repository->find($taxYearId);

        assert($taxYearSummary instanceof TaxYearSummary);

        $this->displaySummary(
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

        $taxYear = $this->choice('Review another tax year?', ['No', ...$availableTaxYears], 'No');

        if ($taxYear === 'No') {
            return self::SUCCESS;
        }

        return $this->summary($repository, $taxYear, $availableTaxYears);
    }

    /** Display a tax year's summary. */
    private function displaySummary(
        string $taxYear,
        string $proceeds,
        string $costBasis,
        string $nonAttributableAllowableCost,
        string $totalCostBasis,
        string $capitalGain,
        string $income,
    ): void {
        $this->info(sprintf('Summary for tax year %s', $taxYear));

        $this->table(
            ['Proceeds', 'Cost basis', 'Non-attributable allowable cost', 'Total cost basis', 'Capital gain or loss', 'Income'],
            [[$proceeds, $costBasis, $nonAttributableAllowableCost, $totalCostBasis, $capitalGain, $income]],
        );
    }

    /** @throws TaxYearIdException */
    private function validateTaxYear(mixed $taxYear): void
    {
        is_null($taxYear) || TaxYearId::fromString(is_string($taxYear) ? $taxYear : '');
    }
}
