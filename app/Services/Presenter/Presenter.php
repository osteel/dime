<?php

namespace App\Services\Presenter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Presenter
{
    private SymfonyStyle $ui;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->ui = new SymfonyStyle($input, $output);
    }

    /** Display a success message. */
    public function info(string $message): void
    {
        $this->ui->block(sprintf(' ℹ️   %s', $message), null, 'fg=white;bg=blue', ' ', true);
    }

    /** Display a success message. */
    public function error(string $message): void
    {
        $this->ui->block(sprintf(' 🚨  %s', $message), null, 'fg=white;bg=red', ' ', true);
    }

    /** Display a success message. */
    public function success(string $message): void
    {
        $this->ui->block(sprintf(' 🎉  %s', $message), null, 'fg=white;bg=green', ' ', true);
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
