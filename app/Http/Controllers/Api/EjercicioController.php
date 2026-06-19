<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ejercicio;
use Illuminate\Http\Request;

class EjercicioController extends Controller
{
    public function index()
    {
        $ejercicios = Ejercicio::all();

        return response()->json([
            "total" => $ejercicios->count(),
            "ejercicios" => $ejercicios
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'zona' => 'required|string|max:100',
            'nivel' => 'required|string|max:50',
            'posicion' => 'required|string|max:100',
            'duracionMinutos' => 'required|integer',
            'repeticiones' => 'required|integer',
            'descripcion' => 'required|string',
            'advertencia' => 'required|string',
        ]);

        $ejercicio = Ejercicio::create($request->all());

        return response()->json([
            "message" => "Ejercicio creado correctamente",
            "ejercicio" => $ejercicio
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            "ejercicio" => Ejercicio::findOrFail($id)
        ]);
    }

    public function porZona($zona)
    {
        $ejercicios = Ejercicio::where('zona', $zona)->get();

        return response()->json([
            "total" => $ejercicios->count(),
            "ejercicios" => $ejercicios
        ]);
    }

    public function porNivel($nivel)
    {
        $ejercicios = Ejercicio::where('nivel', $nivel)->get();

        return response()->json([
            "total" => $ejercicios->count(),
            "ejercicios" => $ejercicios
        ]);
    }

    public function buscar(Request $request)
    {
        $texto = $request->texto;

        $ejercicios = Ejercicio::where('nombre', 'like', "%$texto%")
            ->orWhere('zona', 'like', "%$texto%")
            ->orWhere('nivel', 'like', "%$texto%")
            ->orWhere('descripcion', 'like', "%$texto%")
            ->get();

        return response()->json([
            "total" => $ejercicios->count(),
            "ejercicios" => $ejercicios
        ]);
    }
}