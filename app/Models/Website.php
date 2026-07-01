<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    protected $fillable = [
        'server_id', 'url', 'name', 'check_interval_seconds',
        'expected_status_code', 'expected_keyword', 'timeout_seconds',
        'enabled', 'last_checked_at', 'last_status', 'last_http_code',
        'last_response_ms', 'last_uptime_percent',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(WebsiteCheck::class);
    }
}
