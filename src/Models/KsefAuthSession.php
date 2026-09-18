<?php

namespace AdamDziuk\LaravelKsef\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $nip
 * @property string $environment
 * @property string $access_token
 * @property Carbon $access_token_valid_until
 * @property string $refresh_token
 * @property Carbon $refresh_token_valid_until
 */
class KsefAuthSession extends Model
{
    protected $table = 'ksef_auth_sessions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'access_token_valid_until' => 'datetime',
            'refresh_token_valid_until' => 'datetime',
        ];
    }

    public function accessTokenIsValid(): bool
    {
        return $this->access_token_valid_until->isFuture();
    }

    public function refreshTokenIsValid(): bool
    {
        return $this->refresh_token_valid_until->isFuture();
    }
}
