import './bootstrap';
import axios from 'axios';
import { createApp } from 'vue';
import App from './App.vue';
import { createVuetify } from 'vuetify';
import '@mdi/font/css/materialdesignicons.css';
import router from './router';
import 'vuetify/styles';
import { mdi } from 'vuetify/iconsets/mdi';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

// Configuración del token de autorización en Axios
const token = localStorage.getItem('authToken');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Configuración de Vuetify
const vuetify = createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi', // Establecer 'mdi' como el set de íconos predeterminado
        sets: {
            mdi, // Añadir el set de íconos Material Design Icons
        },
    },
    locale: {
        defaultLocale: 'es', // Establecer 'es' como el idioma predeterminado
        messages: { es }, // Añadir los mensajes en español
    },
});
const options = {
    // You can set your default options here
};

// Crear la aplicación Vue y montarla
createApp(App)
  .use(router)
  .use(vuetify)
  .use(Toast, options)
  .use(VueSweetalert2)
  .mount('#app');
