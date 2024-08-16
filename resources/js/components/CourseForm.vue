<template>
    <v-container>
        <!-- Header -->
        <v-app-bar app color="white" elevation="3">
            <v-btn icon @click="goBackToCourses">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <v-toolbar-title class="headline">SWGCC</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn text @click="logout">Cerrar Sesión</v-btn>
        </v-app-bar>

        <v-row class="justify-center">
            <v-col cols="12" md="8">
                <v-card>
                    <v-card-title>{{ isEditing ? 'Editar Curso' : 'Crear Curso' }}</v-card-title>
                    <v-card-text>
                        <v-form @submit.prevent="submitForm" v-model="valid">
                            <v-text-field
                                v-model="course.title"
                                label="Título"
                                :rules="[rules.required]"
                                required
                            ></v-text-field>
                            <v-textarea
                                v-model="course.description"
                                label="Descripción"
                                :rules="[rules.required]"
                            ></v-textarea>
                            <v-file-input
                                v-model="coverImage"
                                label="Portada"
                                accept="image/*"
                                :rules="[rules.required]"
                            ></v-file-input>
                            <v-file-input
                                v-model="files"
                                label="Archivos adjuntos"
                                multiple
                            ></v-file-input>

                            <div class="d-flex justify-space-between align-center mb-4">
                                <span class="font-weight-bold">Secciones:</span>
                                <v-btn icon @click="addSection">
                                    <v-icon>mdi-plus</v-icon>
                                </v-btn>
                                <v-btn icon @click="removeSection" :disabled="course.sections.length <= 1">
                                    <v-icon>mdi-minus</v-icon>
                                </v-btn>
                            </div>

                            <div v-for="(section, index) in course.sections" :key="index" class="mb-4">
                                <v-card flat>
                                    <v-card-title>Sección {{ index + 1 }}</v-card-title>
                                    <v-text-field
                                        v-model="section.title"
                                        label="Título de la Sección"
                                        :rules="[rules.required]"
                                        required
                                    ></v-text-field>
                                    <v-textarea
                                        v-model="section.content"
                                        label="Contenido de la Sección"
                                        :rules="[rules.required]"
                                    ></v-textarea>
                                    <v-file-input
                                        v-model="section.media"
                                        label="Medios adjuntos"
                                        multiple
                                    ></v-file-input>

                                    <!-- Solo la última sección puede tener la lección -->
                                    <div v-if="index === course.sections.length - 1">
                                        <v-card-title>Lección de la Sección {{ index + 1 }}</v-card-title>
                                        <v-text-field
                                            v-model="section.lesson.title"
                                            label="Título de la Lección"
                                            :rules="[rules.required]"
                                            required
                                        ></v-text-field>
                                        <v-textarea
                                            v-model="section.lesson.content"
                                            label="Contenido de la Lección"
                                            :rules="[rules.required]"
                                            required
                                        ></v-textarea>

                                        <v-btn color="primary" @click="addQuestion(index)">Agregar Pregunta</v-btn>

                                        <div v-for="(question, questionIndex) in section.lesson.questions" :key="questionIndex">
                                            <v-text-field
                                                v-model="question.text"
                                                label="Pregunta"
                                                :rules="[rules.required]"
                                                required
                                            ></v-text-field>
                                            <v-text-field
                                                v-model="question.points"
                                                label="Puntos"
                                                type="number"
                                                min="0.1"
                                                step="0.1"
                                                
                                                required
                                            ></v-text-field>

                                            <v-btn color="secondary" @click="addAnswer(index, questionIndex)">Agregar Respuesta</v-btn>

                                            <v-row v-for="(answer, answerIndex) in question.answers" :key="answerIndex" align="center">
                                                <v-col cols="8">
                                                    <v-text-field
                                                        v-model="answer.text"
                                                        label="Respuesta"
                                                        :rules="[rules.required]"
                                                        required
                                                    ></v-text-field>
                                                </v-col>
                                                <v-col cols="4">
                                                    <v-checkbox
                                                        v-model="answer.correct"
                                                        label="Correcta"
                                                        @change="validateSingleCorrectAnswer(section.lesson.questions, questionIndex)"
                                                    ></v-checkbox>
                                                </v-col>
                                            </v-row>
                                        </div>
                                    </div>
                                </v-card>
                            </div>

                            <v-btn :disabled="!valid" type="submit" color="primary">{{ isEditing ? 'Actualizar Curso' : 'Crear Curso' }}</v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import axios from 'axios';

export default {
    name: 'CourseForm',
    data() {
        return {
            course: {
                title: '',
                description: '',
                instructor_id: null,
                sections: [
                    {
                        title: '',
                        content: '',
                        media: [],
                        lesson: { title: '', content: '', questions: [] }
                    }
                ]
            },
            coverImage: null,
            files: [],
            activeSection: 0,
            isEditing: false,
            valid: true,
            feedbackMessage: '',
            feedbackType: 'error',
            rules: {
                required: value => !!value || 'Este campo es requerido.',
                totalPoints: questions => value => {
                    const totalPoints = questions.reduce((sum, question) => sum + parseFloat(question.points || 0), 0);
                    return totalPoints === 10 || 'La suma total de puntos debe ser exactamente 10.';
                }
            },
        };
    },
    methods: {
        addSection() {
            this.course.sections.push({
                title: '',
                content: '',
                media: [],
                lesson: null // La lección solo se agrega en la última sección
            });
            this.activeSection = this.course.sections.length - 1;
            this.updateLessons();
        },
        removeSection() {
            if (this.course.sections.length > 1) {
                this.course.sections.pop();
                this.activeSection = this.course.sections.length - 1;
                this.updateLessons();
            }
        },
        updateLessons() {
            // Solo la última sección puede tener la lección
            this.course.sections.forEach((section, index) => {
                if (index === this.course.sections.length - 1) {
                    if (!section.lesson) {
                        section.lesson = {title: '', content: '', questions: []};
                    }
                } else {
                    section.lesson = null;
                }
            });
        },
        addQuestion(sectionIndex) {
            this.course.sections[sectionIndex].lesson.questions.push({
                text: '',
                points: 0,
                answers: []
            });
        },
        addAnswer(sectionIndex, questionIndex) {
            this.course.sections[sectionIndex].lesson.questions[questionIndex].answers.push({
                text: '',
                correct: false
            });
        },
        validateSingleCorrectAnswer(questions, questionIndex) {
            const question = questions[questionIndex];
            const correctAnswers = question.answers.filter(answer => answer.correct);
            if (correctAnswers.length > 1) {
                question.answers[question.answers.length - 1].correct = false;
                this.feedbackMessage = "Solo puede haber una respuesta correcta por pregunta.";
                this.feedbackType = "error";
            } else {
                this.feedbackMessage = '';
            }
        },
        submitForm() {
            const lastSection = this.course.sections[this.course.sections.length - 1];
            if (!lastSection.lesson || !lastSection.lesson.title || !lastSection.lesson.content) {
                this.feedbackMessage = 'La lección de la última sección es obligatoria.';
                this.feedbackType = "error";
                return;
            }

            const formData = this.prepareFormData();

            const request = this.isEditing
                ? axios.put(`/api/courses/${this.course.id}`, formData)
                : axios.post('/api/courses', formData);

            request.then(response => {
                this.$router.push('/courses');
            }).catch(error => {
                if (error.response && error.response.data.errors) {
                    this.feedbackMessage = `Error al enviar el formulario: ${JSON.stringify(error.response.data.errors)}`;
                    this.feedbackType = "error";
                } else {
                    console.error('Error desconocido:', error);
                }
            });
        },
        prepareFormData() {
            const formData = new FormData();
            formData.append('title', this.course.title);
            formData.append('description', this.course.description);
            formData.append('instructor_id', this.course.instructor_id);
            if (this.coverImage) formData.append('cover_image', this.coverImage);
            this.files.forEach(file => formData.append('files[]', file));

            this.course.sections.forEach((section, sectionIndex) => {
                formData.append(`sections[${sectionIndex}][title]`, section.title);
                formData.append(`sections[${sectionIndex}][content]`, section.content);

                section.media.forEach((mediaFile, mediaIndex) => {
                    formData.append(`sections[${sectionIndex}][media][${mediaIndex}]`, mediaFile);
                });

                if (section.lesson) {
                    formData.append(`sections[${sectionIndex}][lesson][title]`, section.lesson.title);
                    formData.append(`sections[${sectionIndex}][lesson][content]`, section.lesson.content);

                    section.lesson.questions.forEach((question, questionIndex) => {
                        formData.append(`sections[${sectionIndex}][lesson][questions][${questionIndex}][text]`, question.text);
                        formData.append(`sections[${sectionIndex}][lesson][questions][${questionIndex}][type]`, 'única');
                        formData.append(`sections[${sectionIndex}][lesson][questions][${questionIndex}][points]`, question.points);

                        question.answers.forEach((answer, answerIndex) => {
                            formData.append(`sections[${sectionIndex}][lesson][questions][${questionIndex}][answers][${answerIndex}][text]`, answer.text);
                            formData.append(`sections[${sectionIndex}][lesson][questions][${questionIndex}][answers][${answerIndex}][correct]`, answer.correct ? '1' : '0');
                        });
                    });
                }
            });

            return formData;
        },
        fetchCourse() {
            const courseId = this.$route.params.id;
            if (courseId) {
                this.isEditing = true;
                axios.get(`/api/courses/${courseId}/edit`)
                    .then(response => {
                        this.course = response.data;
                        this.sectionsCount = this.course.sections.length;
                        this.updateLessons();
                    })
                    .catch(error => {
                        console.error('Error al obtener el curso:', error);
                    });
            }
        },
        goBackToCourses() {
            this.$router.push('/courses');
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
        }
    },
    created() {
        axios.get('/api/user')
            .then(response => {
                this.course.instructor_id = response.data.id;
                this.fetchCourse();
            })
            .catch(error => {
                console.error('Error al obtener el ID del instructor:', error);
            });
    }
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
</style>
