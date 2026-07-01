<?php

namespace App\Enums;

enum ServiceType: string
{
    case Nginx = 'nginx';
    case PhpFpm = 'php-fpm';
    case Pm2 = 'pm2';
    case Redis = 'redis';
    case Mysql = 'mysql';
    case Queue = 'queue';
    case Docker = 'docker';
}
