<?php

namespace App\Services\SelfUpdate;

use Humbug\SelfUpdate\Strategy\GithubStrategy;
use LaravelZero\Framework\Components\Updater\Strategy\StrategyInterface;

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
        return sprintf('%sdime', parent::getDownloadUrl($package));
    }
}
