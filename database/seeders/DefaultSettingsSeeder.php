<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'Sentinel Server Monitor', 'type' => 'string'],
            ['key' => 'metrics_retention_days', 'value' => '30', 'type' => 'integer'],
            ['key' => 'logs_retention_days', 'value' => '14', 'type' => 'integer'],
            ['key' => 'alert_history_retention_days', 'value' => '90', 'type' => 'integer'],
            ['key' => 'audit_logs_retention_days', 'value' => '180', 'type' => 'integer'],
            ['key' => 'dashboard_refresh_interval_seconds', 'value' => '30', 'type' => 'integer'],
            ['key' => 'data_collection_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'alert_cooldown_minutes', 'value' => '5', 'type' => 'integer'],
        ];

        DB::table('settings')->insert($settings);
    }
}
