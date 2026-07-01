<?php

namespace App\Enums;

enum MetricType: string
{
    case Cpu = 'cpu';
    case Ram = 'ram';
    case Disk = 'disk';
    case NetworkIn = 'network_in';
    case NetworkOut = 'network_out';
}
