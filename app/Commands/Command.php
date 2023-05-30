<?php

namespace App\Commands;

use App\Services\Presenter\Presenter;
use LaravelZero\Framework\Commands\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    protected Presenter $presenter;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->presenter = new Presenter($input, $output);

        return parent::execute($input, $output);
    }
}
