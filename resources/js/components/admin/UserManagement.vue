<template>
    <v-container>
        <!-- Header -->
        <v-row class="mb-5">
            <v-col cols="12" class="text-center">
                <h2 class="display-1 font-weight-bold">Gestión de Usuarios</h2>
            </v-col>
        </v-row>

        <!-- Botón de Agregar Usuario -->
        <v-row class="mb-3 justify-center">
            <v-col cols="12" md="4" class="text-center">
                <v-btn color="primary" large @click="createUser">
                    <v-icon left>mdi-account-plus</v-icon>
                    Agregar Usuario
                </v-btn>
            </v-col>
        </v-row>

        <!-- Tabla de Usuarios -->
        <v-row>
            <v-col cols="12">
                <v-data-table
                    :headers="headers"
                    :items="users"
                    :items-per-page="5"
                    class="elevation-1"
                    dense
                >
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon @click="editUser(item)" color="blue">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon @click="deleteUser(item)" color="red">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                    <template v-slot:no-data>
                        <v-alert :value="true" color="info" icon="mdi-information">
                            No hay usuarios registrados en este momento.
                        </v-alert>
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
                { text: 'Cédula', value: 'cedula', align: 'start', sortable: true },
                { text: 'Nombre', value: 'name', align: 'start', sortable: true },
                { text: 'Correo Electrónico', value: 'email', align: 'start', sortable: true },
                { text: 'Rol', value: 'role.name', align: 'start', sortable: true },
                { text: 'Acciones', value: 'actions', align: 'center', sortable: false },
            ],
            users: [],
        };
    },
    methods: {
        async fetchUsers() {
            try {
                const response = await axios.get('/api/users');
                if (Array.isArray(response.data)) {
                    this.users = response.data;
                } else {
                    this.users = [];
                }
            } catch (error) {
                console.error('Error fetching users:', error);
                this.users = [];
            }
        },
        createUser() {
            this.$router.push('/admin/users/create');
        },
        editUser(user) {
            this.$router.push(`/admin/users/${user.id}/edit`);
        },
        async deleteUser(user) {
            const confirmed = confirm(`¿Estás seguro de que deseas eliminar al usuario ${user.name}?`);
            if (confirmed) {
                try {
                    await axios.delete(`/api/users/${user.id}`);
                    this.fetchUsers();
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

<style scoped>
.display-1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #1976d2;
}

.v-btn {
    font-size: 1rem;
    font-weight: 500;
}

.v-icon {
    margin-right: 0.5rem;
}

.text-center {
    text-align: center;
}
</style>
