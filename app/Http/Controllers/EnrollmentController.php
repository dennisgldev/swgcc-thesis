<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonResponse;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    public function enroll(Request $request, Course $course)
    {
        Log::info('Iniciando el proceso de inscripción para el curso:', ['course_id' => $course->id]);

        $user = $request->user();
        $alreadyEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($alreadyEnrolled) {
            Log::info('Usuario ya inscrito en el curso', ['user_id' => $user->id, 'course_id' => $course->id]);
            return response()->json(['message' => 'Ya estás inscrito en este curso'], 400);
        }

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'En curso',
        ]);

        Log::info('Inscripción exitosa y estado del curso actualizado para el usuario', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => $enrollment->status,
        ]);

        return response()->json([
            'message' => 'Inscripción exitosa',
            'enrollment' => [
                'course_id' => $course->id,
                'status' => $enrollment->status,
            ]
        ], 201);
    }

    public function isEnrolled(Request $request, Course $course)
    {
        $user = $request->user();
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        Log::info('Verificando inscripción', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => (bool) $enrollment,
            'status' => $enrollment ? $enrollment->status : 'Disponible',
        ]);

        return response()->json([
            'is_enrolled' => (bool) $enrollment,
            'status' => $enrollment ? $enrollment->status : 'Disponible',
        ]);
    }

    public function getMyCourses(Request $request)
    {
        $user = $request->user();

        $courses = Course::join('enrollments', 'courses.id', '=', 'enrollments.course_id')
            ->where('enrollments.user_id', $user->id)
            ->whereIn('enrollments.status', ['En curso', 'Finalizado'])
            ->select('courses.*', 'enrollments.status')
            ->get();

        return response()->json($courses);
    }

    public function finalizeCourse(Request $request, Course $course)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        // Validar si el usuario ha completado todas las lecciones
        $courseWithLessons = $course->load('sections.lessons');

        foreach ($courseWithLessons->sections as $section) {
            foreach ($section->lessons as $lesson) {
                $lessonCompleted = LessonResponse::where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->where('score', '>=', 7)
                    ->exists();

                if (!$lessonCompleted) {
                    return response()->json([
                        'message' => 'No has completado todas las lecciones o tu puntaje no cumple con el requisito mínimo de 7.'
                    ], 422);
                }
            }
        }

        // Cambiar el estado a 'Finalizado' en la inscripción
        $enrollment->status = 'Finalizado';
        $enrollment->save();

        Log::info('Curso finalizado para el usuario', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => $enrollment->status,
        ]);

        return response()->json(['message' => '¡Curso finalizado con éxito!', 'status' => $enrollment->status]);
    }
}
