<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    protected $table = 'pd_user_groups';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'min_points',
        'is_system',
        'display_order',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UserGroup $group): void {
            if (empty($group->created_at)) {
                $group->created_at = now();
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'group_id');
    }
}
