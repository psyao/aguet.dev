<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Model events stay on: Velox forgets its
     * cached Settings (the Home Page among them) when a Setting is saved.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            HomeSeeder::class,
            ProjectSeeder::class,
            SkillGroupSeeder::class,
        ]);
    }
}
