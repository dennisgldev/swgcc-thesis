<template>
  <v-container>
    <v-row>
      <v-col cols="12" v-if="course">
        <!-- Imagen de portada del curso -->
        <v-card class="mb-5">
          <v-img :src="course.cover_image ? `/storage/${course.cover_image}` : '/images/default-cover.jpg'" height="300px" class="white--text">
            <v-card-title class="headline">{{ course.title }}</v-card-title>
          </v-img>
          <v-card-subtitle>{{ course.description }}</v-card-subtitle>
        </v-card>

        <!-- Archivos adjuntos del curso -->
        <div v-if="course.media && course.media.length > 0" class="mb-5">
          <h3>Archivos adjuntos del curso:</h3>
          <v-list dense>
            <v-list-item v-for="media in course.media" :key="media.id">
              <v-list-item-content>
                <v-list-item-title>
                  <template v-if="isVideo(media.file_type)">
                    <video controls :src="media.file_url" width="100%"></video>
                  </template>
                  <template v-else>
                    <a :href="media.file_url" target="_blank">{{ media.file_name }}</a>
                  </template>
                </v-list-item-title>
              </v-list-item-content>
            </v-list-item>
          </v-list>
        </div>

        <!-- Secciones -->
        <v-expansion-panels>
          <v-expansion-panel v-for="(section, sectionIndex) in course.sections" :key="section.id">
            <v-expansion-panel-header>
              <v-row align="center">
                <v-col cols="12" class="text-left">
                  <v-icon left>mdi-book-open-page-variant</v-icon>
                  {{ section.title }}
                </v-col>
              </v-row>
            </v-expansion-panel-header>
            <v-expansion-panel-content>
              <p>{{ section.content }}</p>

              <!-- Archivos adjuntos de la sección -->
              <div v-if="section.media && section.media.length > 0" class="mb-3">
                <h4>Archivos adjuntos de la sección:</h4>
                <v-list dense>
                  <v-list-item v-for="media in section.media" :key="media.id">
                    <v-list-item-content>
                      <v-list-item-title>
                        <template v-if="isVideo(media.file_type)">
                          <video controls :src="media.file_url" width="100%"></video>
                        </template>
                        <template v-else>
                          <a :href="media.file_url" target="_blank">{{ media.file_name }}</a>
                        </template>
                      </v-list-item-title>
                    </v-list-item-content>
                  </v-list-item>
                </v-list>
              </div>

              <!-- Lecciones (Solo si el usuario está inscrito) -->
              <v-expansion-panels v-if="isEnrolled && Array.isArray(section.lessons) && section.lessons.length > 0">
                <v-expansion-panel v-for="lesson in section.lessons" :key="lesson.id">
                  <v-expansion-panel-header>
                    <v-row align="center">
                      <v-col cols="12" class="text-left">
                        <v-icon left>mdi-file-document-box-outline</v-icon>
                        {{ lesson.title }}
                      </v-col>
                    </v-row>
                  </v-expansion-panel-header>
                  <v-expansion-panel-content>
                    <p>{{ lesson.content }}</p>

                    <!-- Archivos adjuntos de la lección -->
                    <div v-if="lesson.media && lesson.media.length > 0" class="mb-3">
                      <h4>Archivos adjuntos de la lección:</h4>
                      <v-list dense>
                        <v-list-item v-for="media in lesson.media" :key="media.id">
                          <v-list-item-content>
                            <v-list-item-title>
                              <template v-if="isVideo(media.file_type)">
                                <video controls :src="media.file_url" width="100%"></video>
                              </template>
                              <template v-else>
                                <a :href="media.file_url" target="_blank">{{ media.file_name }}</a>
                              </template>
                            </v-list-item-title>
                          </v-list-item-content>
                        </v-list-item>
                      </v-list>
                    </div>

                    <!-- Preguntas -->
                    <v-list v-if="Array.isArray(lesson.questions) && lesson.questions.length > 0">
                      <v-list-item v-for="question in lesson.questions" :key="question.id">
                        <v-list-item-content>
                          <v-list-item-title>{{ question.text }}</v-list-item-title>

                          <!-- Respuestas -->
                          <v-list v-if="Array.isArray(question.answers) && question.answers.length > 0">
                            <v-list-item v-for="answer in question.answers" :key="answer.id">
                              <v-list-item-content>
                                <v-radio-group v-model="selectedAnswers[lesson.id][question.id]" v-if="question.type === 'única'">
                                  <v-radio
                                    :label="answer.text"
                                    :value="answer.id"
                                    class="ml-3"
                                  ></v-radio>
                                </v-radio-group>

                                <v-checkbox
                                  v-if="question.type === 'múltiple'"
                                  :label="answer.text"
                                  :value="answer.id"
                                  v-model="selectedAnswers[lesson.id][question.id]"
                                  class="ml-3"
                                ></v-checkbox>
                              </v-list-item-content>
                            </v-list-item>
                          </v-list>

                        </v-list-item-content>
                      </v-list-item>
                    </v-list>

                    <!-- Botón de envío para la lección -->
                    <v-btn color="success" @click="submitLesson(lesson.id)" class="mt-3">Enviar Lección</v-btn>
                  </v-expansion-panel-content>
                </v-expansion-panel>
              </v-expansion-panels>

            </v-expansion-panel-content>
          </v-expansion-panel>
        </v-expansion-panels>

        <!-- Botón para finalizar curso (Solo en la última sección y si el curso está en curso) -->
        <v-btn
          v-if="isEnrolled && course.status === 'En curso' && isLastSection"
          color="primary"
          @click="finalizeCourse"
          class="mt-5"
        >
          Finalizar Curso
        </v-btn>

        <!-- Botón de inscripción (Solo si no está inscrito) -->
        <v-btn color="primary" @click="enrollInCourse" v-if="!isEnrolled" class="mt-5">Inscribirse en el Curso</v-btn>
        
      </v-col>
    </v-row>
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
    };
  },
  computed: {
    isLastSection() {
      if (!this.course || !this.course.sections) return false;
      const lastSectionId = this.course.sections[this.course.sections.length - 1].id;
      return this.course.sections.some(section => section.id === lastSectionId);
    },
  },
  methods: {
    fetchCourse() {
    const courseId = this.$route.params.id;
    console.log('Fetching course data for course ID:', courseId);

    axios.get(`/api/courses/${courseId}`)
        .then(response => {
            this.course = response.data;
            console.log('Course data received:', this.course);

            if (Array.isArray(this.course.sections)) {
                this.course.sections.forEach(section => {
                    this.selectedAnswers[section.id] = {};
                    console.log('Processing section:', section);

                    // Verificar si "lessons" es un objeto y convertirlo en un array si es necesario
                    if (section.lessons && !Array.isArray(section.lessons)) {
                        section.lessons = [section.lessons];
                        console.warn(`"lessons" convertido en array en sección con ID: ${section.id}`);
                    }

                    if (section.lessons && Array.isArray(section.lessons)) {
                        section.lessons.forEach(lesson => {
                            this.selectedAnswers[lesson.id] = {};
                            console.log('Processing lesson:', lesson);

                            if (lesson.questions && !Array.isArray(lesson.questions)) {
                                lesson.questions = [lesson.questions];
                                console.warn(`"questions" convertido en array en lección con ID: ${lesson.id}`);
                            }

                            if (lesson.questions && Array.isArray(lesson.questions)) {
                                lesson.questions.forEach(question => {
                                    this.selectedAnswers[question.id] = {};
                                    console.log('Processing question:', question);
                                });
                            } else {
                                console.warn('No questions found for lesson:', lesson.id);
                            }
                        });
                    } else {
                        console.warn('No lessons found for section:', section.id);
                    }
                });
            } else {
                console.warn('No sections found in course:', this.course.id);
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
    enrollInCourse() {
      if (this.isEnrolled) return;

      const courseId = this.course.id;
      console.log('Enrolling in course with ID:', courseId);

      axios.post(`/api/courses/${courseId}/enroll`)
        .then(response => {
          console.log('Enrollment successful:', response.data);
          alert('Inscripción realizada con éxito.');
          this.isEnrolled = true;
          this.course.status = 'En curso';
        })
        .catch(error => {
          console.error('Error enrolling in course:', error);
        });
    },
    finalizeCourse() {
      const courseId = this.course.id;
      console.log('Finalizing course with ID:', courseId);

      axios.post(`/api/courses/${courseId}/finalize`)
        .then(response => {
          console.log('Finalización de curso exitosa:', response.data);
          alert('¡Felicidades! Has finalizado el curso con éxito.');
          this.course.status = 'Finalizado';
        })
        .catch(error => {
          console.error('Error finalizing course:', error);
          alert('No has completado todas las lecciones o tu puntaje no cumple con el requisito mínimo de 7.');
        });
    },
    submitLesson(lessonId) {
      if (!this.isEnrolled) {
        alert('Debes estar inscrito en el curso para enviar las lecciones.');
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

      console.log('Submitting lesson with ID:', lessonId, 'Payload:', payload);

      axios.post(`/api/lessons/${lessonId}/submit`, payload)
        .then(response => {
          console.log('Lesson submitted successfully:', response.data);
          alert('Respuestas enviadas correctamente.');
        })
        .catch(error => {
          console.error('Error submitting lesson:', error);
          console.log('Error details:', error.response.data);
        });
    },
    checkRole() {
      axios.get('/api/user')
        .then(response => {
          this.isTeacher = response.data.role_id === 2;
          console.log('User role checked. Is Teacher:', this.isTeacher);

          if (this.course) {
            axios.get(`/api/courses/${this.course.id}/is-enrolled`)
              .then(res => {
                this.isEnrolled = res.data.is_enrolled;
                console.log('Enrollment status checked. Is Enrolled:', this.isEnrolled);
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
  },
  created() {
    console.log('CourseDetail component created.');
    this.fetchCourse();
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

.v-expansion-panel-header {
  background-color: #f5f5f5;
}

.v-expansion-panel-content {
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
