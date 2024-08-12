<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h2>Cursos Disponibles</h2>
      </v-col>
    </v-row>
    <v-row>
      <v-col
        v-for="course in courses"
        :key="course.id"
        cols="12" sm="6" md="4"
      >
        <v-card @click="goToCourse(course.id)" class="course-card" hover elevation="3">
          <v-img
            :src="course.cover_image ? `/storage/${course.cover_image}` : '/images/default-cover.jpg'"
            height="200px"
          ></v-img>
          <v-card-title>{{ course.title }}</v-card-title>
          <v-card-subtitle>{{ course.instructor ? course.instructor.name : 'Instructor desconocido' }}</v-card-subtitle>
          <v-card-actions>
            <v-btn icon @click.stop="editCourse(course.id)" v-if="isTeacher">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
            <v-btn icon @click.stop="deleteCourse(course.id)" v-if="isTeacher">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
    <v-row v-if="courses.length === 0">
      <v-col cols="12">
        <p>No hay cursos disponibles en este momento.</p>
      </v-col>
    </v-row>
    <v-row v-if="isTeacher">
      <v-col cols="12">
        <v-btn color="primary" @click="createCourse">Crear Nuevo Curso</v-btn>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CourseList',
  data() {
    return {
      courses: [],
      isTeacher: false,
    };
  },
  methods: {
    fetchCourses() {
  axios.get('/api/courses')
    .then(response => {
      this.courses = response.data;
      this.courses.forEach(course => {
        if (!course.instructor) {
          console.error(`El curso con ID ${course.id} no tiene un instructor asociado.`);
        }
      });
    })
    .catch(error => {
      console.error('Error al obtener los cursos:', error);
    });
},
    goToCourse(courseId) {
      this.$router.push(`/courses/${courseId}`);
    },
    createCourse() {
      this.$router.push('/courses/create');
    },
    editCourse(courseId) {
      this.$router.push(`/courses/${courseId}/edit`);
    },
    deleteCourse(courseId) {
      axios.delete(`/api/courses/${courseId}`)
        .then(response => {
          this.fetchCourses();
          alert('Curso eliminado con éxito');
        })
        .catch(error => {
          console.error('Error al eliminar el curso:', error);
        });
    },
    checkRole() {
      console.log('Verificando rol del usuario...');
      axios.get('/api/user')
        .then(response => {
          console.log('Respuesta del usuarioooo:', response.data);
          this.isTeacher = response.data.role_id === 2; // Asume que el ID 2 es para "Docente"
          console.log('Es docente:', this.isTeacher);
        })
        .catch(error => {
          console.error('Error al obtener el rol del usuario:', error);
        });
    }
  },
  created() {
    console.log('Componente creado, inicializando...');
    this.fetchCourses();
    this.checkRole();
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
