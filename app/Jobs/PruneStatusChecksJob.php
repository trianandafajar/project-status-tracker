<?php

namespace App\Jobs;

use App\Models\StatusCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PruneStatusChecksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const RETENTION_HOURS = 24;

    public function handle(): void
    {
        StatusCheck::query()
            ->where('checked_at', '<', now()->subHours(self::RETENTION_HOURS))
            ->delete();
    }
}
