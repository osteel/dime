<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset;

use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use EventSauce\EventSourcing\AggregateRoot;

/** @extends AggregateRoot<SharePoolingAssetId> */
interface SharePoolingAssetContract extends AggregateRoot
{
    public function acquire(AcquireSharePoolingAsset $action): void;

    public function disposeOf(DisposeOfSharePoolingAsset $action): void;
}
