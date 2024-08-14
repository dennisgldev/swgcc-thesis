<template>
    <v-app>
        <!-- Header -->
        <v-app-bar app color="white" elevation="3">
            <v-toolbar-title class="headline">SWGCC</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-toolbar-title class="text-center">
                Hola, {{ firstName }}
            </v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn text @click="openChangePasswordDialog">Cambiar Contraseña</v-btn>
            <v-btn text @click="createCourse" v-if="isTeacher">Crear Curso</v-btn>
            <v-btn text @click="logout">Cerrar Sesión</v-btn>
        </v-app-bar>

        <!-- Loader -->
        <v-overlay :value="loading" opacity="0.8">
            <v-progress-circular indeterminate size="64"></v-progress-circular>
        </v-overlay>

        <!-- Contenido Principal -->
        <v-main>
            <v-container>
                <v-row>
                    <v-col cols="12">
                        <h2 class="text-center">{{ title }}</h2>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col
                        v-for="course in filteredCourses"
                        :key="course.id"
                        cols="12" sm="6" md="3"
                    >
                        <v-card
                            @click="goToCourse(course.id)"
                            class="course-card"
                            hover
                            elevation="2"
                        >
                            <v-img
                                :src="course.cover_image ? `/uploads/${course.cover_image}` : '/images/default-cover.jpg'"
                                height="150px"
                            ></v-img>
                            <v-card-title>{{ course.title }}</v-card-title>
                            <v-card-subtitle>{{ course.instructor ? course.instructor.name : 'Instructor desconocido' }}</v-card-subtitle>
                            <v-card-text>
                                <v-chip
                                    :color="getCourseChipColor(course.enrollment_status)"
                                    dark
                                    class="ma-2"
                                >
                                    {{ course.enrollment_status }}
                                </v-chip>
                            </v-card-text>
                            <v-card-actions>
                                <v-btn
                                    text
                                    @click.stop="deleteCourse(course.id)"
                                    v-if="isTeacher && course.instructor_id === userId"
                                >
                                    Eliminar
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>
                <v-row v-if="filteredCourses.length === 0 && !loading">
                    <v-col cols="12">
                        <p class="text-center">No hay cursos disponibles en este momento.</p>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>

        <!-- Bottom Navigation -->
        <v-bottom-navigation
            v-model="bottomNav"
            color="white"
            grow
        >
            <v-btn @click="changeView('catalog')">
                <span>Catálogo</span>
            </v-btn>
<!--            <v-btn @click="changeView('myCourses')">-->
<!--                <span>Mis Cursos</span>-->
<!--            </v-btn>-->
<!--            <v-btn @click="changeView('reports')">-->
<!--                <span>Reportes</span>-->
<!--            </v-btn>-->
        </v-bottom-navigation>

        <!-- Footer -->
        <v-footer app color="white" flat>
            <v-col class="text-center">
                &copy; 2024 SWGCC
            </v-col>
        </v-footer>

        <!-- Dialogo para cambiar la contraseña -->
        <v-dialog v-model="changePasswordDialog" persistent max-width="500">
            <v-card>
                <v-card-title>
                    <span class="headline">Cambiar Contraseña</span>
                </v-card-title>
                <v-card-text>
                    <v-form ref="changePasswordForm" v-model="valid" lazy-validation>
                        <v-text-field
                            label="Contraseña Actual"
                            v-model="currentPassword"
                            :rules="[rules.required]"
                            type="password"
                            required
                        ></v-text-field>
                        <v-text-field
                            label="Nueva Contraseña"
                            v-model="newPassword"
                            :rules="[rules.required, rules.min]"
                            type="password"
                            required
                        ></v-text-field>
                        <v-text-field
                            label="Confirmar Nueva Contraseña"
                            v-model="confirmPassword"
                            :rules="[rules.required, confirmPasswordRule]"
                            type="password"
                            required
                        ></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" text @click="submitChangePassword">Cambiar</v-btn>
                    <v-btn color="secondary" text @click="closeChangePasswordDialog">Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-app>
</template>

<script>
import axios from 'axios';

export default {
    name: 'CourseList',
    data() {
        return {
            courses: [],
            enrolledCourses: [],
            isTeacher: false,
            loading: true,
            bottomNav: 'catalog',
            title: 'Catálogo de Cursos',
            userId: null,
            userName: '', // Nueva propiedad para almacenar el nombre completo del usuario
            changePasswordDialog: false,
            currentPassword: '',
            newPassword: '',
            confirmPassword: '', // Asegúrate de que esta propiedad esté definida
            feedbackMessage: '',
            feedbackDialog: false,
            valid: true,
            rules: {
                required: value => !!value || 'Este campo es requerido.',
                min: value => value.length >= 8 || 'La nueva contraseña debe tener al menos 8 caracteres.',
            },
        };
    },
    computed: {
        filteredCourses() {
            if (this.bottomNav === 'catalog') {
                return this.courses;
            } else if (this.bottomNav === 'myCourses') {
                return this.enrolledCourses.filter(course => course.enrollment_status === 'En curso' || course.enrollment_status === 'Pendiente');
            }
            return [];
        },
        firstName() {
            return this.userName.split(' ')[0]; // Obtener solo la primera palabra del nombre
        },
        confirmPasswordRule() {
            return value => value === this.newPassword || 'Las contraseñas no coinciden.';
        }
    },
    methods: {
        fetchCourses() {
            this.loading = true;
            console.log('Fetching courses...');

            axios.get('/api/courses')
                .then(response => {
                    console.log('Courses fetched:', response.data);
                    this.courses = response.data.map(course => {
                        const enrollment = course.enrollments?.find(enrollment => enrollment.user_id === this.userId);
                        return {
                            ...course,
                            enrollment_status: enrollment ? enrollment.status : 'Disponible',
                        };
                    });
                })
                .catch(error => {
                    console.error('Error fetching courses:', error);
                })
                .finally(() => {
                    this.loading = false;
                    console.log('Courses fetch completed.');
                });
        },
        fetchEnrolledCourses() {
            this.loading = true;
            console.log('Fetching enrolled courses...');
            axios.get('/api/enrolled-courses')
                .then(response => {
                    console.log('Enrolled courses fetched:', response.data);
                    this.enrolledCourses = response.data;
                })
                .catch(error => {
                    console.error('Error fetching enrolled courses:', error);
                })
                .finally(() => {
                    this.loading = false;
                    console.log('Enrolled courses fetch completed.');
                });
        },
        getUserId() {
            axios.get('/api/user')
                .then(response => {
                    this.userId = response.data.id;
                    this.isTeacher = response.data.role_id === 2;
                    this.userName = response.data.name; // Obtener el nombre completo del usuario
                    this.fetchCourses();
                    this.fetchEnrolledCourses();
                })
                .catch(error => {
                    console.error('Error fetching user data:', error);
                    this.$router.push('/login');
                });
        },
        goToCourse(courseId) {
            this.$router.push(`/courses/${courseId}`);
        },
        createCourse() {
            this.$router.push('/courses/create');
        },
        deleteCourse(courseId) {
            console.log('Deleting course with ID:', courseId);
            axios.delete(`/api/courses/${courseId}`)
                .then(response => {
                    console.log('Course deleted:', response.data);
                    this.fetchCourses();
                    alert('Curso eliminado con éxito');
                })
                .catch(error => {
                    console.error('Error deleting course:', error);
                });
        },
        logout() {
            console.log('Logging out...');
            axios.post('/logout')
                .then(() => {
                    localStorage.removeItem('authToken');
                    console.log('Logout successful.');
                    this.$router.push('/login');
                })
                .catch(error => {
                    console.error('Error logging out:', error);
                });
        },
        openChangePasswordDialog() {
            this.changePasswordDialog = true;
        },
        closeChangePasswordDialog() {
            this.changePasswordDialog = false;
            this.currentPassword = '';
            this.newPassword = '';
            this.confirmPassword = '';
        },
        submitChangePassword() {
            axios.post('/api/change-password', {
                current_password: this.currentPassword,
                new_password: this.newPassword,
                new_password_confirmation: this.confirmPassword
            })
                .then(response => {
                    // Maneja la respuesta exitosa
                    console.log('Contraseña cambiada con éxito:', response.data.message);
                    this.feedbackMessage = 'Contraseña cambiada con éxito.';
                    this.feedbackDialog = true;
                    this.closeChangePasswordDialog(); // Cierra el diálogo después del cambio exitoso
                })
                .catch(error => {
                    // Maneja el error
                    if (error.response && error.response.data.errors) {
                        console.error('Error changing password:', error.response.data.errors);
                        this.feedbackMessage = error.response.data.errors.new_password ? error.response.data.errors.new_password[0] : 'Error desconocido';
                    } else {
                        console.error('Error changing password:', error);
                        this.feedbackMessage = 'Error al cambiar la contraseña.';
                    }
                    this.feedbackDialog = true;
                });
        },
        changeView(view) {
            this.bottomNav = view;
            console.log('Changing view to:', view);
            if (view === 'catalog') {
                this.title = 'Catálogo de Cursos';
                this.fetchCourses();
            } else if (view === 'myCourses') {
                this.title = 'Mis Cursos';
                this.fetchEnrolledCourses();
            } else if (view === 'reports') {
                this.title = 'Reportes';
            }
        },
        getCourseChipColor(status) {
            switch (status) {
                case 'Disponible':
                    return 'light-green lighten-1';
                case 'En curso':
                    return 'amber lighten-1';
                case 'Finalizado':
                    return 'light-blue lighten-1';
                default:
                    return 'grey lighten-1';
            }
        }
    },
    created() {
        console.log('Component created. Initializing data fetch...');
        this.getUserId();
    }
};
</script>

<style scoped>
.course-card {
    cursor: pointer;
    transition: transform 0.3s;
    margin-bottom: 16px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    font-family: 'Roboto', sans-serif;
}

.course-card:hover {
    transform: scale(1.02);
}

.text-center {
    text-align: center;
}

.overlay {
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.overlay img {
    width: 50px;
    height: 50px;
}

.btn {
    font-family: 'Roboto', sans-serif;
    font-weight: 500;
}

.toolbar-title {
    font-family: 'Roboto', sans-serif;
    font-weight: 700;
}

.card-title {
    font-family: 'Roboto', sans-serif;
    font-weight: 700;
    font-size: 1.2rem;
}

.app-bar {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

    font-family: 'Roboto', sans-serif;
    font-weight: 500;
}

.v-toolbar-title {
    font-family: 'Roboto', sans-serif;
    font-weight: 700;
}

.v-card-title {
    font-family: 'Roboto', sans-serif;
    font-weight: 700;
    font-size: 1.2rem;
}

.v-app-bar {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
