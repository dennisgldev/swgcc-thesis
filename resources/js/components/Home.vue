<template>
  <div id="app">
    <v-app>
      <!-- Loader -->
      <v-overlay v-if="isLoading" absolute :value="true" opacity="0.8">
        <v-progress-circular indeterminate size="64">
          <v-icon large color="primary">mdi-loading</v-icon>
        </v-progress-circular>
      </v-overlay>

      <!-- AppBar -->
      <v-app-bar app color="white" flat class="shadow-sm">
        <v-container fluid class="d-flex align-center justify-space-between">
          <v-btn text class="fw-bold text-dark-blue" @click="navigateTo('/')">
            SWGCC
          </v-btn>

          <v-spacer></v-spacer>

          <v-btn
            class="d-none d-lg-block"
            color="primary"
            outlined
            @click="navigateTo('/login')"
          >
            Iniciar Sesión
          </v-btn>

          <v-app-bar-nav-icon @click.stop="drawer = !drawer" class="d-lg-none"></v-app-bar-nav-icon>

          <v-navigation-drawer v-model="drawer" app temporary right class="d-lg-none">
            <v-list dense>
              <v-list-item @click="navigateTo('/login')">
                <v-list-item-title>Iniciar Sesión</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-navigation-drawer>
        </v-container>
      </v-app-bar>

      <!-- Main Content -->
      <v-main>
        <v-container class="mt-5 pt-5">
          <!-- Hero Section -->
          <v-row class="hero-section mb-5" justify="center" align="center">
            <v-col cols="12" md="6" class="text-center">
              <h1 class="display-1 font-weight-bold text-dark-blue">
                Bienvenido a SWGCC
              </h1>
              <p class="lead">
                Sistema Web Open Source para la Gestión de Cursos de Capacitación.
              </p>
              <v-btn color="primary" @click="navigateTo('/login')" class="my-4">
                Iniciar Sesión
              </v-btn>
            </v-col>
            <v-col cols="12" md="6" class="hero-image-container">
              <img
                src="../../../public/uploads/home_images/UG.png"
                alt="Hero Image"
                class="animate__animated animate__fadeInRight w-100"
              >
            </v-col>
          </v-row>

          <!-- About Section -->
          <v-row id="about" class="mb-5">
            <v-col
              cols="12"
              md="4"
              v-for="(card, index) in aboutCards"
              :key="index"
            >
              <v-card
                class="hover-card animate__animated animate__fadeInUp h-100"
                :class="`animate__delay-${index}s`"
                elevation="2"
              >
                <v-card-title class="text-dark-blue d-flex align-center">
                  {{ card.title }}
                </v-card-title>
                <v-card-text class="flex-grow-1">
                  {{ card.text }}
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </v-main>

      <!-- Footer -->
      <v-footer color="white" padless>
        <v-col class="text-center py-4">
          &copy; 2024 Facultad de Ciencias Matemáticas y Físicas - Universidad de Guayaquil. Todos los derechos reservados.
        </v-col>
      </v-footer>
    </v-app>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const isLoading = ref(true);
const drawer = ref(false);

const aboutCards = [
  {
    title: 'Cursos impartidos por docentes',
    text: 'Nuestros cursos son ofertados por docentes expertos en la carrera de software.',
  },
  {
    title: 'Aprendizaje continuo',
    text: 'Aumenta tu conocimiento y agrega valor a tu carrera con nuestros cursos.',
  },
  {
    title: 'Acceso 24/7',
    text: 'Aprende a tu propio ritmo con acceso a nuestros cursos en cualquier momento.',
  },
];

const navigateTo = (path) => {
  window.location.href = path;
};

onMounted(() => {
  setTimeout(() => {
    isLoading.value = false;
  }, 2000);
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
@import 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css';

body {
  font-family: 'Roboto', sans-serif;
}

.text-dark-blue {
  color: #003366;
}

.hero-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
}

.hover-card {
  transition: transform 0.3s, box-shadow 0.3s;
}

.hover-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.hero-image-container {
  max-width: 30%;
}

.hero-image-container img {
  border-radius: 8px;
  width: 100%;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: rgba(255, 255, 255, 0.9);
}

.v-footer {
  background-color: #f8f9fa;
}

.v-btn {
  text-transform: none;
  font-weight: 500;
}

@media (max-width: 768px) {
  .hero-section {
    flex-direction: column;
    text-align: center;
  }

  .hero-content,
  .hero-image-container {
    max-width: 100%;
  }
}
</style>
