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

                    <!-- Selección de Rol Base con Radio Buttons -->
                    <v-radio-group
                        v-model="user.role_id"
                        label="Rol Base"
                        required
                    >
                        <v-radio
                            v-for="role in rolesBase"
                            :key="role.id"
                            :label="role.name"
                            :value="role.id"
                        ></v-radio>
                    </v-radio-group>

                    <!-- Selección de Rol Personalizado con Radio Buttons -->
                    <v-radio-group
                        v-model="user.custom_role_id"
                        label="Rol Personalizado"
                    >
                        <v-radio
                            v-for="customRole in customRoles"
                            :key="customRole.id"
                            :label="customRole.name"
                            :value="customRole.id"
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
                role_id: null, // Rol base seleccionado
                custom_role_id: null, // Rol personalizado seleccionado
            },
            rolesBase: [], // Lista de roles base
            customRoles: [], // Lista de roles personalizados
        };
    },
    methods: {
        async fetchRoles() {
            try {
                const rolesBaseResponse = await axios.get('/api/roles');
                console.log('Roles Base fetched:', rolesBaseResponse.data);
                this.rolesBase = rolesBaseResponse.data;

                const customRolesResponse = await axios.get('/api/custom_roles');
                console.log('Custom Roles fetched:', customRolesResponse.data);
                this.customRoles = customRolesResponse.data;
            } catch (error) {
                console.error('Error fetching roles:', error);
            }
        },
        async createUser() {
            try {
                const payload = {
                    ...this.user,
                    role_id: this.user.custom_role_id || this.user.role_id, // Priorizar custom_role_id si está seleccionado
                };

                const response = await axios.post('/api/users', payload);
                if (response.status === 201) {
                    this.$router.push('/admin/users');
                    alert('Usuario creado con éxito');
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

<style scoped>
/* Estilos específicos para la creación de usuarios */
</style>
