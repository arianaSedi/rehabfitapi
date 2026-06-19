<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\EjercicioSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            EjercicioSeeder::class,
        ]);
    }
}