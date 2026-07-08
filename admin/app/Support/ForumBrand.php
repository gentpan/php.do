<?php

namespace App\Support;

use App\Models\Setting;

class ForumBrand
{
    public static function name(): string
    {
        $name = trim((string) Setting::getValue('site_name', ''));

        return $name !== '' ? $name : 'php.do';
    }

    public static function logoUrl(bool $forDarkBackground = true): string
    {
        return $forDarkBackground ? '/assets/logo-white.svg' : '/assets/logo.svg';
    }
}
