<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use Domain\ValueObjects\Asset;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingAssetSet implements SerializablePayload
{
    public function __construct(
        public Asset $asset,
    ) {
    }

    /** @return array{asset:array{symbol:string,is_non_fungible:string}} */
    public function toPayload(): array
    {
        return ['asset' => $this->asset->toPayload()];
    }

    /** @param array{asset:array{symbol:string,is_non_fungible:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(Asset::fromPayload($payload['asset']));
    }
}
