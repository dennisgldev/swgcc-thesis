<template>
    <v-container>
      <v-row>
        <v-col cols="12">
          <h1>Panel de Administración</h1>
          <v-btn color="primary" @click="navigateTo('users')">Gestión de Usuarios</v-btn>
          <v-btn color="secondary" @click="navigateTo('roles')">Gestión de Roles</v-btn>
        </v-col>
      </v-row>
      <router-view />
    </v-container>
  </template>
  
  <script>
  export default {
    name: 'AdminDashboard',
    methods: {
      navigateTo(path) {
        this.$router.push(`/admin/${path}`);
      }
    }
  };
  </script>
  