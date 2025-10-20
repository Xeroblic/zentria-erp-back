<?php

namespace Tests\Seeders;

use Illuminate\Database\Seeder;

class TestBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([\Database\Seeders\RolesAndPermissionsSeeder::class]);
    }
}

