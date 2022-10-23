<?php

namespace Domain\Section104Pool\Enums;

enum Section104PoolTransactionType: string
{
    case Acquisition = 'acquisition';
    case Disposal = 'disposal';
}
