<?php

namespace App\Enums;

enum HealingStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
