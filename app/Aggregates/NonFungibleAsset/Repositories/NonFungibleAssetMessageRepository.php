<?php

declare(strict_types=1);

namespace App\Aggregates\NonFungibleAsset\Repositories;

use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetMessageRepository as NonFungibleAssetMessageRepositoryInterface;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateUuidV4MessageRepository;

final class NonFungibleAssetMessageRepository extends IlluminateUuidV4MessageRepository implements NonFungibleAssetMessageRepositoryInterface
{
}
