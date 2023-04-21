<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingAssetAcquired implements SerializablePayload
{
    public function __construct(
        public SharePoolingAssetAcquisition $sharePoolingAssetAcquisition,
    ) {
    }

    /** @return array{share_pooling_asset_acquisition:array{id:string,date:string,quantity:string,cost_basis:array{quantity:string,currency:string},same_day_quantity:string,thirty_day_quantity:string}} */
    public function toPayload(): array
    {
        return ['share_pooling_asset_acquisition' => $this->sharePoolingAssetAcquisition->toPayload()];
    }

    /** @param array{share_pooling_asset_acquisition:array{id:string,date:string,quantity:string,cost_basis:array{quantity:string,currency:string},same_day_quantity:string,thirty_day_quantity:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(SharePoolingAssetAcquisition::fromPayload($payload['share_pooling_asset_acquisition']));
    }
}
