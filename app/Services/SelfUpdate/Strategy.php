<?php

namespace App\Services\SelfUpdate;

use Humbug\SelfUpdate\Strategy\GithubStrategy;
use LaravelZero\Framework\Components\Updater\Strategy\StrategyInterface;
use Phar;

/** @codeCoverageIgnore */
final class Strategy extends GithubStrategy implements StrategyInterface
{
    /**
     * Returns the Download Url.
     *
     * @param array<mixed, mixed> $package
     */
    protected function getDownloadUrl(array $package): string
    {
        return sprintf('%s%s', parent::getDownloadUrl($package), basename(Phar::running()));
    }
}
