<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DefaultUserSeeder::class,
            DefaultSettingsSeeder::class,
            DefaultAlertRulesSeeder::class,
        ]);

        if (app()->environment('local', 'development', 'testing')) {
            $this->call(Dev\DevSeeder::class);
        }
    }
}
