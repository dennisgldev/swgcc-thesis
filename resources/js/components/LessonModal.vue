<template>
    <v-dialog v-model="isVisible" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="headline">Agregar Lección</span>
        </v-card-title>
        <v-card-text>
          <v-form ref="form" v-model="valid" lazy-validation>
            <v-text-field v-model="lessonTitle" label="Título de la Lección" required></v-text-field>
            <v-textarea v-model="lessonContent" label="Contenido de la Lección" required></v-textarea>
            <!-- Aquí puedes agregar más campos según sea necesario -->
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="close">Cancelar</v-btn>
          <v-btn color="blue darken-1" text @click="submitLesson">Guardar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </template>
  
  <script>
  export default {
    props: {
      courseId: {
        type: Number,
        required: true
      },
      sectionId: {
        type: [Number, null],  // Acepta tanto un número como nulo
        required: true
      }
    },
    data() {
      return {
        isVisible: false,
        lessonTitle: '',
        lessonContent: '',
        valid: false,
      };
    },
    methods: {
      show() {
        this.isVisible = true;
      },
      close() {
        this.isVisible = false;
      },
      submitLesson() {
        if (!this.sectionId || !this.courseId) {
          console.error('Section ID or Course ID is missing.');
          return;
        }
  
        const payload = {
          title: this.lessonTitle,
          content: this.lessonContent,
          section_id: this.sectionId,
          course_id: this.courseId
        };
  
        axios.post(`/api/lessons`, payload)
          .then(response => {
            this.$emit('lessonAdded');
            this.close();
          })
          .catch(error => {
            console.error('Error al crear la lección:', error);
          });
      }
    }
  };
  </script>
  
  <style scoped>
  .headline {
    font-weight: bold;
  }
  </style>
  