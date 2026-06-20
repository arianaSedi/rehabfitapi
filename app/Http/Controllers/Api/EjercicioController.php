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
            'total' => $ejercicios->count(),
            'ejercicios' => $ejercicios,
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

        return response()->json($ejercicio, 201);
    }


    public function show($id)
    {
        return Ejercicio::findOrFail($id);
    }

    public function porZona($zona)
    {
        $resultado = Ejercicio::whereRaw('LOWER(zona) = ?', [mb_strtolower($zona)])->get();

        return response()->json([
            'total' => $resultado->count(),
            'zona' => $zona,
            'ejercicios' => $resultado,
        ]);
    }

    public function porNivel($nivel)
    {
        $resultado = Ejercicio::whereRaw('LOWER(nivel) = ?', [mb_strtolower($nivel)])->get();

        return response()->json([
            'total' => $resultado->count(),
            'nivel' => $nivel,
            'ejercicios' => $resultado,
        ]);
    }

    public function buscar(Request $request)
    {
        $texto = $request->texto;

        if (!$texto) {
            return response()->json([
                'mensaje' => 'Debes enviar un texto de búsqueda. Ejemplo: /buscar?texto=rodilla'
            ], 400);
        }

        $resultado = Ejercicio::where('nombre', 'like', "%$texto%")
            ->orWhere('zona', 'like', "%$texto%")
            ->orWhere('nivel', 'like', "%$texto%")
            ->orWhere('posicion', 'like', "%$texto%")
            ->orWhere('descripcion', 'like', "%$texto%")
            ->get();

        return response()->json([
            'total' => $resultado->count(),
            'busqueda' => $texto,
            'ejercicios' => $resultado,
        ]);
    }
}