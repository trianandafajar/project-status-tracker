<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Running = 'running';
    case Stopped = 'stopped';
    case Restarting = 'restarting';
    case Unknown = 'unknown';
}
