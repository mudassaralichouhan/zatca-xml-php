<?php

namespace App\Helpers;

use App\Config\Config;

final class Helper
{
    public static function isProduction(): bool
    {
        return in_array(Config::get('APP_ENV'), ['pro','prod','production'], true);
    }
}
