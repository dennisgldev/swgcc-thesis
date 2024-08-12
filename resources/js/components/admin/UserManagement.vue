<template>
    <v-container>
      <v-row>
        <v-col cols="12">
          <h2>Gestión de Usuarios</h2>
          <v-btn color="primary" @click="createUser">Agregar Usuario</v-btn>
          <v-data-table
            :headers="headers"
            :items="users"
            :items-per-page="5"
            class="elevation-1"
          >
            <template v-slot:item.actions="{ item }">
              <v-btn icon @click="editUser(item)">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon @click="deleteUser(item)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-col>
      </v-row>
    </v-container>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'UserManagement',
    data() {
      return {
        headers: [
          { text: 'Cédula', value: 'cedula' },
          { text: 'Nombre', value: 'name' },
          { text: 'Correo Electrónico', value: 'email' },
          { text: 'Rol', value: 'role.name' },
          { text: 'Acciones', value: 'actions', sortable: false },
        ],
        users: [],
      };
    },
    methods: {
      async fetchUsers() {
        try {
          const response = await axios.get('/api/users');
          this.users = response.data;
        } catch (error) {
          console.error('Error fetching users:', error);
        }
      },
      createUser() {
        this.$router.push('/admin/users/create');
      },
      editUser(user) {
        this.$router.push(`/admin/users/${user.id}/edit`);
      },
      async deleteUser(user) {
        if (confirm(`¿Estás seguro de que deseas eliminar al usuario ${user.name}?`)) {
          try {
            await axios.delete(`/api/users/${user.id}`);
            this.fetchUsers(); // Actualiza la lista después de eliminar
          } catch (error) {
            console.error('Error deleting user:', error);
          }
        }
      }
    },
    created() {
      this.fetchUsers();
    }
  };
  </script>
  