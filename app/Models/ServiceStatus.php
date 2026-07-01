<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceStatus extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceStatusFactory> */
    use HasFactory;

    protected $table = 'service_status_history';
    public $timestamps = false;

    protected $fillable = [
        'service_id', 'status', 'output', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
