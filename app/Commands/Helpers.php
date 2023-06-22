<?php

namespace App\Commands;

final class Helpers
{
    public static function installedViaComposer(): bool
    {
        return str_contains(__DIR__, '.composer/vendor');
    }

    public static function installedViaDocker(): bool
    {
        return str_contains(__DIR__, '/docker');
    }

    public static function installedViaPhar(): bool
    {
        return ! self::installedViaComposer() && ! self::installedViaDocker();
    }
}
