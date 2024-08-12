<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Enrollment; // Asegúrate de que este modelo existe
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    public function enroll(Request $request, Course $course)
    {
        Log::info('Iniciando el proceso de inscripción para el curso:', ['course_id' => $course->id]);

        // Verifica si el usuario ya está inscrito
        $user = $request->user();
        $alreadyEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($alreadyEnrolled) {
            Log::info('Usuario ya inscrito en el curso', ['user_id' => $user->id, 'course_id' => $course->id]);
            return response()->json(['message' => 'Ya estás inscrito en este curso'], 400);
        }

        // Crea la inscripción
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        Log::info('Inscripción exitosa', ['user_id' => $user->id, 'course_id' => $course->id]);

        return response()->json(['message' => 'Inscripción exitosa', 'enrollment' => $enrollment], 201);
    }

    // Método para verificar si un usuario está inscrito en un curso
    public function isEnrolled(Request $request, Course $course)
    {
        $user = $request->user();
        $isEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        Log::info('Verificando inscripción', ['user_id' => $user->id, 'course_id' => $course->id, 'is_enrolled' => $isEnrolled]);

        return response()->json(['is_enrolled' => $isEnrolled]);
    }
}
