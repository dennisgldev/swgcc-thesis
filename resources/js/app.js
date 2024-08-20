import './bootstrap';

// En main.js o App.vue
import axios from 'axios';

const token = localStorage.getItem('authToken');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}


import { createApp } from 'vue';
import App from './App.vue';
import { createVuetify } from 'vuetify';
import '@mdi/font/css/materialdesignicons.css';
import router from './router';
import 'vuetify/styles';
import { mdi } from 'vuetify/iconsets/mdi';


import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

const vuetify = createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi', // Establecer 'mdi' como el set de íconos predeterminado
        sets: {
            mdi, // Añadir el set de íconos Material Design Icons
        },
    },
});

createApp(App)
  .use(router)
  .use(vuetify)
  .mount('#app');
