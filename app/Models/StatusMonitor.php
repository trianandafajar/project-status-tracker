<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusMonitor extends Model
{
    protected $fillable = [
        'name',
        'group_name',
        'type',
        'url',
        'method',
        'expected_status_code',
        'expected_keyword',
        'request_body_template',
        'timeout_seconds',
        'enabled',
        'last_checked_at',
        'last_status',
        'last_http_code',
        'last_response_ms',
        'last_uptime_percent',
        'last_error_message',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_checked_at' => 'datetime',
            'last_uptime_percent' => 'decimal:2',
        ];
    }

    public function checks(): HasMany
    {
        return $this->hasMany(StatusCheck::class);
    }
}
