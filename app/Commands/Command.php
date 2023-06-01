<?php

namespace App\Commands;

use App\Services\Presenter\PresenterContract;
use LaravelZero\Framework\Commands\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    protected PresenterContract $presenter;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->presenter = resolve(PresenterContract::class, [$input, $output]); // @phpstan-ignore-line

        return parent::execute($input, $output);
    }
}
