<template>
    <v-container>
        <h2>Gestión de Roles Personalizados</h2>
        <v-card>
            <v-card-title>Roles Personalizados Disponibles</v-card-title>
            <v-card-text>
                <v-data-table
                    :headers="headers"
                    :items="roles"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon @click="openEditDialog(item)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon @click="deleteRole(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </v-card-text>
            <v-card-actions>
                <v-btn color="primary" @click="openCreateDialog">Agregar Rol</v-btn>
            </v-card-actions>
        </v-card>

        <!-- Diálogo para crear/editar roles personalizados -->
        <v-dialog v-model="dialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span v-if="editingRole">Editar Rol Personalizado</span>
                    <span v-else>Crear Rol Personalizado</span>
                </v-card-title>
                <v-card-text>
                    <v-form ref="roleForm">
                        <v-text-field
                            label="Nombre del Rol"
                            v-model="role.name"
                            :rules="[rules.required]"
                        ></v-text-field>
                        <!-- Selector de roles base como radio buttons -->
                        <v-radio-group
                            v-model="role.role_id"
                            :rules="[rules.required]"
                            label="Rol Base Asociado"
                        >
                            <v-radio
                                v-for="roleBase in rolesBase"
                                :key="roleBase.id"
                                :label="roleBase.name"
                                :value="roleBase.id"
                            ></v-radio>
                        </v-radio-group>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue-grey lighten-1" @click="saveRole">{{ editingRole ? 'Guardar Cambios' : 'Crear Rol' }}</v-btn>
                    <v-btn color="secondary" @click="closeDialog">Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script>
import axios from 'axios';

export default {
    name: 'CustomRoleManagement',
    data() {
        return {
            roles: [],
            role: {
                name: '',
                role_id: null, // ID del rol base seleccionado
            },
            rolesBase: [
                { id: 1, name: 'Gestionar usuarios' },
                { id: 2, name: 'Gestionar cursos' },
                { id: 3, name: 'Realizar cursos' },
                { id: 4, name: 'Externo' },
            ],
            headers: [
                { text: 'Nombre del Rol Personalizado', value: 'name' },
                { text: 'Rol Base Asociado', value: 'role_base' }, // Nueva columna para mostrar el rol base asociado
                { text: 'Acciones', value: 'actions', sortable: false },
            ],
            dialog: false,
            editingRole: false, // Indica si se está editando o creando un rol
            rules: {
                required: value => !!value || 'Este campo es requerido',
            },
        };
    },
    methods: {
        async fetchRoles() {
            try {
                const response = await axios.get('/api/custom_roles');
                this.roles = response.data.map(role => ({
                    ...role,
                    role_base: this.rolesBase.find(r => r.id === role.role_id)?.name || 'N/A'
                }));
                console.log('Roles fetched:', this.roles);
            } catch (error) {
                console.error('Error fetching roles:', error);
            }
        },
        openCreateDialog() {
            this.editingRole = false;
            this.role = { name: '', role_id: null }; // Inicializa el rol personalizado
            this.dialog = true;
            console.log('Opening create dialog:', this.role);
        },
        openEditDialog(role) {
            this.editingRole = true;
            this.role = { ...role, role_id: role.role_id };
            this.dialog = true;
            console.log('Opening edit dialog:', this.role);
        },
        async saveRole() {
            if (this.$refs.roleForm.validate()) {
                try {
                    const payload = {
                        name: this.role.name,
                        role_id: this.role.role_id,
                    };

                    console.log('Payload to save role:', payload);

                    if (this.editingRole) {
                        await axios.put(`/api/custom_roles/${this.role.id}`, payload);
                    } else {
                        await axios.post('/api/custom_roles', payload);
                    }
                    this.dialog = false;
                    this.fetchRoles();
                } catch (error) {
                    console.error('Error saving role:', error);
                    if (error.response && error.response.status === 422) {
                        alert('Hubo un error con los datos proporcionados: ' + JSON.stringify(error.response.data.errors));
                    } else {
                        alert('Hubo un error al guardar el rol. Por favor, intenta nuevamente.');
                    }
                }
            }
        },
        async deleteRole(role) {
            if (confirm(`¿Estás seguro de que deseas eliminar el rol ${role.name}?`)) {
                try {
                    await axios.delete(`/api/custom_roles/${role.id}`);
                    this.fetchRoles();
                } catch (error) {
                    console.error('Error deleting role:', error);
                }
            }
        },
        closeDialog() {
            this.dialog = false;
        },
    },
    created() {
        this.fetchRoles();
    },
};
</script>

<style scoped>
/* Estilos específicos para la gestión de roles personalizados */
</style>
