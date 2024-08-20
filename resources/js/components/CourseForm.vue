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

                            <!-- Editor de descripción del curso con feedback visual y barra de herramientas -->
                            <div class="editor-wrapper">
                                <div v-if="descriptionEditor" class="button-group">
                                    <v-btn @click="toggleFormat('bold')">
                                        <v-icon>mdi-format-bold</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('italic')">
                                        <v-icon>mdi-format-italic</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('underline')">
                                        <v-icon>mdi-format-underline</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('heading', 1)">
                                        <v-icon>mdi-format-header-1</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('heading', 2)">
                                        <v-icon>mdi-format-header-2</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('bulletList')">
                                        <v-icon>mdi-format-list-bulleted</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('orderedList')">
                                        <v-icon>mdi-format-list-numbered</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('alignLeft')">
                                        <v-icon>mdi-format-align-left</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('alignCenter')">
                                        <v-icon>mdi-format-align-center</v-icon>
                                    </v-btn>
                                    <v-btn @click="toggleFormat('alignRight')">
                                        <v-icon>mdi-format-align-right</v-icon>
                                    </v-btn>
                                </div>
                                <editor-content :editor="descriptionEditor" placeholder="Añadir descripción" />
                            </div>

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

                                    <!-- Editor de contenido de la sección con feedback visual y barra de herramientas -->
                                    <div class="editor-wrapper">
                                        <div v-if="sectionEditors[index]" class="button-group">
                                            <v-btn @click="toggleFormat('bold', sectionEditors[index])">
                                                <v-icon>mdi-format-bold</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('italic', sectionEditors[index])">
                                                <v-icon>mdi-format-italic</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('underline', sectionEditors[index])">
                                                <v-icon>mdi-format-underline</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('heading', sectionEditors[index], 1)">
                                                <v-icon>mdi-format-header-1</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('heading', sectionEditors[index], 2)">
                                                <v-icon>mdi-format-header-2</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('bulletList', sectionEditors[index])">
                                                <v-icon>mdi-format-list-bulleted</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('orderedList', sectionEditors[index])">
                                                <v-icon>mdi-format-list-numbered</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('alignLeft', sectionEditors[index])">
                                                <v-icon>mdi-format-align-left</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('alignCenter', sectionEditors[index])">
                                                <v-icon>mdi-format-align-center</v-icon>
                                            </v-btn>
                                            <v-btn @click="toggleFormat('alignRight', sectionEditors[index])">
                                                <v-icon>mdi-format-align-right</v-icon>
                                            </v-btn>
                                        </div>
                                        <editor-content :editor="sectionEditors[index]" placeholder="Añadir contenido de la sección" />
                                    </div>

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

                                        <!-- Editor de contenido de la lección con feedback visual y barra de herramientas -->
                                        <div class="editor-wrapper">
                                            <div v-if="lessonEditor" class="button-group">
                                                <v-btn @click="toggleFormat('bold', lessonEditor)">
                                                    <v-icon>mdi-format-bold</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('italic', lessonEditor)">
                                                    <v-icon>mdi-format-italic</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('underline', lessonEditor)">
                                                    <v-icon>mdi-format-underline</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('heading', lessonEditor, 1)">
                                                    <v-icon>mdi-format-header-1</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('heading', lessonEditor, 2)">
                                                    <v-icon>mdi-format-header-2</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('bulletList', lessonEditor)">
                                                    <v-icon>mdi-format-list-bulleted</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('orderedList', lessonEditor)">
                                                    <v-icon>mdi-format-list-numbered</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('alignLeft', lessonEditor)">
                                                    <v-icon>mdi-format-align-left</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('alignCenter', lessonEditor)">
                                                    <v-icon>mdi-format-align-center</v-icon>
                                                </v-btn>
                                                <v-btn @click="toggleFormat('alignRight', lessonEditor)">
                                                    <v-icon>mdi-format-align-right</v-icon>
                                                </v-btn>
                                            </div>
                                            <editor-content :editor="lessonEditor" placeholder="Añadir contenido de la lección" />
                                        </div>

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
                                                    <v-radio
                                                        v-model="question.correctAnswer"
                                                        :value="answerIndex"
                                                        label="Correcta"
                                                        @change="selectCorrectAnswer(index, questionIndex, answerIndex)"
                                                    ></v-radio>
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
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import axios from 'axios';

export default {
    name: 'CourseForm',
    components: {
        EditorContent,
    },
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
                        lesson: { title: '', content: '', questions: [] },
                    },
                ],
            },
            coverImage: null,
            files: [],
            descriptionEditor: null,
            sectionEditors: [],
            lessonEditor: null,
            isEditing: false,
            valid: true,
            feedbackMessage: '',
            feedbackType: 'error',
            rules: {
                required: (value) => !!value || 'Este campo es requerido.',
            },
        };
    },
    methods: {
        toggleFormat(format, editor = this.descriptionEditor, level = null) {
            if (level) {
                editor.chain().focus().toggleHeading({ level }).run();
            } else {
                editor.chain().focus()[`toggle${format.charAt(0).toUpperCase() + format.slice(1)}`]().run();
            }
        },
        addSection() {
            this.course.sections.push({
                title: '',
                content: '',
                media: [],
                lesson: null,
            });
            this.sectionEditors.push(
                new Editor({
                    extensions: [StarterKit, TextAlign, Underline],
                })
            );
            this.updateLessons();
        },
        removeSection() {
            if (this.course.sections.length > 1) {
                this.course.sections.pop();
                this.sectionEditors.pop();
                this.updateLessons();
            }
        },
        updateLessons() {
            this.course.sections.forEach((section, index) => {
                if (index === this.course.sections.length - 1) {
                    if (!section.lesson) {
                        section.lesson = { title: '', content: '', questions: [] };
                    }
                    this.$nextTick(() => {
                        if (!this.lessonEditor) {
                            this.lessonEditor = new Editor({
                                extensions: [StarterKit, TextAlign, Underline],
                                content: section.lesson.content || '',
                            });

                            this.lessonEditor.on('update', ({ editor }) => {
                                this.updateLessonContent(editor.getHTML());
                            });
                        }
                    });
                } else {
                    section.lesson = null;
                }
            });
        },
        updateLessonContent(content) {
            const lastSection = this.course.sections[this.course.sections.length - 1];
            lastSection.lesson.content = content;
            console.log("Contenido de la lección actualizado:", content);
        },
        addQuestion(sectionIndex) {
            const section = this.course.sections[sectionIndex];
            if (section.lesson) {
                section.lesson.questions.push({
                    text: '',
                    type: 'única',
                    points: 1,
                    answers: [],
                });
            }
        },
        addAnswer(sectionIndex, questionIndex) {
            const question = this.course.sections[sectionIndex].lesson.questions[questionIndex];
            if (question) {
                question.answers.push({
                    text: '',
                    correct: false,
                });
            }
        },
        selectCorrectAnswer(sectionIndex, questionIndex, answerIndex) {
            const question = this.course.sections[sectionIndex].lesson.questions[questionIndex];
            question.answers.forEach((answer, idx) => {
                answer.correct = idx === answerIndex;
            });
        },
        prepareFormData() {
            const formData = new FormData();
            formData.append('title', this.course.title);
            formData.append('description', this.descriptionEditor.getHTML());
            formData.append('instructor_id', this.course.instructor_id);
            if (this.coverImage) formData.append('cover_image', this.coverImage);
            this.files.forEach((file) => formData.append('files[]', file));

            this.course.sections.forEach((section, sectionIndex) => {
                formData.append(`sections[${sectionIndex}][title]`, section.title);
                formData.append(`sections[${sectionIndex}][content]`, this.sectionEditors[sectionIndex].getHTML());

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
                            formData.append(
                                `sections[${sectionIndex}][lesson][questions][${questionIndex}][answers][${answerIndex}][correct]`,
                                answer.correct ? '1' : '0'
                            );
                        });
                    });
                }
            });

            return formData;
        },
        submitForm() {
            console.log("Datos del curso:", this.course);
            console.log("Descripción HTML:", this.descriptionEditor.getHTML());
            this.sectionEditors.forEach((editor, index) => {
                console.log(`Contenido de la sección ${index + 1}:`, editor.getHTML());
            });
            if (this.lessonEditor) {
                console.log("Contenido de la lección:", this.lessonEditor.getHTML());
            }

            const lastSection = this.course.sections[this.course.sections.length - 1];
            if (!lastSection.lesson || !lastSection.lesson.title || !lastSection.lesson.content) {
                this.feedbackMessage = 'La lección de la última sección es obligatoria.';
                this.feedbackType = 'error';
                console.log("La lección de la última sección no está completa.");
                return;
            }

            const formData = this.prepareFormData();

            axios
                .post('/api/courses', formData)
                .then((response) => {
                    console.log("Curso creado exitosamente:", response.data);
                    this.$router.push('/courses');
                })
                .catch((error) => {
                    console.error('Error al enviar el formulario:', error);
                    if (error.response && error.response.data.errors) {
                        this.feedbackMessage = `Error al enviar el formulario: ${JSON.stringify(error.response.data.errors)}`;
                        this.feedbackType = 'error';
                    }
                });
        },
        fetchCourse() {
            const courseId = this.$route.params.id;
            if (courseId) {
                this.isEditing = true;
                axios
                    .get(`/api/courses/${courseId}/edit`)
                    .then((response) => {
                        this.course = response.data;
                        this.sectionsCount = this.course.sections.length;
                        this.sectionEditors = this.course.sections.map(
                            () =>
                                new Editor({
                                    extensions: [StarterKit, TextAlign, Underline],
                                })
                        );
                        this.descriptionEditor = new Editor({
                            extensions: [StarterKit, TextAlign, Underline],
                            content: this.course.description,
                        });
                        this.updateLessons();
                    })
                    .catch((error) => {
                        console.error('Error al obtener el curso:', error);
                    });
            } else {
                this.descriptionEditor = new Editor({
                    extensions: [StarterKit, TextAlign, Underline],
                    content: '',
                });
                this.sectionEditors.push(
                    new Editor({
                        extensions: [StarterKit, TextAlign, Underline],
                    })
                );
            }
        },
        goBackToCourses() {
            this.$router.push('/courses');
        },
        logout() {
            axios.post('/logout').then(() => {
                localStorage.removeItem('authToken');
                this.$router.push('/login');
            });
        },
    },
    created() {
        axios.get('/api/user').then((response) => {
            this.course.instructor_id = response.data.id;
            this.fetchCourse();
        });
    },
};
</script>

<style scoped>
.editor-wrapper {
    border: 1px solid #ddd;
    padding: 10px;
    margin-bottom: 20px;
    background-color: #fafafa;
}

.editor-wrapper:focus-within {
    border-color: #1976d2;
}

.button-group {
    margin-bottom: 10px;
}

.button-group .v-btn {
    margin-right: 5px;
    background-color: #f1f1f1;
    border: 1px solid #ccc;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
}

.button-group .v-btn.is-active {
    background-color: #ccc;
}

.editor-wrapper .ProseMirror {
    min-height: 150px;
    padding: 10px;
    font-size: 16px;
}
</style>
