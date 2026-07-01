<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    /** @use HasFactory<\Database\Factories\ServerFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'host', 'port', 'username', 'auth_type', 'auth_key',
        'connection_type', 'status', 'health_score', 'last_checked_at', 'os', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
        ];
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }
}
