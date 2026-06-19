<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Ejercicio;

class IAController extends Controller
{
    /**
     * =========================
     * HELPERS (equivalentes a las funciones sueltas de server.js)
     * =========================
     */

    /**
     * Normaliza texto: minúsculas y sin acentos (equivalente a normalizarTexto en Node).
     */
    private function normalizarTexto($texto)
    {
        $texto = mb_strtolower((string) $texto, 'UTF-8');

        // Quita acentos/diacríticos (equivalente a normalize("NFD") + regex de Node)
        $texto = preg_replace('/\p{Mn}/u', '', \Normalizer::normalize($texto, \Normalizer::FORM_D));

        return $texto;
    }

    /**
     * Detecta la zona del cuerpo y las palabras clave asociadas a partir de la consulta.
     * Equivalente a detectarZonaYPrioridades en Node.
     */
    private function detectarZonaYPrioridades($consulta)
    {
        $texto = $this->normalizarTexto($consulta);

        if (str_contains($texto, "rodilla") || str_contains($texto, "rodillas")) {
            return [
                "zonaDetectada" => "Rodilla",
                "palabrasClave" => ["rodilla", "cuadriceps", "talon", "pierna"],
            ];
        }

        if (
            str_contains($texto, "tobillo") ||
            str_contains($texto, "tobillos") ||
            str_contains($texto, "pie") ||
            str_contains($texto, "pies")
        ) {
            return [
                "zonaDetectada" => "Tobillo",
                "palabrasClave" => ["tobillo", "pie", "talon", "pantorrilla"],
            ];
        }

        if (str_contains($texto, "hombro") || str_contains($texto, "hombros")) {
            return [
                "zonaDetectada" => "Hombro",
                "palabrasClave" => ["hombro", "escapular", "omoplatos", "brazo"],
            ];
        }

        if (
            str_contains($texto, "muneca") ||
            str_contains($texto, "muñeca") ||
            str_contains($texto, "munecas") ||
            str_contains($texto, "muñecas")
        ) {
            return [
                "zonaDetectada" => "Muñeca",
                "palabrasClave" => ["muneca", "muñeca"],
            ];
        }

        if (
            str_contains($texto, "mano") ||
            str_contains($texto, "manos") ||
            str_contains($texto, "dedo") ||
            str_contains($texto, "dedos")
        ) {
            return [
                "zonaDetectada" => "Mano",
                "palabrasClave" => ["mano", "dedos"],
            ];
        }

        if (
            str_contains($texto, "espalda") ||
            str_contains($texto, "lumbar") ||
            str_contains($texto, "columna") ||
            str_contains($texto, "lumbalgia")
        ) {
            return [
                "zonaDetectada" => "Espalda",
                "palabrasClave" => ["espalda", "lumbar", "columna", "omoplatos"],
            ];
        }

        if (
            str_contains($texto, "cuello") ||
            str_contains($texto, "cervical") ||
            str_contains($texto, "cervicales")
        ) {
            return [
                "zonaDetectada" => "Cuello",
                "palabrasClave" => ["cuello", "cervical"],
            ];
        }

        if (
            str_contains($texto, "pierna") ||
            str_contains($texto, "piernas") ||
            str_contains($texto, "cadera")
        ) {
            return [
                "zonaDetectada" => "Pierna",
                "palabrasClave" => ["pierna", "cadera", "isquiotibiales"],
            ];
        }

        return [
            "zonaDetectada" => "General",
            "palabrasClave" => ["general", "movilidad", "suave", "respiracion", "sentado"],
        ];
    }

    /**
     * Busca ejercicios candidatos según la consulta y el dolor actual.
     * Equivalente a buscarEjerciciosCandidatos en Node, pero usando Ejercicio::all().
     */
    private function buscarEjerciciosCandidatos($consulta, $dolorActual)
    {
        $texto = $this->normalizarTexto($consulta);
        $deteccion = $this->detectarZonaYPrioridades($consulta);
        $zonaDetectada = $deteccion["zonaDetectada"];
        $palabrasClave = $deteccion["palabrasClave"];

        $ejercicios = Ejercicio::all();

        $candidatos = $ejercicios->filter(function ($ejercicio) use ($palabrasClave) {
            $contenido = $this->normalizarTexto(
                $ejercicio->nombre . " " .
                $ejercicio->zona . " " .
                $ejercicio->nivel . " " .
                $ejercicio->posicion . " " .
                $ejercicio->descripcion
            );

            foreach ($palabrasClave as $palabra) {
                if (str_contains($contenido, $this->normalizarTexto($palabra))) {
                    return true;
                }
            }

            return false;
        });

        if ($dolorActual >= 7) {
            $candidatos = $candidatos->filter(function ($ejercicio) {
                return $this->normalizarTexto($ejercicio->nivel) === "baja";
            });
        }

        if (str_contains($texto, "sentado") || str_contains($texto, "sentada") || str_contains($texto, "silla")) {
            $sentados = $candidatos->filter(function ($ejercicio) {
                return str_contains($this->normalizarTexto($ejercicio->posicion), "sentado");
            });

            if ($sentados->count() > 0) {
                $candidatos = $sentados;
            }
        }

        if (str_contains($texto, "acostado") || str_contains($texto, "acostada") || str_contains($texto, "cama")) {
            $acostados = $candidatos->filter(function ($ejercicio) {
                return str_contains($this->normalizarTexto($ejercicio->posicion), "acostado");
            });

            if ($acostados->count() > 0) {
                $candidatos = $acostados;
            }
        }

        if (str_contains($texto, "de pie") || str_contains($texto, "parado") || str_contains($texto, "parada")) {
            $dePie = $candidatos->filter(function ($ejercicio) {
                return str_contains($this->normalizarTexto($ejercicio->posicion), "de pie");
            });

            if ($dePie->count() > 0) {
                $candidatos = $dePie;
            }
        }

        if ($candidatos->count() === 0) {
            $candidatos = $ejercicios->filter(function ($ejercicio) use ($zonaDetectada) {
                return $this->normalizarTexto($ejercicio->zona) === $this->normalizarTexto($zonaDetectada);
            });
        }

        if ($candidatos->count() === 0) {
            $candidatos = $ejercicios->filter(function ($ejercicio) {
                return $this->normalizarTexto($ejercicio->nivel) === "baja";
            });
        }

        return $candidatos->values()->take(8);
    }

    /**
     * Construye el texto de recomendación de respaldo (fallback) cuando falla la IA.
     */
    private function construirRecomendacionRespaldo($zonaDetectada, $movilidad, $objetivo, $apoyoFisico, $dolor)
    {
        return
            "No se pudo consultar Gemini en este momento, pero RehabFit generó una recomendación de respaldo con base en tu perfil y el catálogo de ejercicios.\n\n" .
            "Zona detectada: {$zonaDetectada}.\n\n" .
            "Cómo estructurar mejor tu consulta:\n" .
            "• Indica la zona afectada: rodilla, hombro, espalda, tobillo, mano, cuello, etc.\n" .
            "• Indica tu dolor actual del 0 al 10.\n" .
            "• Explica tu nivel de movilidad: baja, media o alta.\n" .
            "• Menciona tu objetivo: reducir dolor, mejorar movilidad o fortalecer.\n" .
            "• Indica si necesitas apoyo físico, silla, bastón o ayuda de otra persona.\n\n" .
            "Tomando en cuenta tu perfil:\n" .
            "• Movilidad: " . ($movilidad ?: "No especificada") . "\n" .
            "• Objetivo: " . ($objetivo ?: "No especificado") . "\n" .
            "• Apoyo físico: " . ($apoyoFisico ?: "No especificado") . "\n" .
            "• Dolor actual: {$dolor}/10\n\n" .
            "Recomendación general: realiza ejercicios suaves, lentos y controlados. Detente si el dolor aumenta, aparece inflamación, mareo o inestabilidad.";
    }

    /**
     * =========================
     * ENDPOINT PRINCIPAL
     * =========================
     */
    public function recomendacion(Request $request)
    {
        // Se capturan aquí para que estén disponibles también dentro del catch
        $consulta = $request->input('consulta');
        $movilidad = $request->input('movilidad');
        $objetivo = $request->input('objetivo');
        $apoyoFisico = $request->input('apoyoFisico');
        $uid = $request->input('uid');
        $dolor = (int) ($request->input('dolorActual') ?? 0);

        try {

            // =========================
            // 1. VALIDACIÓN DE CONSULTA
            // =========================
            if (!$consulta || mb_strlen(trim($consulta)) < 10) {
                return response()->json([
                    "ok" => false,
                    "recomendacion" => "Escribe una consulta más detallada. Ejemplo: Tengo dolor leve en rodilla, movilidad baja y quiero ejercicios suaves sentado.",
                    "ejerciciosRecomendados" => []
                ], 400);
            }

            // =========================
            // 2. DETECCIÓN DE ZONA Y CANDIDATOS
            // =========================
            $deteccion = $this->detectarZonaYPrioridades($consulta);
            $zonaDetectada = $deteccion["zonaDetectada"];

            $candidatos = $this->buscarEjerciciosCandidatos($consulta, $dolor);

            // =========================
            // 3. PROMPT PARA GEMINI
            // =========================
            $prompt = "
Eres un asistente de apoyo para una app de rehabilitación llamada RehabFit.

IMPORTANTE:
- No eres médico.
- No debes diagnosticar enfermedades.
- No debes prometer curación.
- No recomiendes medicamentos.
- Debes recomendar únicamente ejercicios del catálogo proporcionado.
- Si el dolor es alto, recomienda ejercicios de baja intensidad y consultar a un profesional.
- Responde en español claro, breve y seguro.
- No repitas exactamente la misma recomendación en todas las consultas.
- Adapta la recomendación a la zona afectada detectada.
- Menciona por qué los ejercicios elegidos son adecuados.

Datos del usuario:
- UID: " . ($uid ?: "No especificado") . "
- Consulta del usuario: {$consulta}
- Zona detectada por el sistema: {$zonaDetectada}
- Movilidad: " . ($movilidad ?: "No especificada") . "
- Objetivo: " . ($objetivo ?: "No especificado") . "
- Apoyo físico: " . ($apoyoFisico ?: "No especificado") . "
- Dolor actual: {$dolor}/10

Catálogo de ejercicios candidatos:
" . json_encode($candidatos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

Devuelve SOLO un JSON válido con esta estructura exacta:

{
  \"recomendacion\": \"Texto breve de recomendación. Debe mencionar la zona afectada, explicar cómo estructurar mejor la consulta y agregar una advertencia de seguridad.\",
  \"idsEjercicios\": [1, 2, 3]
}

Reglas:
- idsEjercicios debe tener máximo 5 IDs.
- Los IDs deben existir en el catálogo candidato.
- No inventes ejercicios.
- La recomendación debe variar según la zona afectada y el objetivo del usuario.
- Si el usuario pide ejercicios sentado, prioriza ejercicios en posición sentado.
- Si el usuario menciona espalda, no recomiendes rodilla salvo que esté en el catálogo candidato.
- No agregues texto fuera del JSON.
";

            if (!env("API_KEY_GEMINI")) {
                throw new \Exception("Falta configurar API_KEY_GEMINI");
            }

            // =========================
            // 4. LLAMADA A GEMINI (modelo gemini-2.5-flash, igual que server.js)
            // =========================
            $response = Http::withHeaders([
                "Content-Type" => "application/json"
            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . env("API_KEY_GEMINI"),
                [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $prompt]
                            ]
                        ]
                    ]
                ]
            );

            $textoIA = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? "";

            $textoIA = trim(str_replace(["```json", "```"], "", $textoIA));

            $respuestaIA = json_decode($textoIA, true);

            // =========================
            // 5. FALLBACK SI EL JSON DE LA IA NO ES VÁLIDO
            // =========================
            if (!$respuestaIA) {
                $respuestaIA = [
                    "recomendacion" => $textoIA,
                    "idsEjercicios" => $candidatos->take(5)->pluck('id')->values()->all(),
                ];
            }

            // =========================
            // 6. MAPEAR IDS A EJERCICIOS (dentro de los candidatos, igual que Node)
            // =========================
            $ids = is_array($respuestaIA['idsEjercicios'] ?? null) ? $respuestaIA['idsEjercicios'] : [];

            $ejerciciosRecomendados = $candidatos->filter(function ($ejercicio) use ($ids) {
                return in_array($ejercicio->id, $ids);
            })->values();

            // =========================
            // 7. RESPUESTA FINAL
            // =========================
            return response()->json([
                "ok" => true,
                "zonaDetectada" => $zonaDetectada,
                "recomendacion" => $respuestaIA['recomendacion'] ?? "Recomendación generada correctamente.",
                "ejerciciosRecomendados" => $ejerciciosRecomendados->count() > 0
                    ? $ejerciciosRecomendados
                    : $candidatos->take(5)->values(),
            ]);

        } catch (\Exception $e) {

            // =========================
            // FALLBACK GENERAL SI FALLA GEMINI U OTRA PARTE DEL PROCESO
            // =========================
            report($e);

            $dolorFallback = $dolor ?: 0;
            $deteccionFallback = $this->detectarZonaYPrioridades($consulta ?? "");
            $zonaDetectadaFallback = $deteccionFallback["zonaDetectada"];
            $candidatosFallback = $this->buscarEjerciciosCandidatos($consulta ?? "", $dolorFallback);

            $recomendacionRespaldo = $this->construirRecomendacionRespaldo(
                $zonaDetectadaFallback,
                $movilidad,
                $objetivo,
                $apoyoFisico,
                $dolorFallback
            );

            return response()->json([
                "ok" => true,
                "zonaDetectada" => $zonaDetectadaFallback,
                "recomendacion" => $recomendacionRespaldo,
                "ejerciciosRecomendados" => $candidatosFallback->take(5)->values(),
            ]);
        }
    }
}
