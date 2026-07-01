<?php

namespace App\Enums;

enum ServerConnectionType: string
{
    case Ssh = 'ssh';
    case Agent = 'agent';
}
