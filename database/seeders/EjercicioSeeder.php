<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ejercicio;
use Illuminate\Support\Facades\DB;

class EjercicioSeeder extends Seeder
{
    public function run()
    {
        DB::table('ejercicios')->truncate();

        $json = file_get_contents(database_path('seeders/ejercicios.json'));
        $ejercicios = json_decode($json, true);

        foreach ($ejercicios as $ejercicio) {
            unset($ejercicio['id']);
            Ejercicio::create($ejercicio);
        }
    }
}