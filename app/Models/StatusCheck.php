<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'status_monitor_id',
        'status',
        'http_status_code',
        'response_time_ms',
        'error_message',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(StatusMonitor::class, 'status_monitor_id');
    }
}
