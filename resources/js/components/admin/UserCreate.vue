<template>
    <v-container>
        <v-row>
            <v-col cols="12">
                <h2>Agregar Usuario</h2>
                <v-form @submit.prevent="createUser">
                    <!-- Nombre -->
                    <v-text-field
                        v-model="user.name"
                        label="Nombre"
                        required
                    ></v-text-field>

                    <!-- Cédula -->
                    <v-text-field
                        v-model="user.cedula"
                        label="Cédula"
                        required
                    ></v-text-field>

                    <!-- Correo Electrónico -->
                    <v-text-field
                        v-model="user.email"
                        label="Correo Electrónico"
                        type="email"
                        required
                    ></v-text-field>

                    <!-- Contraseña -->
                    <v-text-field
                        v-model="user.password"
                        label="Contraseña"
                        type="password"
                        required
                    ></v-text-field>

                    <!-- Selección de Rol con Radio Buttons -->
                    <v-radio-group
                        v-model="user.role_id"
                        label="Rol"
                        required
                    >
                        <v-radio
                            v-for="role in allRoles"
                            :key="role.id"
                            :label="role.name"
                            :value="role.id"
                        ></v-radio>
                    </v-radio-group>

                    <!-- Botón Guardar Usuario -->
                    <v-btn type="submit" color="primary">Guardar Usuario</v-btn>
                </v-form>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import axios from 'axios';

export default {
    name: 'UserCreate',
    data() {
        return {
            user: {
                name: '',
                cedula: '',
                email: '',
                password: '',
                role_id: null,
            },
            roles: [], // Roles normales
            customRoles: [], // Roles personalizados
            allRoles: [], // Combinación de roles normales y personalizados
        };
    },
    methods: {
        async fetchRoles() {
            try {
                const [rolesResponse, customRolesResponse] = await Promise.all([
                    axios.get('/api/roles'),
                    axios.get('/api/custom_roles')
                ]);

                console.log('Roles fetched:', rolesResponse.data);
                console.log('Custom Roles fetched:', customRolesResponse.data);

                this.roles = rolesResponse.data.map(role => ({
                    id: role.id,
                    name: role.name
                }));

                this.customRoles = customRolesResponse.data.map(customRole => ({
                    id: customRole.id,
                    name: customRole.name
                }));

                // Combina ambos arrays para mostrarlos en un solo grupo de radio buttons
                this.allRoles = [...this.roles, ...this.customRoles];

            } catch (error) {
                console.error('Error fetching roles or custom roles:', error);
            }
        },
        async createUser() {
            try {
                const response = await axios.post('/api/users', this.user);
                if (response.status === 201) {
                    this.$router.push('/admin/users');
                    alert('Usuario creado con éxito'); // Feedback al usuario
                }
            } catch (error) {
                console.error('Error creating user:', error);

                if (error.response && error.response.status === 422) {
                    alert('Hubo un error con los datos proporcionados: ' + JSON.stringify(error.response.data.errors));
                } else {
                    alert('Hubo un error al crear el usuario. Por favor, intenta nuevamente.');
                }
            }
        },
    },
    created() {
        this.fetchRoles();
    },
};
</script>
