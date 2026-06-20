<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EjercicioController;
use App\Http\Controllers\Api\IAController;

Route::get('/ejercicios', [EjercicioController::class, 'index']);
Route::post('/ejercicios', [EjercicioController::class, 'store']);
Route::get('/ejercicios/{id}', [EjercicioController::class, 'show']);

Route::get('/ejercicios/zona/{zona}', [EjercicioController::class, 'porZona']);
Route::get('/ejercicios/nivel/{nivel}', [EjercicioController::class, 'porNivel']);

Route::get('/buscar', [EjercicioController::class, 'buscar']);


Route::post('/ia/recomendacion', [IAController::class, 'recomendacion']);

// =========================================================================
// RUTA TEMPORAL DE MANTENIMIENTO — solo para corregir los datos duplicados.
// Protegida con una clave secreta (variable de entorno MANTENIMIENTO_KEY).
// BORRAR ESTA RUTA (y el método en el controller) cuando ya no se necesite.
// =========================================================================
Route::get('/mantenimiento/reset-ejercicios', [EjercicioController::class, 'resetEjercicios']);