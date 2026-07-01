<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultAlertRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'High CPU Usage',
                'metric_type' => 'cpu',
                'service_type' => null,
                'operator' => '>',
                'threshold' => 90,
                'severity' => 'critical',
                'enabled' => true,
                'cooldown_minutes' => 5,
            ],
            [
                'name' => 'High RAM Usage',
                'metric_type' => 'ram',
                'service_type' => null,
                'operator' => '>',
                'threshold' => 90,
                'severity' => 'warning',
                'enabled' => true,
                'cooldown_minutes' => 5,
            ],
            [
                'name' => 'High Disk Usage',
                'metric_type' => 'disk',
                'service_type' => null,
                'operator' => '>',
                'threshold' => 85,
                'severity' => 'warning',
                'enabled' => true,
                'cooldown_minutes' => 5,
            ],
            [
                'name' => 'Service Down',
                'metric_type' => null,
                'service_type' => 'service',
                'operator' => '==',
                'threshold' => 0,
                'severity' => 'critical',
                'enabled' => true,
                'cooldown_minutes' => 2,
            ],
        ];

        DB::table('alert_rules')->insert($rules);
    }
}
