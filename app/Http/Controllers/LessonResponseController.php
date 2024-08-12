<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LessonResponse;
use App\Models\LessonResponseAnswer;
use App\Models\Lesson;
use Illuminate\Support\Facades\Log;

class LessonResponseController extends Controller
{
    public function store(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'answers' => 'required|array',
            'answers.*' => 'required|exists:answers,id',
        ]);

        $userId = auth()->user()->id; // Obtener el ID del usuario autenticado
        $lessonId = $request->lesson_id;

        // Log para verificar lo que se recibe
        Log::info('Datos recibidos en store:', ['user_id' => $userId, 'lesson_id' => $lessonId, 'answers' => $request->answers]);

        // Crear un registro de respuesta a la lección
        $lessonResponse = LessonResponse::create([
            'user_id' => $userId,
            'lesson_id' => $lessonId,
            'response' => '', // Valor inicial, puedes ajustarlo según la lógica
        ]);

        // Guardar las respuestas seleccionadas
        foreach ($request->answers as $answerId) {
            Log::info('Guardando respuesta:', ['lesson_response_id' => $lessonResponse->id, 'answer_id' => $answerId]);
            LessonResponseAnswer::create([
                'lesson_response_id' => $lessonResponse->id,
                'answer_id' => $answerId,
            ]);
        }

        return response()->json(['message' => 'Respuestas guardadas con éxito.']);
    }

    public function show($lessonId)
    {
        $userId = auth()->user->id();
    
        // Validar si el usuario está inscrito en el curso de la lección
        $lesson = Lesson::findOrFail($lessonId);
        $course = $lesson->section->course;
        
        if (!$course->students()->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'No estás inscrito en este curso.'], 403);
        }
    
        // Obtener las respuestas del usuario autenticado para una lección específica
        $lessonResponse = LessonResponse::with('answers')
                            ->where('user_id', $userId)
                            ->where('lesson_id', $lessonId)
                            ->first();
    
        if (!$lessonResponse) {
            return response()->json(['message' => 'No se encontraron respuestas para esta lección.'], 404);
        }
    
        return response()->json($lessonResponse);
    }
    

    public function submit(Request $request, Lesson $lesson)
    {
        // Validar las respuestas enviadas
        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.selected_answers' => 'required|array',
            'answers.*.selected_answers.*' => 'exists:answers,id',
        ]);

        $userId = auth()->user()->id;  // Obtener el ID del usuario autenticado
        Log::info('Datos validados en submit:', ['user_id' => $userId, 'lesson_id' => $lesson->id, 'answers' => $validated['answers']]);

        // Iterar sobre las respuestas enviadas
        foreach ($validated['answers'] as $answerData) {
            Log::info('Procesando respuesta para la pregunta:', ['question_id' => $answerData['question_id']]);

            $response = LessonResponse::create([
                'user_id' => $userId,
                'lesson_id' => $lesson->id,
                'response' => '', // Puedes inicializar el campo `response` aquí si es necesario
            ]);

            // Guardar las respuestas seleccionadas
            foreach ($answerData['selected_answers'] as $answerId) {
                // Verificar si es un array y extraer el valor correcto
                if (is_array($answerId)) {
                    $answerId = array_values($answerId)[0];
                }
                
                Log::info('Guardando respuesta seleccionada:', ['lesson_response_id' => $response->id, 'answer_id' => $answerId]);
                LessonResponseAnswer::create([
                    'lesson_response_id' => $response->id,
                    'answer_id' => $answerId,
                ]);
            }
        }

        return response()->json(['message' => 'Respuestas enviadas correctamente'], 200);
    }
}
