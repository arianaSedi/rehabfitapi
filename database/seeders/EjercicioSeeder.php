<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ejercicio;

class EjercicioSeeder extends Seeder
{
    public function run()
    {
        $json = file_get_contents(database_path('seeders/ejercicios.json'));

        $ejercicios = json_decode($json, true);

        foreach ($ejercicios as $ejercicio) {
            unset($ejercicio['id']);

            // updateOrCreate evita duplicados sin importar cuántas veces
            // se corra el seeder: si ya existe un ejercicio con ese nombre,
            // lo actualiza en vez de crear uno nuevo.
            Ejercicio::updateOrCreate(
                ['nombre' => $ejercicio['nombre']],
                $ejercicio
            );
        }
    }
}