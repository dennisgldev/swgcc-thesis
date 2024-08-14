<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LessonResponse;
use App\Models\LessonResponseAnswer;
use App\Models\Lesson;
use App\Models\Answer; // Asegúrate de importar el modelo Answer
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
        $userId = auth()->user()->id;

        // Validar si el usuario está inscrito en el curso de la lección
        $lesson = Lesson::findOrFail($lessonId);
        $course = $lesson->section->course;

        Log::info(!$course->students()->where('user_id', $userId)->exists());
//        if (!$course->students()->where('user_id', $userId)->exists()) {
//            return response()->json(['message' => 'No estás inscrito en este curso.'], 403);
//        }

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

        $totalScore = 0;
        $responseDetails = [];

        // Iterar sobre las respuestas enviadas
        foreach ($validated['answers'] as $answerData) {
            Log::info('Procesando respuesta para la pregunta:', ['question_id' => $answerData['question_id']]);

            // Buscar la pregunta para obtener su puntaje asignado
            $question = $lesson->questions()->where('id', $answerData['question_id'])->first();
            $questionPoints = $question->points;

            // Guardar las respuestas seleccionadas y calcular el score
            foreach ($answerData['selected_answers'] as $answerId) {
                $answer = Answer::find($answerId);
                if ($answer && $answer->correct) {
                    // Incrementa el score basado en la respuesta correcta usando el puntaje de la pregunta
                    $totalScore += $questionPoints;
                }

                // Guardar cada respuesta en los detalles del response
                $responseDetails[] = [
                    'question_id' => $answerData['question_id'],
                    'selected_answers' => $answerId,
                ];

                Log::info('Guardando respuesta seleccionada:', ['lesson_response_id' => $lesson->id, 'answer_id' => $answerId]);
            }
        }

        // Crear el registro de respuesta a la lección con el score calculado y el JSON de respuestas
        $response = LessonResponse::create([
            'user_id' => $userId,
            'lesson_id' => $lesson->id,
            'response' => json_encode($responseDetails), // Almacena el JSON de las respuestas
            'score' => $totalScore, // Almacena el score calculado
        ]);

        return response()->json(['message' => 'Respuestas enviadas correctamente', 'score' => $totalScore], 200);
    }
}
