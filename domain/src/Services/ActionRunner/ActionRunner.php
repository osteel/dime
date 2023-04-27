<?php

declare(strict_types=1);

namespace Domain\Services\ActionRunner;

interface ActionRunner
{
    public function run(object $action): void;
}
