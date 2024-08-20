<template>
    <v-container>
        <v-row>
            <v-col cols="12">
                <h2>Gestión de Usuarios</h2>
                <v-btn color="primary" @click="openUserDialog">Agregar Usuario</v-btn>
                <v-text-field
                    v-model="search"
                    label="Buscar por cédula"
                    class="mt-4"
                    append-icon="mdi-magnify"
                ></v-text-field>
                <v-data-table-virtual
                    :headers="userHeaders"
                    :items="filteredUsers"
                    :items-per-page="10"
                    :search="search"
                    :sort-by="['name']"
                    class="elevation-1"
                >
                    <template v-slot:item.name="{ item }">
                        {{ item.name.toUpperCase() }}
                    </template>
                    <template v-slot:item.cedula="{ item }">
                        {{ item.cedula.toUpperCase() }}
                    </template>
                    <template v-slot:item.email="{ item }">
                        {{ item.email.toLowerCase() }}
                    </template>
                    <template v-slot:item.role="{ item }">
                        {{ item.roles[0]?.name.charAt(0).toUpperCase() + item.roles[0]?.name.slice(1).toLowerCase() }}
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon @click="openUserEditDialog(item)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon @click="deleteUser(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table-virtual>
                <v-pagination
                    v-model="page"
                    :length="pageCount"
                    total-visible="10"
                    prev-icon="mdi-chevron-left"
                    next-icon="mdi-chevron-right"
                ></v-pagination>
            </v-col>
        </v-row>

        <!-- Diálogo para crear/editar usuarios -->
        <v-dialog v-model="userDialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span v-if="editingUser">Editar Usuario</span>
                    <span v-else>Crear Usuario</span>
                </v-card-title>
                <v-card-text>
                    <v-form ref="userForm">
                        <v-text-field
                            label="Nombre"
                            v-model="user.name"
                            :rules="[rules.required]"
                            @input="formatText('name', true)"
                        ></v-text-field>
                        <v-text-field
                            label="Cédula"
                            v-model="user.cedula"
                            :rules="[rules.required, rules.cedula]"
                            @input="formatText('cedula')"
                        ></v-text-field>
                        <v-text-field
                            label="Correo Electrónico"
                            v-model="user.email"
                            type="email"
                            :rules="[rules.required, rules.email]"
                            @input="formatText('email')"
                        ></v-text-field>
                        <v-text-field
                            label="Contraseña"
                            v-model="user.password"
                            type="password"
                            :rules="editingUser ? [] : [rules.required, rules.password]"
                        ></v-text-field>
                        <label>Rol</label>
                        <v-radio-group
                            v-model="user.role_id"
                            :rules="[rules.required]"
                        >
                            <v-radio
                                v-for="role in roles"
                                :key="role.id"
                                :label="role.name.toUpperCase()"
                                :value="role.id"
                            ></v-radio>
                        </v-radio-group>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" @click="saveUser">{{ editingUser ? 'Guardar Cambios' : 'Crear Usuario' }}</v-btn>
                    <v-btn color="secondary" @click="closeUserDialog">Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Integración de RoleManagement.vue para gestionar roles y permisos -->
        <role-management ref="roleManagement"></role-management>
    </v-container>
</template>

<script>
import axios from 'axios';
import RoleManagement from './RoleManagement.vue'; // Importa el componente para gestionar roles

export default {
    name: 'UserManagement',
    components: {
        RoleManagement
    },
    data() {
        return {
            users: [],
            roles: [],
            user: {
                name: '',
                cedula: '',
                email: '',
                password: '',
                role_id: null, // Cambiado a null para manejar un solo rol
            },
            userHeaders: [
                { text: 'Nombre', value: 'name' },
                { text: 'Cédula', value: 'cedula' },
                { text: 'Correo Electrónico', value: 'email' },
                { text: 'Rol', value: 'role' },
                { text: 'Acciones', value: 'actions', sortable: false },
            ],
            userDialog: false,
            editingUser: false,
            search: '', // Campo de búsqueda
            page: 1,
            itemsPerPage: 10,
            rules: {
                required: value => !!value || 'Este campo es requerido',
                email: value => {
                    const pattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/;
                    return pattern.test(value) || 'Correo electrónico no válido';
                },
                password: value => {
                    const pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                    return pattern.test(value) || 'La contraseña debe tener al menos 8 caracteres, una letra mayúscula, una letra minúscula, un número y un carácter especial.';
                },
                cedula: value => {
                    return value || 'Cédula ecuatoriana no válida';
                },
            },
        };
    },
    computed: {
        filteredUsers() {
            return this.users.filter(user =>
                user.cedula.toLowerCase().includes(this.search.toLowerCase())
            );
        },
        formattedUsers() {
            return this.filteredUsers.map(user => ({
                ...user,
                name: user.name.toUpperCase(),
                cedula: user.cedula.toLowerCase(),
                email: user.email.toLowerCase(),
            })).sort((a, b) => a.name.localeCompare(b.name));
        },
        pageCount() {
            return Math.ceil(this.filteredUsers.length / this.itemsPerPage);
        },
    },
    methods: {
        formatText(field, isName = false) {
            if (this.user[field]) {
                this.user[field] = isName 
                    ? this.user[field].toUpperCase() 
                    : this.user[field].toLowerCase();
            }
        },
        validarCedula(cedula) {
            if (cedula.length !== 10) return false;

            const digitos = cedula.split('').map(Number);
            const digitoVerificador = digitos.pop();

            let suma = 0;
            digitos.forEach((digito, indice) => {
                if (indice % 2 === 0) {
                    let multiplicado = digito * 2;
                    suma += multiplicado > 9 ? multiplicado - 9 : multiplicado;
                } else {
                    suma += digito;
                }
            });

            const ultimoDigito = (10 - (suma % 10)) % 10;
            return ultimoDigito === digitoVerificador;
        },
        async fetchUsers() {
            try {
                const response = await axios.get('/api/users');
                this.users = response.data;
            } catch (error) {
                console.error('Error fetching users:', error);
            }
        },
        async fetchRoles() {
            try {
                const response = await axios.get('/api/roles');
                this.roles = response.data;
            } catch (error) {
                console.error('Error fetching roles:', error);
            }
        },
        openUserDialog() {
            this.editingUser = false;
            this.user = { name: '', cedula: '', email: '', password: '', role_id: null };
            this.userDialog = true;
        },
        openUserEditDialog(user) {
            this.editingUser = true;
            this.user = { 
                ...user, 
                password: '', 
                role_id: user.roles.length > 0 ? user.roles[0].id : null // Asegura que solo un rol sea seleccionado
            };
            this.userDialog = true;
        },
        async saveUser() {
            if (this.$refs.userForm.validate()) {
                try {
                    const userPayload = { ...this.user };

                    // Si estás editando y la contraseña está vacía, elimina el campo de la carga útil
                    if (this.editingUser && !userPayload.password) {
                        delete userPayload.password;
                    }

                    if (this.editingUser) {
                        await axios.put(`/api/users/${this.user.id}`, userPayload);
                    } else {
                        await axios.post('/api/users', userPayload);
                    }
                    this.fetchUsers();
                    this.userDialog = false;
                } catch (error) {
                    console.error('Error saving user:', error);
                    alert('Hubo un error al guardar el usuario.');
                }
            }
        },
        async deleteUser(user) {
            if (confirm(`¿Estás seguro de que deseas eliminar al usuario ${user.name}?`)) {
                try {
                    await axios.delete(`/api/users/${user.id}`);
                    this.fetchUsers();
                } catch (error) {
                    console.error('Error deleting user:', error);
                    alert('Hubo un error al eliminar el usuario.');
                }
            }
        },
        closeUserDialog() {
            this.userDialog = false;
        },
    },
    created() {
        this.fetchUsers();
        this.fetchRoles();
    },
};
</script>

<style scoped>
/* Estilos específicos para la gestión de usuarios */
</style>
