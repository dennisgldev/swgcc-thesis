<template>
    <v-container>
        <!-- Header -->
        <v-app-bar app color="blue-grey-lighten-4" elevation="3">
            <v-toolbar-title class="headline">SWGCC - Panel de Administración</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn text small @click="navigateTo('users')" color="white">Gestión de Usuarios</v-btn>
            <v-btn text small @click="logout" color="white">Cerrar Sesión</v-btn>
        </v-app-bar>

        <!-- Contenido Principal -->
        <v-row class="mt-5">
            <v-col cols="12" md="8" class="mx-auto">
                <h1 class="text-center mb-5">Bienvenido al Panel de Administración</h1>
                <p class="text-center">
                    Utiliza el menú de abajo para navegar a las diferentes secciones de administración.
                </p>

                <!-- Botones de Navegación -->
                <v-row justify="center" class="mt-5">
                    <v-btn
                        color="blue-lighten-4"
                        @click="navigateTo('users')"
                        class="mx-2"
                        small
                    >
                        Gestión de Usuarios y Roles
                    </v-btn>
                    <!-- Agrega más botones de navegación aquí si es necesario -->
                </v-row>
            </v-col>
        </v-row>

        <!-- Dialogo de Políticas de Privacidad -->
        <v-dialog v-model="privacyDialog" persistent max-width="600px">
            <v-card>
                <v-card-title class="headline">Políticas de Privacidad</v-card-title>
                <v-card-text>
                    <p>
                        Bienvenido al panel de administración de la plataforma e-learning SWGCC.
                        Antes de proceder, es importante que revises nuestras políticas de privacidad.
                        Al ingresar a este panel y utilizar las funcionalidades disponibles, aceptas cumplir con estas políticas.
                    </p>
                    <p>
                        En SWGCC, respetamos tu privacidad y nos comprometemos a proteger cualquier dato personal que nos proporciones.
                        Todos los datos de los usuarios se manejan con la mayor confidencialidad y se utilizan únicamente para
                        los propósitos definidos dentro de la plataforma. Como administrador, tienes acceso a información sensible,
                        por lo que te pedimos que manejes estos datos con responsabilidad y conforme a las normativas aplicables.
                    </p>
                    <p>
                        Si tienes alguna duda o inquietud sobre nuestras políticas de privacidad, no dudes en ponerte en contacto con nosotros.
                    </p>
                    <p>
                        Al hacer clic en "Aceptar", confirmas que has leído, comprendido y aceptado nuestras políticas de privacidad.
                    </p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue-grey lighten-1" @click="acceptPrivacyPolicy">Aceptar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Vista enrutada -->
        <router-view />
    </v-container>
</template>

<script>
export default {
    name: 'AdminDashboard',
    data() {
        return {
            privacyDialog: true, // Se muestra el diálogo de políticas al cargar el componente
        };
    },
    methods: {
        navigateTo(path) {
            this.$router.push(`/admin/${path}`);
        },
        logout() {
            this.$router.push('/login');
        },
        acceptPrivacyPolicy() {
            this.privacyDialog = false;
        }
    }
};
</script>

<style scoped>
h1 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 2rem;
}

.v-app-bar {
    background-color: #CFD8DC !important; /* blue-grey-lighten-4 */
}

.text-center {
    text-align: center;
}

.v-btn {
    min-width: auto;
    padding: 0 16px;
    font-size: 14px;
    font-weight: 500;
    text-transform: none;
    color: #263238 !important; /* Texto oscuro para buen contraste */
}

.mb-5 {
    margin-bottom: 3rem;
}
</style>
