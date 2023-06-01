<?php

namespace App\Services\Presenter;

interface PresenterContract
{
    /** Display an error message. */
    public function error(string $message): void;

    /** Display an information message. */
    public function info(string $message): void;

    /** Display a success message. */
    public function success(string $message): void;

    /** Display a warning message. */
    public function warning(string $message): void;

    /**
     * Display multiple choices.
     *
     * @param list<string> $choices
     */
    public function choice(string $question, array $choices, ?string $default = null): string;

    /** Display a tax year's summary. */
    public function summary(
        string $taxYear,
        string $proceeds,
        string $costBasis,
        string $nonAttributableAllowableCost,
        string $totalCostBasis,
        string $capitalGain,
        string $income
    ): void;

    /** Initiate a progress bar. */
    public function progressStart(int $size): void;

    /** Advance a progress bar. */
    public function progressAdvance(int $step = 1): void;

    /** Complete a progress bar. */
    public function progressComplete(): void;
}
