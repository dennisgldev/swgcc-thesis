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
                                    :color="getCourseChipColor(course.status)"
                                    dark
                                    class="ma-2"
                                >
                                    {{ course.status }}
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

        <!-- Botón para Generar Reporte en PDF -->
        <v-row justify="center" class="mt-5">
            <v-btn color="primary" @click="generatePdf">Generar Reporte en PDF</v-btn>
        </v-row>

        <!-- Footer -->
        <!-- <v-footer app color="white" flat>
            <v-col class="text-center">
                &copy; 2024 SWGCC
            </v-col>
        </v-footer> -->

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
import { jsPDF } from "jspdf";
import "jspdf-autotable"; // Importando la librería para tablas

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
                return this.enrolledCourses.filter(course => course.status === 'En curso' || course.status === 'Finalizado');
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
        async fetchCourses() {
            this.loading = true;
            console.log('Fetching courses...');

            try {
                const response = await axios.get('/api/courses');
                console.log('Courses API Response:', response);
                this.courses = response.data.map(course => {
                    const enrollment = course.enrollments?.find(enrollment => enrollment.user_id === this.userId);
                    return {
                        ...course,
                        status: enrollment ? enrollment.status : 'Disponible',
                    };
                });
                console.log('Courses:', this.courses);
            } catch (error) {
                console.error('Error fetching courses:', error.response ? error.response : error);
            } finally {
                this.loading = false;
                console.log('Courses fetch completed.');
            }
        },
        async fetchEnrolledCourses() {
            this.loading = true;
            console.log('Fetching enrolled courses...');

            try {
                const response = await axios.get('/api/enrolled-courses');
                console.log('Enrolled Courses API Response:', response);
                this.enrolledCourses = response.data;
                console.log('Enrolled Courses:', this.enrolledCourses);
            } catch (error) {
                console.error('Error fetching enrolled courses:', error.response ? error.response : error);
            } finally {
                this.loading = false;
                console.log('Enrolled courses fetch completed.');
            }
        },
        async fetchLastGrades() {
            console.log("Fetching last grades for each course...");
            for (let course of this.enrolledCourses) {
                try {
                    const courseResponse = await axios.get(`/api/courses/${course.id}`);
                    const lastSection = courseResponse.data.sections.slice(-1)[0];
                    const lastLesson = lastSection.lessons.slice(-1)[0];
                    const lessonResponse = await axios.get(`/api/lessons/${lastLesson.id}/responses`);
                    course.last_grade = lessonResponse.data ? lessonResponse.data.score : null;
                    console.log(`Last grade for course ${course.title}: ${course.last_grade}`);
                } catch (error) {
                    console.error(`Error fetching last grade for course ${course.title}:`, error);
                    course.last_grade = null; // Si hay un error, asegura que no haya undefined
                }
            }
        },
        async generatePdf() {
            // Asegurarse de que la data esté cargada antes de generar el PDF
            console.log("Iniciando generación de PDF...");

            await this.fetchCourses();
            console.log("Cursos cargados:", this.courses);

            await this.fetchEnrolledCourses();
            console.log("Cursos inscritos cargados:", this.enrolledCourses);

            await this.fetchLastGrades();
            console.log("Calificaciones finales cargadas para los cursos:", this.enrolledCourses);

            const doc = new jsPDF();

            // Título del Reporte
            console.log("Generando título del reporte...");
            doc.setFontSize(22);
            doc.setTextColor(33, 150, 243); // Azul para el título
            doc.text('Reporte de Cursos', 105, 20, null, null, 'center');

            // Subtítulo con el nombre del usuario
            console.log("Añadiendo nombre del usuario al reporte:", this.userName);
            doc.setFontSize(14);
            doc.setTextColor(0, 0, 0); // Negro para el texto
            doc.text(`Usuario: ${this.userName}`, 105, 30, null, null, 'center');

            // Listado de cursos en curso
            console.log("Añadiendo listado de cursos en curso...");
            doc.setFontSize(16);
            doc.text('Cursos en curso:', 14, 45);

            let y = 55;
            let enCursoCourses = this.enrolledCourses.filter(course => course.status === 'En curso');
            if (enCursoCourses.length === 0) {
                console.log("No hay cursos en curso.");
                doc.setFontSize(12);
                doc.text('Ningún curso en curso', 14, y);
                y += 10;
            } else {
                enCursoCourses.forEach((course, index) => {
                    console.log(`Añadiendo curso en curso: ${course.title}`);
                    doc.setFontSize(12);
                    doc.text(`${index + 1}. ${course.title}`, 14, y);
                    if (course.last_grade !== null && course.last_grade !== undefined) {
                        doc.text(`Última Calificación: ${course.last_grade}`, 14, y + 8);
                        y += 12;
                    }
                    y += 8;
                });
            }

            // Listado de cursos finalizados
            console.log("Añadiendo listado de cursos finalizados...");
            doc.setFontSize(16);
            doc.text('Cursos finalizados:', 14, y + 15);

            y += 25;
            let finalizadoCourses = this.enrolledCourses.filter(course => course.status === 'Finalizado');
            if (finalizadoCourses.length === 0) {
                console.log("No hay cursos finalizados.");
                doc.setFontSize(12);
                doc.text('Ningún curso finalizado', 14, y);
                y += 10;
            } else {
                finalizadoCourses.forEach((course, index) => {
                    console.log(`Añadiendo curso finalizado: ${course.title}`);
                    doc.setFontSize(12);
                    doc.text(`${index + 1}. ${course.title}`, 14, y);
                    if (course.last_grade !== null && course.last_grade !== undefined) {
                        doc.text(`Última Calificación: ${course.last_grade}`, 14, y + 8);
                        y += 12;
                    }
                    y += 8;
                });
            }

            // Tabla de resumen
            console.log("Generando tabla de resumen...");
            doc.autoTable({
                startY: y + 20,
                head: [['Curso', 'Estado', 'Última Calificación']],
                body: this.enrolledCourses.map(course => {
                    console.log(`Añadiendo fila a la tabla: Curso: ${course.title}, Estado: ${course.status}, Última Calificación: ${course.last_grade !== null && course.last_grade !== undefined ? course.last_grade : 'N/A'}`);
                    return [
                        course.title,
                        course.status,
                        course.last_grade !== null && course.last_grade !== undefined ? course.last_grade : 'N/A'
                    ];
                }),
                theme: 'grid',
                headStyles: { fillColor: [33, 150, 243] }, // Azul para el encabezado
            });

            // Añadir un gráfico de barras para el progreso
            console.log("Generando gráfico de barras para el progreso...");
            const completedCourses = this.enrolledCourses.filter(course => course.status === 'Finalizado').length;
            const inProgressCourses = this.enrolledCourses.filter(course => course.status === 'En curso').length;
            const chartX = 14;
            const chartY = doc.autoTable.previous.finalY + 20;
            const chartWidth = 160;
            const chartHeight = 10;

            // Barra de "En curso"
            doc.setFillColor(33, 150, 243); // Azul para "En curso"
            doc.rect(chartX, chartY, chartWidth * (inProgressCourses / this.enrolledCourses.length), chartHeight, 'F');
            doc.setTextColor(255, 255, 255); // Blanco para el texto
            doc.text(`En curso: ${inProgressCourses}`, chartX + 2, chartY + 7);

            // Barra de "Finalizados"
            doc.setFillColor(76, 175, 80); // Verde para "Finalizados"
            doc.rect(chartX, chartY + 15, chartWidth * (completedCourses / this.enrolledCourses.length), chartHeight, 'F');
            doc.text(`Finalizados: ${completedCourses}`, chartX + 2, chartY + 22);

            // Pie de página
            console.log("Añadiendo pie de página...");
            doc.setFontSize(10);
            doc.setTextColor(150, 150, 150); // Gris para el pie de página
            doc.text('Reporte generado por SWGCC', 105, 290, null, null, 'center');

            // Descargar el PDF con el nombre del usuario
            const pdfFileName = `Reporte_de_${this.userName}.pdf`;
            console.log(`Guardando PDF con el nombre: ${pdfFileName}`);
            doc.save(pdfFileName);
        },
        getUserId() {
            console.log('Fetching user data...');
            axios.get('/api/user')
                .then(response => {
                    console.log('User API Response:', response);
                    this.userId = response.data.id;
                    this.isTeacher = response.data.role_id === 2;
                    this.userName = response.data.name; // Obtener el nombre completo del usuario
                    this.fetchCourses();
                    this.fetchEnrolledCourses();
                })
                .catch(error => {
                    console.error('Error fetching user data:', error.response ? error.response : error);
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
                    console.error('Error deleting course:', error.response ? error.response : error);
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
                    console.error('Error logging out:', error.response ? error.response : error);
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
                    console.log('Contraseña cambiada con éxito:', response.data.message);
                    this.feedbackMessage = 'Contraseña cambiada con éxito.';
                    this.feedbackDialog = true;
                    this.closeChangePasswordDialog();
                })
                .catch(error => {
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
