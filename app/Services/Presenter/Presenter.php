<?php

namespace App\Services\Presenter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Presenter implements PresenterContract
{
    private SymfonyStyle $ui;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->ui = new SymfonyStyle($input, $output);
    }

    /** Display an error message. */
    public function error(string $message): void
    {
        $this->ui->block(sprintf(' 🚨  %s', $message), null, 'fg=white;bg=red', ' ', true);
    }

    /** Display an information message. */
    public function info(string $message): void
    {
        $this->ui->block(sprintf(' ℹ️   %s', $message), null, 'fg=white;bg=blue', ' ', true);
    }

    /** Display a success message. */
    public function success(string $message): void
    {
        $this->ui->block(sprintf(' 🎉  %s', $message), null, 'fg=white;bg=green', ' ', true);
    }

    /** Display a warning message. */
    public function warning(string $message): void
    {
        $this->ui->block(sprintf(' ⚠️   %s', $message), null, 'fg=yellow;bg=default', ' ', true);
    }

    /**
     * Display multiple choices.
     *
     * @param list<string> $choices
     *
     * @codeCoverageIgnore
     */
    public function choice(string $question, array $choices, ?string $default = null): string
    {
        $choice = $this->ui->choice($question, $choices, $default);

        assert(is_string($choice));

        return $choice;
    }

    /** Display a tax year's summary. */
    public function summary(
        string $taxYear,
        string $proceeds,
        string $costBasis,
        string $nonAttributableAllowableCost,
        string $totalCostBasis,
        string $capitalGain,
        string $income,
    ): void {
        $this->info(sprintf('Summary for tax year %s', $taxYear));

        $this->ui->table(
            ['Proceeds', 'Cost basis', 'Non-attributable allowable cost', 'Total cost basis', 'Capital gain or loss', 'Income'],
            [[$proceeds, $costBasis, $nonAttributableAllowableCost, $totalCostBasis, $capitalGain, $income]],
        );
    }

    /** Initiate a progress bar. */
    public function progressStart(int $size): void
    {
        $this->ui->progressStart($size);
    }

    /** Advance a progress bar. */
    public function progressAdvance(int $step = 1): void
    {
        $this->ui->progressAdvance($step);
    }

    /** Complete a progress bar. */
    public function progressComplete(): void
    {
        $this->ui->progressFinish();
    }
}
