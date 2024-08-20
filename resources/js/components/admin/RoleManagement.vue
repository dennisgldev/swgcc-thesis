<template>
    <v-container>
        <v-row>
            <v-col cols="12">
                <h2>Gestión de Roles</h2>
                <v-btn color="primary" @click="openRoleDialog">Agregar Rol</v-btn>
                <v-data-table-virtual
                    :headers="roleHeaders"
                    :items="sortedRoles"
                    class="elevation-1"
                    hide-default-footer
                >
                    <template v-slot:item.name="{ item }">
                        {{ item.name.charAt(0).toUpperCase() + item.name.slice(1).toLowerCase() }}
                    </template>
                    <template v-slot:item.permissions="{ item }">
                        <v-list-item
                            v-for="permission in item.permissions"
                            :key="permission.id"
                            :title="permission.name.charAt(0).toUpperCase() + permission.name.slice(1).toLowerCase()"
                        >
                        </v-list-item>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon @click="openRoleEditDialog(item)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon @click="deleteRole(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table-virtual>
            </v-col>
        </v-row>

        <!-- Diálogo para crear/editar roles -->
        <v-dialog v-model="roleDialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span v-if="editingRole">Editar Rol</span>
                    <span v-else>Crear Rol</span>
                </v-card-title>
                <v-card-text>
                    <v-form ref="roleForm">
                        <v-text-field
                            label="Nombre del Rol"
                            v-model="role.name"
                            :rules="[rules.required]"
                        ></v-text-field>
                        <v-row>
                            <v-col cols="12">
                                <label>Permisos</label>
                                <v-checkbox
                                    v-for="permission in permissions"
                                    :key="permission.id"
                                    :label="permission.name"
                                    :value="permission.id"
                                    v-model="role.permissions"
                                ></v-checkbox>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" @click="saveRole">{{ editingRole ? 'Guardar Cambios' : 'Crear Rol' }}</v-btn>
                    <v-btn color="secondary" @click="closeRoleDialog">Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script>
import axios from 'axios';

export default {
    name: 'RoleManagement',
    data() {
        return {
            roles: [],
            permissions: [],
            role: {
                name: '',
                permissions: [], // Esta propiedad almacenará los IDs de los permisos seleccionados
            },
            roleHeaders: [
                { text: 'Nombre del Rol', value: 'name' },
                { text: 'Permisos', value: 'permissions' },
                { text: 'Acciones', value: 'actions', sortable: false },
            ],
            roleDialog: false,
            editingRole: false,
            rules: {
                required: value => !!value || 'Este campo es requerido',
            },
        };
    },
    computed: {
        sortedRoles() {
            return this.roles.sort((a, b) => a.name.localeCompare(b.name));
        }
    },
    methods: {
        async fetchRoles() {
            try {
                const response = await axios.get('/api/roles');
                this.roles = response.data.map(role => ({
                    id: role.id,
                    name: role.name,
                    permissions: role.permissions.map(p => ({ id: p.id, name: p.name })), // Asegurarse de que los permisos sean el objeto correcto
                }));
                console.log('Roles fetched:', this.roles);
            } catch (error) {
                console.error('Error fetching roles:', error);
            }
        },
        async fetchPermissions() {
            try {
                const response = await axios.get('/api/permissions');
                this.permissions = response.data; // Directamente asignar los permisos recibidos
                console.log('Permissions fetched:', this.permissions);
            } catch (error) {
                console.error('Error fetching permissions:', error);
            }
        },
        openRoleDialog() {
            this.editingRole = false;
            this.role = { name: '', permissions: [] };
            this.roleDialog = true;
        },
        openRoleEditDialog(role) {
            this.editingRole = true;
            this.role = {
                id: role.id,
                name: role.name,
                permissions: role.permissions.map(p => p.id), // Solo IDs de permisos
            };
            this.roleDialog = true;
            console.log('Editing role:', this.role);
        },
        async saveRole() {
            if (this.$refs.roleForm.validate()) {
                try {
                    console.log('Saving role:', this.role);
                    if (this.editingRole) {
                        await axios.put(`/api/roles/${this.role.id}`, this.role);
                    } else {
                        await axios.post('/api/roles', this.role);
                    }
                    this.fetchRoles();
                    this.roleDialog = false;
                } catch (error) {
                    console.error('Error saving role:', error);
                    alert('Hubo un error al guardar el rol.');
                }
            }
        },
        async deleteRole(role) {
            if (confirm(`¿Estás seguro de que deseas eliminar el rol ${role.name}?`)) {
                try {
                    await axios.delete(`/api/roles/${role.id}`);
                    this.fetchRoles();
                } catch (error) {
                    console.error('Error deleting role:', error);
                    alert('Hubo un error al eliminar el rol.');
                }
            }
        },
        closeRoleDialog() {
            this.roleDialog = false;
        },
    },
    created() {
        this.fetchRoles();
        this.fetchPermissions(); // Obtener los permisos cuando se carga el componente
    },
};
</script>

<style scoped>
/* Estilos específicos para la gestión de roles */
</style>
