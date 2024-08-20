<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    // Listar todos los cursos (disponible para docentes y estudiantes)
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info('Accediendo a la lista de cursos', ['user_id' => $user->id]);

        if (!$user->can('ver cursos')) {
            Log::warning('Acceso denegado para listar cursos', ['user_id' => $user->id]);
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $userId = $user->id;

        $courses = Course::with(['instructor', 'sections.lessons', 'enrollments'])->get();

        $courses->each(function ($course) use ($userId) {
            $enrollment = $course->enrollments->where('user_id', $userId)->first();
            $course->setAttribute('status', $enrollment ? $enrollment->status : 'Disponible');
        });

        foreach ($courses as $course) {
            if ($course->cover_image) {
                Log::info("Curso ID {$course->id} tiene una portada en: {$course->cover_image}");
            } else {
                Log::warning("Curso ID {$course->id} no tiene portada asociada");
            }
        }

        return response()->json($courses);
    }

    // Mostrar los detalles de un curso (disponible para docentes y estudiantes)
    public function show(Request $request, $id)
    {
        $user = $request->user();
        Log::info('Accediendo a los detalles del curso', ['user_id' => $user->id, 'course_id' => $id]);

        if (!$user->can('ver cursos')) {
            Log::warning('Acceso denegado para ver detalles del curso', ['user_id' => $user->id, 'course_id' => $id]);
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $userId = $user->id;

        $course = Course::with([
            'instructor',
            'sections.lessons.questions.answers',
            'media', // Archivos del curso
            'sections.media', // Archivos de las secciones
            'enrollments' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }
        ])->findOrFail($id);

        Log::info('Curso encontrado', ['course_id' => $course->id]);

        // Obtener el estado de la inscripción del usuario
        $enrollment = $course->enrollments->first();
        $course->setAttribute('status', $enrollment ? $enrollment->status : 'Disponible');

        // Agregar la URL completa a los archivos para que puedan ser accedidos desde la vista
        if ($course->media) {
            $course->media->each(function ($media) {
                $media->file_url = asset('uploads/' . $media->file_path);
            });
        }

        $course->sections->each(function ($section) {
            if ($section->media) {
                $section->media->each(function ($media) {
                    $media->file_url = asset('uploads/' . $media->file_path);
                });
            }

            if ($section->lessons) {
                $section->lessons->each(function ($lesson) {
                    if ($lesson->questions) {
                        $lesson->questions->each(function ($question) {
                            if ($question->answers) {
                                $question->answers->each(function ($answer) {
                                    // Aquí puedes procesar cada respuesta si es necesario
                                });
                            }
                        });
                    }
                });
            }
        });

        return response()->json($course);
    }

    // Crear un nuevo curso (solo para docentes)
    public function store(Request $request)
    {
        $user = $request->user();
        Log::info('Intentando crear un curso', ['user_id' => $user->id]);

        if (!$user->can('gestión de cursos y reportería')) {
            Log::warning('Acceso denegado para crear curso', ['user_id' => $user->id]);
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Validación de datos
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructor_id' => 'required|exists:users,id',
            'cover_image' => 'nullable|image|max:2048',
            'files.*' => 'nullable|file|max:10240',
            'sections' => 'array',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.content' => 'nullable|string',
            'sections.*.media.*' => 'nullable|file|max:10240',
            'sections.*.lesson.title' => 'nullable|string|max:255',
            'sections.*.lesson.content' => 'nullable|string',
            'sections.*.lesson.questions' => 'array',
            'sections.*.lesson.questions.*.text' => 'required_with:sections.*.lesson|string|max:255',
            'sections.*.lesson.questions.*.type' => 'required_with:sections.*.lesson|in:única,múltiple',
            'sections.*.lesson.questions.*.points' => 'required_with:sections.*.lesson|numeric|min:0|max:10',
            'sections.*.lesson.questions.*.answers' => 'array',
            'sections.*.lesson.questions.*.answers.*.text' => 'required_with:sections.*.lesson.questions|string|max:255',
            'sections.*.lesson.questions.*.answers.*.correct' => 'boolean',
        ]);

        // Validar que el puntaje total de cada lección no exceda 10
        foreach ($validatedData['sections'] as $sectionData) {
            if (isset($sectionData['lesson'])) {
                $totalPoints = array_reduce($sectionData['lesson']['questions'], function($carry, $question) {
                    return $carry + $question['points'];
                }, 0);

                if ($totalPoints !== 10) {
                    return response()->json(['error' => 'El puntaje total de la lección en cada sección debe ser exactamente 10.'], 422);
                }
            }
        }

        // Guardar la imagen de portada del curso si se proporciona
        if ($request->hasFile('cover_image')) {
            $validatedData['cover_image'] = $request->file('cover_image')->store('cover_images', 'public');
        }

        $course = Course::create($validatedData);

        Log::info('Curso creado con éxito', ['course_id' => $course->id]);

        // Guardar archivos adjuntos del curso si se proporcionan
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('course_files', 'public');
                $course->media()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientOriginalExtension(),
                ]);
            }
        }

        // Guardar secciones y lecciones
        foreach ($validatedData['sections'] as $sectionData) {
            $section = $course->sections()->create([
                'title' => $sectionData['title'],
                'content' => $sectionData['content'] ?? null,
            ]);

            // Guardar medios de la sección en `CourseMedia` si se proporcionan
            if (isset($sectionData['media'])) {
                foreach ($sectionData['media'] as $mediaFile) {
                    $path = $mediaFile->store('course_media', 'public');
                    $course->media()->create([
                        'file_name' => $mediaFile->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $mediaFile->getClientOriginalExtension(),
                        'section_id' => $section->id,
                    ]);
                }
            }

            // Guardar lección y preguntas si se proporciona
            if (isset($sectionData['lesson'])) {
                $lesson = $section->lessons()->create([
                    'title' => $sectionData['lesson']['title'] ?? null,
                    'content' => $sectionData['lesson']['content'] ?? null,
                ]);

                foreach ($sectionData['lesson']['questions'] as $questionData) {
                    $question = $lesson->questions()->create([
                        'text' => $questionData['text'],
                        'type' => $questionData['type'],
                        'points' => $questionData['points']
                    ]);

                    foreach ($questionData['answers'] as $answerData) {
                        $question->answers()->create([
                            'text' => $answerData['text'],
                            'correct' => $answerData['correct']
                        ]);
                    }
                }
            }
        }

        return response()->json(['message' => 'Curso creado con éxito', 'course' => $course], 201);
    }

    // Editar un curso (solo para docentes)
    public function edit(Request $request, Course $course)
    {
        $user = $request->user();
        Log::info('Intentando editar un curso', ['user_id' => $user->id, 'course_id' => $course->id]);

        if (!$user->can('gestión de cursos y reportería')) {
            Log::warning('Acceso denegado para editar curso', ['user_id' => $user->id, 'course_id' => $course->id]);
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json($course->load(['sections.lessons.questions.answers', 'media']));
    }

    // Actualizar un curso (solo para docentes)
    public function update(Request $request, Course $course)
    {
        $user = $request->user();
        Log::info('Intentando actualizar un curso', ['user_id' => $user->id, 'course_id' => $course->id]);

        if (!$user->can('gestión de cursos y reportería')) {
            Log::warning('Acceso denegado para actualizar curso', ['user_id' => $user->id, 'course_id' => $course->id]);
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructor_id' => 'required|exists:users,id',
            'cover_image' => 'nullable|image|max:2048',
            'files.*' => 'nullable|file|max:10240',
            'sections' => 'array',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.content' => 'nullable|string',
            'sections.*.media.*' => 'nullable|file|max:10240',
            'sections.*.lesson.title' => 'nullable|string|max:255',
            'sections.*.lesson.content' => 'nullable|string',
            'sections.*.lesson.questions' => 'array',
            'sections.*.lesson.questions.*.text' => 'required|string|max:255',
            'sections.*.lesson.questions.*.type' => 'required|in:única,múltiple',
            'sections.*.lesson.questions.*.points' => 'required|numeric|min:0|max:10',
            'sections.*.lesson.questions.*.answers' => 'array',
            'sections.*.lesson.questions.*.answers.*.text' => 'required|string|max:255',
            'sections.*.lesson.questions.*.answers.*.correct' => 'boolean',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($course->cover_image) {
                Storage::disk('public')->delete($course->cover_image);
            }
            $validatedData['cover_image'] = $request->file('cover_image')->store('cover_images', 'public');
        }

        $course->update($validatedData);

        Log::info('Curso actualizado con éxito', ['course_id' => $course->id]);

        // Actualizar medios del curso
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('course_files', 'public');
                $course->media()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientOriginalExtension(),
                ]);
            }
        }

        // Actualizar secciones y lecciones
        foreach ($validatedData['sections'] as $sectionData) {
            $section = $course->sections()->updateOrCreate(
                ['id' => $sectionData['id'] ?? null],
                ['title' => $sectionData['title'], 'content' => $sectionData['content'] ?? null]
            );

            // Actualizar medios de la sección en `CourseMedia`
            if (isset($sectionData['media'])) {
                foreach ($sectionData['media'] as $mediaFile) {
                    $path = $mediaFile->store('course_media', 'public');
                    $course->media()->create([
                        'file_name' => $mediaFile->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $mediaFile->getClientOriginalExtension(),
                        'section_id' => $section->id, // Relacionar con la sección
                    ]);
                }
            }

            // Actualizar lección y preguntas
            if (isset($sectionData['lesson'])) {
                $lesson = $section->lessons()->updateOrCreate(
                    ['id' => $sectionData['lesson']['id'] ?? null],
                    ['title' => $sectionData['lesson']['title'], 'content' => $sectionData['lesson']['content'] ?? null]
                );

                foreach ($sectionData['lesson']['questions'] as $questionData) {
                    $question = $lesson->questions()->updateOrCreate(
                        ['id' => $questionData['id'] ?? null],
                        ['text' => $questionData['text'], 'type' => $questionData['type'], 'points' => $questionData['points']]
                    );

                    foreach ($questionData['answers'] as $answerData) {
                        $question->answers()->updateOrCreate(
                            ['id' => $answerData['id'] ?? null],
                            ['text' => $answerData['text'], 'correct' => $answerData['correct']]
                        );
                    }
                }
            }
        }

        return response()->json(['message' => 'Curso actualizado con éxito', 'course' => $course]);
    }

    // Eliminar un curso (solo para docentes)
    public function destroy(Request $request, Course $course)
    {
        $user = $request->user();
        Log::info('Intentando eliminar un curso', ['user_id' => $user->id, 'course_id' => $course->id]);

        if (!$user->can('gestión de cursos y reportería')) {
            Log::warning('Acceso denegado para eliminar curso', ['user_id' => $user->id, 'course_id' => $course->id]);
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if ($course->cover_image) {
            Storage::disk('public')->delete($course->cover_image);
        }

        foreach ($course->sections as $section) {
            if ($section->lesson) {
                foreach ($section->lesson->questions as $question) {
                    foreach ($question->answers as $answer) {
                        $answer->delete();
                    }
                    $question->delete();
                }
                $section->lesson->delete();
            }

            foreach ($section->media as $media) {
                Storage::disk('public')->delete($media->file_path);
                $media->delete();
            }

            $section->delete();
        }

        foreach ($course->media as $media) {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();
        }

        $course->delete();

        Log::info('Curso eliminado con éxito', ['course_id' => $course->id]);

        return response()->json(['message' => 'Curso eliminado con éxito']);
    }

    public function finalize(Request $request, $id)
    {
        $user = $request->user();
        Log::info('Iniciando finalización del curso', ['course_id' => $id, 'user_id' => $user->id]);

        if (!$user->can('inscribirse a cursos')) {
            Log::warning('Acceso denegado para finalizar curso', ['user_id' => $user->id, 'course_id' => $id]);
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Encontrar el curso y la inscripción del usuario autenticado
        $course = Course::findOrFail($id);
        Log::info('Curso encontrado para finalización', ['course_id' => $course->id]);

        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$enrollment) {
            Log::warning('Usuario no inscrito en el curso', ['course_id' => $course->id, 'user_id' => $user->id]);
            return response()->json(['message' => 'No estás inscrito en este curso.'], 403);
        }

        // Calcular el puntaje basado en las últimas respuestas a las lecciones
        $userScore = $this->calculateUserScore($course, $user);
        Log::info('Score calculado', ['score' => $userScore]);

        $minimumScore = 7; // Ejemplo de un puntaje mínimo requerido

        if ($userScore >= $minimumScore) {
            $enrollment->status = 'Finalizado';
            $enrollment->save();

            Log::info('Curso finalizado con éxito', ['enrollment_id' => $enrollment->id]);

            return response()->json(['message' => 'Curso finalizado con éxito.']);
        }

        Log::warning('No se cumplen las condiciones para finalizar el curso', [
            'userScore' => $userScore,
            'minimumScore' => $minimumScore
        ]);

        return response()->json(['message' => 'No has completado todas las lecciones o tu puntaje no cumple con el requisito mínimo de 7.'], 400);
    }

    private function calculateUserScore($course, $user)
    {
        $totalScore = 0;

        // Obtener todas las secciones del curso
        foreach ($course->sections as $section) {
            // Obtener todas las lecciones de la sección
            foreach ($section->lessons as $lesson) {
                // Obtener la última respuesta del usuario para esta lección
                $lastLessonResponse = LessonResponse::where('lesson_id', $lesson->id)
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($lastLessonResponse) {
                    // Sumar el puntaje de la última respuesta a la puntuación total
                    $totalScore += $lastLessonResponse->score;
                }
            }
        }

        return $totalScore;
    }

    public function getCoursesWithEnrollments(Request $request)
{
    $user = $request->user();

    // Verificar permisos
    if (!$user->can('gestión de cursos y reportería')) {
        return response()->json(['message' => 'This action is unauthorized.'], 403);
    }

    // Obtener cursos con estudiantes inscritos y sus calificaciones
    $courses = Course::with(['enrollments.user', 'enrollments' => function($query) {
        $query->with(['user', 'user.lessonResponses' => function($query) {
            $query->select('lesson_id', 'user_id', 'score');
        }]);
    }])->get();

    // Asegurarse de que se devuelva un array
    return response()->json($courses->toArray());
}

}
