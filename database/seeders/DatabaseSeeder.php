<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database, every Entry through Kami's SaveEntry.
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
