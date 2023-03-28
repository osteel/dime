<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposal;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingAssetDisposalReverted implements SerializablePayload
{
    public function __construct(
        public SharePoolingAssetDisposal $sharePoolingAssetDisposal,
    ) {
    }

    /** @return array{share_pooling_asset_disposal:array{date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_breakdown:array{breakdown:array<int,string>},thirty_day_quantity_breakdown:array{breakdown:array<int,string>},processed:bool,position:int|null}} */
    public function toPayload(): array
    {
        return ['share_pooling_asset_disposal' => $this->sharePoolingAssetDisposal->toPayload()];
    }

    /** @param array{share_pooling_asset_disposal:array{date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_breakdown:array{breakdown:array<int,string>},thirty_day_quantity_breakdown:array{breakdown:array<int,string>},processed:bool,position:int}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(SharePoolingAssetDisposal::fromPayload($payload['share_pooling_asset_disposal']));
    }
}
