<?php

namespace App\Models;

use App\Support\ForumAvatar;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    use Notifiable;

    protected $table = 'qf_users';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'nickname',
        'email',
        'email_bound_at',
        'avatar',
        'signature',
        'gender',
        'custom_field',
        'is_admin',
        'is_moderator',
        'moderator_delete_limit',
        'status',
        'mute_until',
        'coins',
        'reply_count',
        'points',
        'group_id',
        'notification_sound_enabled',
        'ip',
        'timezone',
        'created_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_moderator' => 'boolean',
            'status' => 'integer',
            'mute_until' => 'datetime',
            'email_bound_at' => 'datetime',
            'created_at' => 'datetime',
            'notification_sound_enabled' => 'boolean',
            // password NOT cast to hashed here — forum uses password_hash; we hash manually on update
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin && (int) $this->status === 1;
    }

    public function getFilamentName(): string
    {
        $name = trim((string) ($this->nickname ?: $this->username));

        return $name !== '' ? $name : 'Admin';
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return ForumAvatar::url($this->avatar, $this->email, 96);
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->created_at)) {
                $user->created_at = now();
            }
            if ($user->status === null) {
                $user->status = 1;
            }
        });
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // qf_users has no remember_token column
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }
}
