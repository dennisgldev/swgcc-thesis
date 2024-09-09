<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Answer;

class CourseSeeder extends Seeder
{
    public function run()
    {
        // Datos de los cursos
        $courses = [
            [
                'title' => 'Métodos de investigación en software',
                'description' => 'Curso introductorio sobre los métodos de investigación aplicados en el desarrollo de software.',
                'instructor_id' => 1,
                'sections' => [
                    [
                        'title' => 'Introducción a la investigación en software',
                        'content' => 'Contenido sobre los fundamentos de la investigación en software.',
                        'lesson' => [
                            'title' => 'Lección 1: Fundamentos',
                            'content' => 'Contenido de la lección sobre los fundamentos de la investigación.',
                            'questions' => [
                                [
                                    'text' => '¿Qué es la investigación científica en software?',
                                    'type' => 'única',
                                    'points' => 5,
                                    'answers' => [
                                        ['text' => 'Proceso de creación de software.', 'correct' => false],
                                        ['text' => 'Proceso de estudio estructurado para la creación de conocimiento.', 'correct' => true],
                                        ['text' => 'Es una herramienta de desarrollo.', 'correct' => false],
                                    ],
                                ],
                                // Puedes agregar más preguntas aquí
                            ],
                        ],
                    ],
                    // Agrega más secciones si es necesario
                ],
            ],
            [
                'title' => 'Estadística aplicada a la investigación en software',
                'description' => 'Curso sobre cómo la estadística se utiliza en la investigación de software.',
                'instructor_id' => 1,
                'sections' => [
                    [
                        'title' => 'Introducción a la estadística en software',
                        'content' => 'Contenido sobre estadística aplicada en software.',
                        'lesson' => [
                            'title' => 'Lección 1: Introducción a la estadística',
                            'content' => 'Contenido sobre la importancia de la estadística en la investigación en software.',
                            'questions' => [
                                [
                                    'text' => '¿Qué es la estadística en investigación de software?',
                                    'type' => 'única',
                                    'points' => 5,
                                    'answers' => [
                                        ['text' => 'Una técnica matemática para analizar datos.', 'correct' => true],
                                        ['text' => 'Una fase de desarrollo de software.', 'correct' => false],
                                    ],
                                ],
                                // Más preguntas si lo deseas
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Inserción de los datos
        foreach ($courses as $courseData) {
            $course = Course::create([
                'title' => $courseData['title'],
                'description' => $courseData['description'],
                'instructor_id' => $courseData['instructor_id'],
            ]);

            foreach ($courseData['sections'] as $sectionData) {
                $section = Section::create([
                    'course_id' => $course->id,
                    'title' => $sectionData['title'],
                    'content' => $sectionData['content'],
                ]);

                if (isset($sectionData['lesson'])) {
                    $lessonData = $sectionData['lesson'];
                    $lesson = Lesson::create([
                        'section_id' => $section->id,
                        'title' => $lessonData['title'],
                        'content' => $lessonData['content'],
                    ]);

                    foreach ($lessonData['questions'] as $questionData) {
                        $question = Question::create([
                            'lesson_id' => $lesson->id,
                            'text' => $questionData['text'],
                            'type' => $questionData['type'],
                            'points' => $questionData['points'],
                        ]);

                        foreach ($questionData['answers'] as $answerData) {
                            Answer::create([
                                'question_id' => $question->id,
                                'text' => $answerData['text'],
                                'correct' => $answerData['correct'],
                            ]);
                        }
                    }
                }
            }
        }
    }
}
