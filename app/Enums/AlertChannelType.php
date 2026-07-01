<?php

namespace App\Enums;

enum AlertChannelType: string
{
    case Telegram = 'telegram';
    case Discord = 'discord';
    case Email = 'email';
}
