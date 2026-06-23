<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ejercicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'zona',
        'nivel',
        'posicion',
        'duracionMinutos',
        'repeticiones',
        'descripcion',
        'advertencia',
        'imagen'
    ];
}

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ejercicios', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->string('zona');
            $table->string('nivel');
            $table->string('posicion');

            $table->integer('duracionMinutos');
            $table->integer('repeticiones');

            $table->text('descripcion');
            $table->text('advertencia');
            $table->string('imagen')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ejercicios');
    }
};
