<?php

namespace App\Services\SelfUpdate;

final class Helpers
{
    public static function installedViaComposer(): bool
    {
        dump(__DIR__);
        return str_contains(__DIR__, '.composer/vendor/bin');
    }
}
