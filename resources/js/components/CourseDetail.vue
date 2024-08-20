<template>
    <v-container>
        <!-- Header -->
        <v-app-bar app color="white" elevation="3">
            <v-btn icon @click="goBackToCourses">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <v-toolbar-title class="headline">SWGCC</v-toolbar-title>
            <v-spacer></v-spacer>
<!--            <v-btn text @click="createCourse" v-if="isTeacher">Crear Curso</v-btn>-->
            <v-btn text @click="logout">Cerrar Sesión</v-btn>
        </v-app-bar>
        <v-row>
            <v-col cols="12" v-if="course">
                <!-- Tarjeta del curso con header y footer -->
                <v-card
                    :variant="variant"
                    class="mx-auto my-5"
                    color="blue-grey-lighten-5"
                    max-width="2000"
                >
                    <!-- Header -->
                    <v-card-title class="text-h5">
                        {{ course.title }}
                    </v-card-title>
                    <v-card-subtitle>
                        {{ course.instructor ? course.instructor.name : 'Instructor desconocido' }}
                    </v-card-subtitle>

                    <!-- Imagen de portada del curso -->
                    <v-img :src="course.cover_image ? `/uploads/${course.cover_image}` : '/images/default-cover.jpg'" height="300px" class="white--text"></v-img>

                    <!-- Descripción del curso -->
                    <v-card-text>
                        {{ course.description }}
                    </v-card-text>

                    <!-- Archivos adjuntos del curso -->
                    <v-divider class="my-3"></v-divider>
                    <v-card-subtitle class="mb-2">
                        <strong>Archivos adjuntos del curso:</strong>
                    </v-card-subtitle>
                    <v-list>
                        <v-list-group value="Archivos">
                            <template v-slot:activator="{ props }">
                                <v-list-item v-bind="props" prepend-icon="mdi-folder" title="Archivos del Curso"></v-list-item>
                            </template>

                            <v-list-group v-for="media in groupedMedia" :key="media.type" :value="media.type">
                                <template v-slot:activator="{ props }">
                                    <v-list-item v-bind="props" prepend-icon="mdi-file" :title="media.type"></v-list-item>
                                </template>

                                <v-list-item v-for="file in media.files" :key="file.id">
                                    <template v-if="isVideo(file.file_type)">
                                        <video controls :src="file.file_url" width="50%"></video>
                                    </template>
                                    <template v-else>
                                        <a :href="file.file_url" target="_blank">{{ file.file_name }}</a>
                                    </template>
                                </v-list-item>
                            </v-list-group>
                        </v-list-group>
                    </v-list>

                    <!-- Footer -->
                    <v-card-actions>
                        <v-btn color="primary" @click="enrollInCourse" v-if="!isEnrolled">
                            Inscribirse en el Curso
                        </v-btn>
                        <v-btn v-if="isEnrolled && enrollmentStatus === 'En curso' && isLastSection" color="primary" @click="finalizeCourse">
                            Finalizar Curso
                        </v-btn>
                    </v-card-actions>
                </v-card>

                <!-- Secciones -->
                <v-expansion-panels multiple class="mx-auto my-5" max-width="1500">
                    <v-expansion-panel v-for="section in course.sections" :key="section.id" class="my-5" variant="popout">
                        <v-expansion-panel-title>
                            {{ section.title }}
                        </v-expansion-panel-title>
                        <v-expansion-panel-text>
                            <p>{{ section.content }}</p>

                            <!-- Archivos adjuntos de la sección -->
                            <div v-if="section.media && section.media.length > 0" class="mb-3">
                                <h4>Archivos adjuntos de la sección:</h4>
                                <v-list>
                                    <v-list-group value="Archivos">
                                        <template v-slot:activator="{ props }">
                                            <v-list-item v-bind="props" prepend-icon="mdi-folder" title="Archivos de la Sección"></v-list-item>
                                        </template>

                                        <v-list-group v-for="media in groupMediaByType(section.media)" :key="media.type" :value="media.type">
                                            <template v-slot:activator="{ props }">
                                                <v-list-item v-bind="props" prepend-icon="mdi-file" :title="media.type"></v-list-item>
                                            </template>

                                            <v-list-item v-for="file in media.files" :key="file.id">
                                                <template v-if="isVideo(file.file_type)">
                                                    <video controls :src="file.file_url" width="100%"></video>
                                                </template>
                                                <template v-else>
                                                    <a :href="file.file_url" target="_blank">{{ file.file_name }}</a>
                                                </template>
                                            </v-list-item>
                                        </v-list-group>
                                    </v-list-group>
                                </v-list>
                            </div>

                            <!-- Lecciones (Solo si el usuario está inscrito) -->
                            <v-expansion-panels v-if="isEnrolled && Array.isArray(section.lessons) && section.lessons.length > 0" multiple class="mx-auto" max-width="1500">
                                <v-expansion-panel v-for="lesson in section.lessons" :key="lesson.id" class="my-4" variant="popout">
                                    <v-expansion-panel-title>
                                        {{ lesson.title }}
                                    </v-expansion-panel-title>
                                    <v-expansion-panel-text>
                                        <p>{{ lesson.content }}</p>

                                        <!-- Archivos adjuntos de la lección -->
                                        <div v-if="lesson.media && lesson.media.length > 0" class="mb-3">
                                            <h4>Archivos adjuntos de la lección:</h4>
                                            <v-list>
                                                <v-list-group value="Archivos">
                                                    <template v-slot:activator="{ props }">
                                                        <v-list-item v-bind="props" prepend-icon="mdi-folder" title="Archivos de la Lección"></v-list-item>
                                                    </template>

                                                    <v-list-group v-for="media in groupMediaByType(lesson.media)" :key="media.type" :value="media.type">
                                                        <template v-slot:activator="{ props }">
                                                            <v-list-item v-bind="props" prepend-icon="mdi-file" :title="media.type"></v-list-item>
                                                        </template>

                                                        <v-list-item v-for="file in media.files" :key="file.id">
                                                            <template v-if="isVideo(file.file_type)">
                                                                <video controls :src="file.file_url" width="100%"></video>
                                                            </template>
                                                            <template v-else>
                                                                <a :href="file.file_url" target="_blank">{{ file.file_name }}</a>
                                                            </template>
                                                        </v-list-item>
                                                    </v-list-group>
                                                </v-list-group>
                                            </v-list>
                                        </div>

                                        <!-- Preguntas -->
                                        <v-list v-if="Array.isArray(lesson.questions) && lesson.questions.length > 0">
                                            <v-list-item v-for="question in lesson.questions" :key="question.id">
                                                <v-list-item-title>{{ question.text }}</v-list-item-title>

                                                <!-- Respuestas -->
                                                <v-list v-if="Array.isArray(question.answers) && question.answers.length > 0">
                                                    <v-list-item v-for="answer in question.answers" :key="answer.id">
                                                        <v-radio-group v-model="selectedAnswers[lesson.id][question.id]" v-if="question.type === 'única'">
                                                            <v-checkbox
                                                                :label="answer.text"
                                                                :value="answer.id"
                                                            ></v-checkbox>
                                                        </v-radio-group>

                                                        <v-checkbox
                                                            v-if="question.type === 'múltiple'"
                                                            :label="answer.text"
                                                            :value="answer.id"
                                                            v-model="selectedAnswers[lesson.id][question.id]"
                                                            class="ml-3"
                                                        ></v-checkbox>
                                                    </v-list-item>
                                                </v-list>

                                            </v-list-item>
                                        </v-list>

                                        <!-- Botón de envío para la lección -->
                                        <v-btn color="green-lighten-3" @click="submitLesson(lesson.id)" class="mt-3">Enviar Lección</v-btn>
                                        <!-- Mostrar calificación -->
                                        <v-chip v-if="lesson.last_grade !== null"
                                                :color="lesson.last_grade < 7 ? 'red' : 'green'"
                                                dark
                                                class="ml-3"
                                        >
                                            Última Calificación: {{ lesson.last_grade }}
                                        </v-chip>
                                    </v-expansion-panel-text>
                                </v-expansion-panel>
                            </v-expansion-panels>

                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>
            </v-col>
        </v-row>

        <!-- Dialogo para mensajes de feedback -->
        <v-dialog v-model="feedbackDialog" persistent max-width="300">
            <v-card>
                <v-card-title class="headline">{{ feedbackMessage }}</v-card-title>
                <v-card-actions>
                    <v-btn color="primary" text @click="closeFeedbackDialog">OK</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Footer -->
        <v-footer app color="white" flat>
            <v-col class="text-center">
                &copy; 2024 SWGCC
            </v-col>
        </v-footer>
    </v-container>
</template>

<script>
import axios from 'axios';

export default {
    name: 'CourseDetail',
    data() {
        return {
            course: null,
            isTeacher: false,
            isEnrolled: false,
            selectedAnswers: {},
            enrollmentStatus: '', // Variable para almacenar el estado de la inscripción
            feedbackDialog: false,
            feedbackMessage: ''
        };
    },
    computed: {
        isLastSection() {
            if (!this.course || !this.course.sections) return false;
            const lastSectionId = this.course.sections[this.course.sections.length - 1].id;
            return this.course.sections.some(section => section.id === lastSectionId);
        },
        groupedMedia() {
            return this.groupMediaByType(this.course.media);
        }
    },
    methods: {
        fetchCourse() {
            const courseId = this.$route.params.id;

            axios.get(`/api/courses/${courseId}`)
                .then(response => {
                    this.course = response.data;

                    if (Array.isArray(this.course.sections)) {
                        this.course.sections.forEach(section => {
                            this.selectedAnswers[section.id] = {};

                            if (section.lessons && !Array.isArray(section.lessons)) {
                                section.lessons = [section.lessons];
                            }

                            if (section.lessons && Array.isArray(section.lessons)) {
                                section.lessons.forEach(lesson => {
                                    this.selectedAnswers[lesson.id] = {};

                                    // Obtener la última calificación del usuario para esta lección
                                    axios.get(`/api/lessons/${lesson.id}/responses`)
                                        .then(res => {
                                            const lessonResponse = res.data;
                                            console.log(res.data);
                                            lesson.last_grade = lessonResponse ? lessonResponse.score : lessonResponse.score;
                                        })
                                        .catch(err => {
                                            console.error(`Error fetching grade for lesson ${lesson.id}:`, err);
                                            lesson.last_grade = null;
                                        });

                                    if (lesson.questions && !Array.isArray(lesson.questions)) {
                                        lesson.questions = [lesson.questions];
                                    }

                                    if (lesson.questions && Array.isArray(lesson.questions)) {
                                        lesson.questions.forEach(question => {
                                            this.selectedAnswers[question.id] = {};
                                        });
                                    }
                                });
                            }
                        });
                    }

                    this.checkRole();
                })
                .catch(error => {
                    console.error('Error fetching course:', error);
                });
        },
        isVideo(fileType) {
            return ['mp4', 'webm', 'ogg'].includes(fileType.toLowerCase());
        },
        groupMediaByType(mediaArray) {
            const groups = {};
            mediaArray.forEach(media => {
                const type = media.file_type.toUpperCase();
                if (!groups[type]) {
                    groups[type] = [];
                }
                groups[type].push(media);
            });
            return Object.keys(groups).map(type => ({
                type,
                files: groups[type]
            }));
        },
        goBackToCourses() {
            this.$router.push('/courses');
        },
        enrollInCourse() {
            if (this.isEnrolled) return;

            const courseId = this.course.id;

            axios.post(`/api/courses/${courseId}/enroll`)
                .then(response => {
                    this.isEnrolled = true;
                    this.enrollmentStatus = response.data.enrollment.status;
                    this.showFeedbackDialog('Inscripción realizada con éxito.');
                })
                .catch(error => {
                    console.error('Error enrolling in course:', error);
                    this.showFeedbackDialog('Error al inscribirse en el curso.');
                });
        },
        finalizeCourse() {
            const courseId = this.course.id;

            axios.post(`/api/courses/${courseId}/finalize`)
                .then(response => {
                    this.enrollmentStatus = 'Finalizado';
                    this.showFeedbackDialog('¡Felicidades! Has finalizado el curso con éxito.');
                })
                .catch(error => {
                    console.error('Error finalizing course:', error);
                    this.showFeedbackDialog('No has completado todas las lecciones o tu puntaje no cumple con el requisito mínimo de 7.');
                });
        },
        submitLesson(lessonId) {
            if (!this.isEnrolled) {
                this.showFeedbackDialog('Debes estar inscrito en el curso para enviar las lecciones.');
                return;
            }

            const payload = {
                answers: Object.keys(this.selectedAnswers[lessonId]).map(questionId => {
                    return {
                        question_id: questionId,
                        selected_answers: Array.isArray(this.selectedAnswers[lessonId][questionId])
                            ? this.selectedAnswers[lessonId][questionId]
                            : [this.selectedAnswers[lessonId][questionId]]
                    };
                })
            };

            axios.post(`/api/lessons/${lessonId}/submit`, payload)
                .then(response => {
                    this.showFeedbackDialog('Respuestas enviadas correctamente.');
                    this.course.sections.forEach(section => {
                        section.lessons.forEach(lesson => {
                            if (lesson.id === lessonId) {
                                lesson.last_grade = response.data.score; // Utilizamos el score devuelto en la respuesta
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('Error submitting lesson:', error);
                    this.showFeedbackDialog('Error al enviar las respuestas.');
                });
        },
        checkRole() {
            axios.get('/api/user')
                .then(response => {
                    this.isTeacher = response.data.role_id === 2;

                    if (this.course) {
                        axios.get(`/api/courses/${this.course.id}/is-enrolled`)
                            .then(res => {
                                this.isEnrolled = res.data.is_enrolled;
                                this.enrollmentStatus = res.data.status;
                            })
                            .catch(err => {
                                console.error('Error checking enrollment:', err);
                            });
                    }
                })
                .catch(error => {
                    console.error('Error al obtener el rol del usuario:', error);
                });
        },
        showFeedbackDialog(message) {
            this.feedbackMessage = message;
            this.feedbackDialog = true;
        },
        closeFeedbackDialog() {
            this.feedbackDialog = false;
        }
    },
    created() {
        this.fetchCourse();
    },
    logout() {
        console.log('Logging out...');
        axios.post('/logout')
            .then(() => {
                localStorage.removeItem('authToken');
                console.log('Logout successful.');
                this.$router.push('/login');
            })
            .catch(error => {
                console.error('Error logging out:', error);
            });
    },
};
</script>

<style scoped>
.course-card {
    cursor: pointer;
    transition: transform 0.3s;
}

.course-card:hover {
    transform: scale(1.05);
}

.v-expansion-panel-title {
    background-color: #f5f5f5;
}

.v-expansion-panel-text {
    background-color: #fafafa;
    padding: 16px;
    border-left: 4px solid #2196F3;
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
}

.v-list-item {
    margin-bottom: 16px;
}
</style>
