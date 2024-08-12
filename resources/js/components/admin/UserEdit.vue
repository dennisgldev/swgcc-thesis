<template>
    <v-container>
      <v-row>
        <v-col cols="12">
          <h2>Editar Usuario</h2>
          <v-form @submit.prevent="updateUser">
            <v-text-field
              v-model="user.name"
              label="Nombre"
              required
            ></v-text-field>
            <v-text-field
              v-model="user.cedula"
              label="Cédula"
              required
            ></v-text-field>
            <v-text-field
              v-model="user.email"
              label="Correo Electrónico"
              type="email"
              required
            ></v-text-field>
            <v-text-field
              v-model="user.password"
              label="Contraseña"
              type="password"
              required
            ></v-text-field>
            <v-select
              v-model="user.role_id"
              :items="roles"
              item-text="name"
              item-value="id"
              label="Rol"
              required
            ></v-select>
            <v-btn type="submit" color="primary">Actualizar Usuario</v-btn>
          </v-form>
        </v-col>
      </v-row>
    </v-container>
  </template>
  
  <script>
  import axios from 'axios';
  export default {
    name: 'UserEdit',
    data() {
      return {
        user: {
          name: '',
          cedula: '',
          email: '',
          password: '',
          role_id: null,
        },
        roles: [],
      };
    },
    methods: {
  async fetchUser() {
    const userId = this.$route.params.userId; // Obtiene el ID del usuario desde la URL
    try {
      const response = await axios.get(`/api/users/${userId}/edit`);
      this.user = response.data; // Pre-rellena el formulario con los datos del usuario
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  },
  async fetchRoles() {
    try {
      const response = await axios.get('/api/roles');
      this.roles = response.data.map(role => ({
        id: role.id,
        name: role.name,
      }));
    } catch (error) {
      console.error('Error fetching roles:', error);
    }
  },
  async updateUser() {
  const userId = this.$route.params.userId;
  const userData = {
    name: this.user.name,
    cedula: this.user.cedula,
    email: this.user.email,
    role_id: this.user.role_id,
  };

  // Solo enviar la contraseña si ha sido modificada
  if (this.user.password) {
    userData.password = this.user.password;
  }

  try {
    const response = await axios.put(`/api/users/${userId}`, userData);
    if (response.status === 200) {
      this.$router.push('/admin/users');
      alert('Usuario actualizado con éxito');
    }
  } catch (error) {
    console.error('Error updating user:', error);
    if (error.response && error.response.status === 422) {
      alert('Error en la validación de los datos: ' + JSON.stringify(error.response.data.errors));
    } else {
      alert('Hubo un error al actualizar el usuario. Por favor, revisa los datos e intenta nuevamente.');
    }
  }
}
},
created() {
  this.fetchUser(); // Carga los datos del usuario al montar el componente
  this.fetchRoles(); // Carga los roles disponibles
}
  };
  </script>
  