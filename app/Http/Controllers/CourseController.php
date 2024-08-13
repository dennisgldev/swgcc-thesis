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
    public function index()
    {
        $userId = auth()->id(); // Obtener el ID del usuario autenticado

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
    public function show($id)
    {
        $userId = auth()->id(); // Obtener el ID del usuario autenticado

        $course = Course::with([
            'sections.lessons.questions.answers',
            'media', // Archivos del curso
            'sections.media', // Archivos de las secciones
            'enrollments' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }
        ])->findOrFail($id);

        // Obtener el estado de la inscripción del usuario
        $enrollment = $course->enrollments->first();
        $course->setAttribute('status', $enrollment ? $enrollment->status : 'Disponible');

        // Agregar la URL completa a los archivos para que puedan ser accedidos desde la vista
        if ($course->media) {
            $course->media->each(function ($media) {
                $media->file_url = asset('storage/' . $media->file_path);
            });
        }

        $course->sections->each(function ($section) {
            if ($section->media) {
                $section->media->each(function ($media) {
                    $media->file_url = asset('storage/' . $media->file_path);
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
    public function edit(Course $course)
    {
        return response()->json($course->load(['sections.lessons.questions.answers', 'media']));
    }

    // Actualizar un curso (solo para docentes)
    public function update(Request $request, Course $course)
    {
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
    public function destroy(Course $course)
    {
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

        return response()->json(['message' => 'Curso eliminado con éxito']);
    }

    public function finalize($id)
    {
        // Iniciar el proceso de finalización
        Log::info('Iniciando finalización del curso', ['course_id' => $id, 'user_id' => auth()->id()]);

        // Encontrar el curso y la inscripción del usuario autenticado
        $course = Course::findOrFail($id);
        $user = auth()->user();
        Log::info('Curso y usuario encontrados', ['course_id' => $course->id, 'user_id' => $user->id]);

        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$enrollment) {
            Log::warning('Usuario no inscrito en el curso', ['course_id' => $course->id, 'user_id' => $user->id]);
            return response()->json(['message' => 'No estás inscrito en este curso.'], 403);
        }

        // Calcular el puntaje basado en las respuestas a las lecciones
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
        $lessonResponses = LessonResponse::where('user_id', $user->id)
            ->whereHas('lesson.section', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->get();

        $totalScore = 0;
        $totalLessons = 0;

        foreach ($lessonResponses as $response) {
            $totalScore += $response->score;
            $totalLessons++;
        }

        return $totalLessons > 0 ? $totalScore / $totalLessons : 0;
    }
}
