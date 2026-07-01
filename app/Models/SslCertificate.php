<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslCertificate extends Model
{
    protected $fillable = [
        'server_id', 'domain', 'issuer', 'valid_from', 'valid_to',
        'days_remaining', 'status', 'san', 'error_message', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'san' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
