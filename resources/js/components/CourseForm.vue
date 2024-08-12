<template>
  <v-container>
    <v-row class="justify-center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>{{ isEditing ? 'Editar Curso' : 'Crear Curso' }}</v-card-title>
          <v-card-text>
            <v-form @submit.prevent="submitForm">
              <v-text-field v-model="course.title" label="Título" required></v-text-field>
              <v-textarea v-model="course.description" label="Descripción"></v-textarea>
              <v-file-input v-model="coverImage" label="Portada" accept="image/*"></v-file-input>
              <v-file-input v-model="files" label="Archivos adjuntos" multiple></v-file-input>

              <v-text-field
                v-model="sectionsCount"
                label="Número de secciones"
                type="number"
                min="1"
                required
                @input="generateSectionsFields"
              ></v-text-field>

              <v-tabs v-model="activeSection" grow>
                <v-tab v-for="(section, index) in course.sections" :key="index">
                  Sección {{ index + 1 }}
                </v-tab>
              </v-tabs>

              <v-tabs-items v-model="activeSection">
                <v-tab-item v-for="(section, index) in course.sections" :key="index">
                  <v-card flat>
                    <v-card-title>Sección {{ index + 1 }}</v-card-title>
                    <v-text-field v-model="section.title" label="Título de la Sección" required></v-text-field>
                    <v-textarea v-model="section.content" label="Contenido de la Sección"></v-textarea>
                    <v-file-input v-model="section.media" label="Medios adjuntos" multiple></v-file-input>

                    <v-checkbox v-model="section.hasLesson" label="Agregar Lección"></v-checkbox>

                    <div v-if="section.hasLesson">
                      <v-card-title>Lección de la Sección {{ index + 1 }}</v-card-title>
                      <v-text-field v-model="section.lesson.title" label="Título de la Lección"></v-text-field>
                      <v-textarea v-model="section.lesson.content" label="Contenido de la Lección"></v-textarea>

                      <v-btn color="primary" @click="addQuestion(index)">Agregar Pregunta</v-btn>

                      <div v-for="(question, questionIndex) in section.lesson.questions" :key="questionIndex">
                        <v-text-field v-model="question.text" label="Pregunta"></v-text-field>
                        <v-select
                          v-model="question.type"
                          :items="['única', 'múltiple']"
                          label="Tipo de Respuesta"
                        ></v-select>
                        <v-text-field
                          v-model="question.points"
                          label="Puntos"
                          type="number"
                          min="0.1"
                          step="0.1"
                          @input="validateTotalPoints(index)"
                        ></v-text-field>

                        <v-btn color="secondary" @click="addAnswer(index, questionIndex)">Agregar Respuesta</v-btn>

                        <v-row v-for="(answer, answerIndex) in question.answers" :key="answerIndex" align="center">
                          <v-col cols="8">
                            <v-text-field v-model="answer.text" label="Respuesta"></v-text-field>
                          </v-col>
                          <v-col cols="4">
                            <v-checkbox v-model="answer.correct" label="Correcta"></v-checkbox>
                          </v-col>
                        </v-row>
                      </div>
                    </div>
                  </v-card>
                </v-tab-item>
              </v-tabs-items>

              <v-btn type="submit" color="primary">{{ isEditing ? 'Actualizar Curso' : 'Crear Curso' }}</v-btn>
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
        sections: []
      },
      coverImage: null,
      files: [],
      sectionsCount: 1,
      activeSection: 0,
      isEditing: false,
    };
  },
  methods: {
    generateSectionsFields() {
      this.course.sections = Array.from({ length: this.sectionsCount }, () => ({
        title: '',
        content: '',
        media: [],
        hasLesson: false,
        lesson: { 
          title: '', 
          content: '', 
          questions: [] 
        }
      }));
    },
    addQuestion(sectionIndex) {
      this.course.sections[sectionIndex].lesson.questions.push({ 
        text: '', 
        type: 'única', 
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
    validateTotalPoints(sectionIndex) {
      const lesson = this.course.sections[sectionIndex].lesson;
      const totalPoints = lesson.questions.reduce((sum, question) => sum + parseFloat(question.points || 0), 0);
      if (totalPoints > 10) {
        alert("La suma total de puntos de todas las preguntas en esta lección debe ser exactamente 10.");
      }
    },
    submitForm() {
      // Validar puntos de cada lección por sección
      for (const section of this.course.sections) {
        if (section.hasLesson) {
          const totalPoints = section.lesson.questions.reduce((total, question) => {
            return total + parseFloat(question.points || 0);
          }, 0);

          if (totalPoints !== 10) {
            alert(`La suma total de puntos de las preguntas en la lección de la sección "${section.title}" debe ser exactamente 10.`);
            return;
          }
        }
      }

      const formData = this.prepareFormData();

      const request = this.isEditing
        ? axios.put(`/api/courses/${this.course.id}`, formData)
        : axios.post('/api/courses', formData);

      request.then(response => {
        this.$router.push('/courses');
      }).catch(error => {
        if (error.response) {
          alert(`Error al enviar el formulario: ${JSON.stringify(error.response.data.errors)}`);
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

        if (section.hasLesson && section.lesson) {
          formData.append(`sections[${sectionIndex}][lesson][title]`, section.lesson.title);
          formData.append(`sections[${sectionIndex}][lesson][content]`, section.lesson.content);

          section.lesson.questions.forEach((question, questionIndex) => {
            formData.append(`sections[${sectionIndex}][lesson][questions][${questionIndex}][text]`, question.text);
            formData.append(`sections[${sectionIndex}][lesson][questions][${questionIndex}][type]`, question.type);
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
          })
          .catch(error => {
            console.error('Error al obtener el curso:', error);
          });
      }
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
