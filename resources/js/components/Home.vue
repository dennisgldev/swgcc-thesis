<template>
    <v-container>
      <v-row>
        <v-col cols="12">
          <h1>Bienvenido a SWGCC</h1>
          <p>
            Este es el sistema de gestión de cursos de capacitación. Desde aquí puedes navegar a las diferentes secciones del sistema.
          </p>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12" md="4">
          <v-btn color="primary" block @click="navigateTo('/login')">
            Iniciar Sesión
          </v-btn>
        </v-col>
        <v-col cols="12" md="4">
          <v-btn color="secondary" block @click="navigateTo('/courses')">
            Ver Cursos
          </v-btn>
        </v-col>
        <v-col cols="12" md="4">
          <v-btn color="success" block @click="navigateTo('/register')">
            Registrarse
          </v-btn>
        </v-col>
      </v-row>
    </v-container>
  </template>
  
  <script>
  export default {
    name: 'Home',
    methods: {
      navigateTo(path) {
        this.$router.push(path);
      }
    }
  };
  </script>
  
  <style scoped>
  h1 {
    margin-bottom: 20px;
  }
  </style>
  