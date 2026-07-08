<?php

namespace App\Support;

use App\Models\Setting;

class ForumAvatar
{
    public static function url(?string $avatar, ?string $email = null, int $size = 160): string
    {
        $avatar = (string) $avatar;

        if ($avatar !== '' && ! self::isGeneratedPath($avatar)) {
            return self::toPublicUrl($avatar);
        }

        if (self::isChosenCartoonPath($avatar)) {
            return self::toPublicUrl($avatar);
        }

        $email = trim((string) $email);
        if ($email !== '' && self::gravatarEnabled()) {
            return self::gravatarUrl($email, $size);
        }

        return $avatar !== '' ? self::toPublicUrl($avatar) : '/assets/avatar-default.svg';
    }

    public static function toPublicUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
            return $path;
        }

        return '/'.ltrim($path, '/');
    }

    public static function isGeneratedPath(string $avatar): bool
    {
        return preg_match('#^assets/avatars/(user|demo|pick)-[a-zA-Z0-9_-]+\.svg$#', $avatar) === 1;
    }

    public static function isChosenCartoonPath(string $avatar): bool
    {
        return preg_match('#^assets/avatars/pick-[a-zA-Z0-9_-]+\.svg$#', $avatar) === 1;
    }

    public static function gravatarEnabled(): bool
    {
        return (int) Setting::getValue('avatar_gravatar_enabled', '1') === 1;
    }

    public static function gravatarUrl(string $email, int $size = 160): string
    {
        $hash = md5(strtolower(trim($email)));

        return 'https://gravatar.bluecdn.com/avatar/'.$hash.'?s='.max(1, $size).'&d=identicon';
    }
}
