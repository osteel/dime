<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command as BaseCommand;

abstract class Command extends BaseCommand
{
    /**
     * Display an error message.
     *
     * @param string          $string
     * @param int|string|null $verbosity
     */
    public function error(mixed $string, mixed $verbosity = null): void
    {
        $this->output->block(sprintf(' 🚨  %s', $string), null, 'fg=white;bg=red', ' ', true);
    }

    /**
     * Display an information message.
     *
     * @param string          $string
     * @param int|string|null $verbosity
     */
    public function info(mixed $string, mixed $verbosity = null): void
    {
        $this->output->block(sprintf(' ℹ️   %s', $string), null, 'fg=white;bg=blue', ' ', true);
    }

    /** Display a success message. */
    public function success(string $message): void
    {
        $this->output->block(sprintf(' 🎉  %s', $message), null, 'fg=white;bg=green', ' ', true);
    }

    /** Display a warning message. */
    public function warning(string $message): void
    {
        $this->output->block(sprintf(' ⚠️   %s', $message), null, 'fg=yellow;bg=default', ' ', true);
    }

    /**
     * Display multiple choices.
     *
     * @param string          $question
     * @param list<string>    $choices
     * @param string|int|null $default
     * @param bool            $multiple
     *
     * @codeCoverageIgnore
     */
    public function choice(mixed $question, array $choices, mixed $default = null, mixed $attempts = null, mixed $multiple = false): string
    {
        $choice = parent::choice($question, $choices, $default);

        assert(is_string($choice));

        return $choice;
    }

    /** Initiate a progress bar. */
    public function progressStart(int $size): void
    {
        $this->output->progressStart($size);
    }

    /** Advance a progress bar. */
    public function progressAdvance(int $step = 1): void
    {
        $this->output->progressAdvance($step);
    }

    /** Complete a progress bar. */
    public function progressComplete(): void
    {
        $this->output->progressFinish();
    }
}
