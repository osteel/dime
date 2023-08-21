<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command as BaseCommand;

use function Laravel\Prompts\select;

abstract class Command extends BaseCommand
{
    /** Display an error message. */
    protected function failure(string $message): void
    {
        $this->output->block(sprintf(' 🚨  %s', $message), null, 'fg=white;bg=red', ' ', true);
    }

    /** Display an information message. */
    protected function hint(string $message): void
    {
        $this->output->block(sprintf(' ℹ️   %s', $message), null, 'fg=white;bg=blue', ' ', true);
    }

    /** Display a success message. */
    protected function success(string $message): void
    {
        $this->output->block(sprintf(' 🎉  %s', $message), null, 'fg=white;bg=green', ' ', true);
    }

    /** Display a warning message. */
    protected function warning(string $message): void
    {
        $this->output->block(sprintf(' ⚠️   %s', $message), null, 'fg=yellow;bg=default', ' ', true);
    }

    /**
     * Display multiple options.
     *
     * @param list<string> $options
     */
    protected function select(string $question, array $options, ?string $default = null): string
    {
        $choice = select($question, $options, $default);

        assert(is_string($choice));

        return $choice;
    }

    /** Initiate a progress bar. */
    protected function progressStart(int $size): void
    {
        $this->output->progressStart($size);
    }

    /** Advance a progress bar. */
    protected function progressAdvance(int $step = 1): void
    {
        $this->output->progressAdvance($step);
    }

    /** Complete a progress bar. */
    protected function progressComplete(): void
    {
        $this->output->progressFinish();
    }
}
