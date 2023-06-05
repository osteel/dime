<?php

namespace App\Commands;

use App\Services\Presenter\Presenter;
use App\Services\Presenter\PresenterContract;
use Illuminate\Contracts\Container\BindingResolutionException;
use LaravelZero\Framework\Application;
use LaravelZero\Framework\Commands\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    protected PresenterContract $presenter;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->presenter = resolve(PresenterContract::class); // @phpstan-ignore-line
        } catch (BindingResolutionException) {
            $this->presenter = new Presenter($input, $output);
            $this->app->singleton(PresenterContract::class, fn (Application $app) => $this->presenter);
        }

        return parent::execute($input, $output);
    }
}
