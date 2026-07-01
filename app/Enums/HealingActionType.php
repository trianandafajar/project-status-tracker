<?php

namespace App\Enums;

enum HealingActionType: string
{
    case RestartService = 'restart_service';
    case ClearCache = 'clear_cache';
    case RunCommand = 'run_command';
    case ScaleWorker = 'scale_worker';
}
