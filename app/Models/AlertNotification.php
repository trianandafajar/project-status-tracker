<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertNotification extends Model
{
    protected $fillable = [
        'alert_id', 'channel_id', 'sent_at', 'status', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(AlertChannel::class, 'channel_id');
    }
}
