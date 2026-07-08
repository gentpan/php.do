<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'qf_settings';

    protected $primaryKey = 'setting_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $row = static::query()->find($key);

        return $row ? (string) $row->setting_value : $default;
    }

    public static function putValue(string $key, ?string $value): void
    {
        static::query()->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => (string) $value]
        );
    }
}
