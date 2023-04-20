<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingAssetDisposedOf implements SerializablePayload
{
    public function __construct(
        public SharePoolingAssetDisposal $sharePoolingAssetDisposal,
    ) {
    }

    /** @return array{share_pooling_asset_disposal:array{id:string,date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_allocation:array{allocation:array<string,string>},thirty_day_quantity_allocation:array{allocation:array<string,string>},processed:bool}} */
    public function toPayload(): array
    {
        return ['share_pooling_asset_disposal' => $this->sharePoolingAssetDisposal->toPayload()];
    }

    /** @param array{share_pooling_asset_disposal:array{id:string,date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_allocation:array{allocation:array<string,string>},thirty_day_quantity_allocation:array{allocation:array<string,string>},processed:bool}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(SharePoolingAssetDisposal::fromPayload($payload['share_pooling_asset_disposal']));
    }
}
