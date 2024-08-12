<template>
    <v-container>
      <v-row class="justify-center">
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Iniciar Sesión</v-card-title>
            <v-card-text>
              <v-form @submit.prevent="login">
                <v-text-field
                  v-model="cedula"
                  label="Cédula"
                  type="text"
                  required
                ></v-text-field>
                <v-text-field
                  v-model="password"
                  label="Contraseña"
                  type="password"
                  required
                ></v-text-field>
                <v-btn type="submit" color="primary" block>
                  Iniciar Sesión
                </v-btn>
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
    name: 'Login',
    data() {
      return {
        cedula: '',
        password: '',
      };
    },
    methods: {
  async login() {
    try {
      const response = await axios.post('/login', {
        cedula: this.cedula,
        password: this.password,
      });

      if (response.status === 200) {
        const role = response.data.role;
        const token = response.data.token;

        // Guardar el token en localStorage
        localStorage.setItem('authToken', token);

        // Configurar el encabezado Authorization para futuras solicitudes
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

        if (role === 'Administrador') {
          this.$router.push('/admin');
        } else {
          this.$router.push('/courses');
        }
      }
    } catch (error) {
      alert('Las credenciales proporcionadas no coinciden con nuestros registros.');
    }
  },
},

  };
  </script>
  