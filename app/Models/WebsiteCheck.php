<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'website_id', 'http_status_code', 'response_time_ms',
        'ssl_days_remaining', 'ssl_status', 'is_up',
        'error_message', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_up' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
