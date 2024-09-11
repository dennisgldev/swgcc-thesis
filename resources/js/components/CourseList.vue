<template>
    <v-app>
        <!-- Header -->
        <v-app-bar app color="white" elevation="3">
            <v-toolbar-title class="headline">SWGCC</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-toolbar-title class="text-center">
                Hola, {{ firstName.charAt(0).toUpperCase() + firstName.slice(1).toLowerCase() }}
            </v-toolbar-title>
            <v-spacer></v-spacer>

            <!-- Botón para el Panel de Administración, visible solo para administradores -->
            <v-btn text v-if="userCan('panel de gestión de usuarios')" @click="goToAdminPanel">Admin Dashboard</v-btn>
            <v-btn v-if="userCan('gestión de cursos y reportería')" color="primary" @click="generatePdf">Generar Reporte en PDF</v-btn>
            <v-btn text @click="openChangePasswordDialog">Cambiar Contraseña</v-btn>
            <!-- Botón para Crear Curso, visible solo para usuarios con el permiso de gestión de cursos -->
            <v-btn text @click="createCourse" v-if="userCan('gestión de cursos y reportería')">Crear Curso</v-btn>
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
                                    v-if="userCan('gestión de cursos y reportería') && course.instructor_id === userId"
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

        <!-- Botón para Generar Reporte en PDF, visible solo para usuarios con el permiso de gestión de cursos -->
        <!-- <v-row justify="center" class="mt-5">
            <v-btn color="primary" @click="generatePdf">Generar Reporte en PDF</v-btn>
        </v-row> -->

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
import { jsPDF } from "jspdf";
import "jspdf-autotable";
import { useToast } from "vue-toastification";

export default {
    name: 'CourseList',
    setup() {
      const toast = useToast();
      return { toast }
    },
    data() {
        return {
            courses: [],
            enrolledCourses: [],
            isTeacher: true,
            loading: true,
            bottomNav: 'catalog',
            title: 'Catálogo de Cursos',
            userId: null,
            userName: '',
            userPermissions: [],
            changePasswordDialog: false,
            currentPassword: '',
            newPassword: '',
            confirmPassword: '',
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
            return this.userName.split(' ')[0];
        },
        confirmPasswordRule() {
            return value => value === this.newPassword || 'Las contraseñas no coinciden.';
        }
    },
    methods: {
        async fetchCourses() {
            this.loading = true;
            try {
                const response = await axios.get('/api/courses');
                this.courses = response.data.map(course => {
                    const enrollment = course.enrollments?.find(enrollment => enrollment.user_id === this.userId);
                    return {
                        ...course,
                        status: enrollment ? enrollment.status : 'Disponible',
                    };
                });
                console.log('Cursos obtenidos:', this.courses);
            } catch (error) {
                console.error('Error fetching courses:', error.response ? error.response : error);
            } finally {
                this.loading = false;
            }
        },
        async fetchEnrolledCourses() {
            this.loading = true;
            try {
                const response = await axios.get('/api/enrolled-courses');
                this.enrolledCourses = response.data;
                console.log('Cursos inscritos obtenidos:', this.enrolledCourses);
            } catch (error) {
                console.error('Error fetching enrolled courses:', error.response ? error.response : error);
            } finally {
                this.loading = false;
            }
        },
        async fetchPermissions() {
            try {
                const response = await axios.get('/api/user/permissions');
                this.userPermissions = response.data.permissions;
                console.log('Permisos obtenidos:', this.userPermissions);
            } catch (error) {
                console.error('Error fetching user permissions:', error);
            }
        },
        async generatePdf() {
            try {
                const response = await axios.get('/api/courses-with-enrollment');
                const courses = Array.isArray(response.data) ? response.data : [];

                if (courses.length === 0) {
                    console.warn('No se encontraron cursos.');
                    this.toast.warning('No se encontraron cursos');
                    return;
                }

                const doc = new jsPDF();
                let currentPage = 1;
                let y = 40; // Ajustar el valor inicial de y

                const addPageHeaderFooter = () => {
                    // Encabezado
                    doc.setFontSize(22);
                    doc.setTextColor(33, 150, 243);
                    doc.text('Reporte | SWGCC', 105, 15, null, null, 'center');
                    doc.setFontSize(14);
                    doc.setTextColor(0, 0, 0);
                    doc.text(`Generado por: ${this.userName.toUpperCase()}`, 105, 25, null, null, 'center');

                    // Pie de página: solo el número de página en la esquina inferior derecha
                    doc.setFontSize(10);
                    doc.setTextColor(150, 150, 150);
                    doc.text(`${currentPage}`, 200, 290); // Solo el número de página
                };

                addPageHeaderFooter();

                courses.forEach((course, courseIndex) => {
                    if (y + 100 > 280) { // Ajuste para crear nueva página si el contenido sobrepasa el límite
                        doc.addPage();
                        currentPage++;
                        addPageHeaderFooter();
                        y = 30; // Reiniciar la posición y después del encabezado
                    }

                    doc.setFontSize(14);
                    doc.setTextColor(60, 60, 60);
                    doc.text(`${courseIndex + 1}. ${course.title}`, 14, y);
                    y += 10;

                    if (course.enrollments && course.enrollments.length > 0) {
                        doc.setFontSize(14);
                        doc.text('Estudiantes inscritos:', 20, y);
                        y += 8;

                        const tableData = [];

                        course.enrollments.forEach((enrollment, studentIndex) => {
                            const studentName = enrollment.user ? enrollment.user.name.toUpperCase() : 'DESCONOCIDO';

                            enrollment.lesson_attempts.forEach(attempt => {
                                const score = attempt.total_score ? parseFloat(attempt.total_score) : 0;

                                tableData.push([
                                    studentName,
                                    attempt.lesson_title,
                                    attempt.attempts_count,
                                    score.toFixed(2)
                                ]);
                            });

                            const scoresByLesson = enrollment.lesson_attempts.map(attempt => parseFloat(attempt.total_score || 0));
                            const lessonNumbers = enrollment.lesson_attempts.map((_, index) => index + 1);

                            this.drawTrendGraph(doc, lessonNumbers, scoresByLesson, y);
                            y += 70;  // Ajuste para dar más espacio al gráfico y evitar solapamientos
                        });

                        doc.autoTable({
                            head: [['Estudiante', 'Lección', 'Intentos', 'Calificación Total']],
                            body: tableData,
                            startY: y,
                            styles: {
                                fontSize: 10,
                                textColor: [0, 0, 0],
                                cellPadding: 3,
                            },
                            headStyles: {
                                fillColor: [0, 122, 204],
                                textColor: [255, 255, 255],
                                fontStyle: 'bold'
                            },
                            alternateRowStyles: {
                                fillColor: [240, 240, 240]
                            },
                        });
                        y = doc.previousAutoTable.finalY + 20; // Ajuste de espacio después de la tabla

                    } else {
                        doc.setFontSize(12);
                        doc.text('No hay estudiantes inscritos', 20, y);
                        y += 8;
                    }

                    y += 10; // Espacio entre cursos
                });

                doc.save(`Reporte_de_Cursos_y_Estudiantes_${this.userName.toUpperCase()}.pdf`);
            } catch (error) {
                console.error('Error generating PDF:', error);
            }
        },
        drawTrendGraph(doc, lessonNumbers, scores, yPosition) {
            // Configuración del gráfico
            const startX = 30;
            const graphWidth = 160;
            const graphHeight = 50;
            const barWidth = graphWidth / lessonNumbers.length;

            doc.setLineWidth(0.2);
            doc.rect(startX, yPosition, graphWidth, graphHeight); // Dibujar el borde del gráfico

            const maxScore = 10;
            const yInterval = graphHeight / maxScore;

            doc.setDrawColor(200);
            for (let i = 0; i <= 10; i += 2) {
                const yGrid = yPosition + graphHeight - (i * yInterval);
                doc.line(startX, yGrid, startX + graphWidth, yGrid);
                doc.text(i.toString(), startX - 8, yGrid + 2);
            }

            doc.setDrawColor(0, 122, 204);
            doc.setLineWidth(1.5);
            for (let i = 0; i < lessonNumbers.length - 1; i++) {
                const x1 = startX + (i * barWidth);
                const y1 = yPosition + graphHeight - (scores[i] * yInterval);
                const x2 = startX + ((i + 1) * barWidth);
                const y2 = yPosition + graphHeight - (scores[i + 1] * yInterval);

                if (!isNaN(x1) && !isNaN(y1) && !isNaN(x2) && !isNaN(y2)) {
                    doc.line(x1, y1, x2, y2);
                }
            }

            doc.setFillColor(255, 0, 0);
            scores.forEach((score, index) => {
                const x = startX + (index * barWidth);
                const y = yPosition + graphHeight - (score * yInterval);

                if (!isNaN(x) && !isNaN(y)) {
                    doc.circle(x, y, 1.5, 'F');
                }
            });

            lessonNumbers.forEach((_, index) => {
                const x = startX + index * barWidth;
                doc.text(`L${lessonNumbers[index]}`, x, yPosition + graphHeight + 5, { align: 'center' });
            });
        },
        userCan(permission) {
            const hasPermission = this.userPermissions.includes(permission);
            console.log(`Permisos del usuario:`, this.userPermissions);
            console.log(`Validación de permiso (${permission}): ${hasPermission}`);
            return hasPermission;
        },
        getUserId() {
            axios.get('/api/user')
                .then(response => {
                    this.userId = response.data.id;
                    this.userName = response.data.name;
                    console.log('Usuario autenticado:', { id: this.userId, name: this.userName });
                    this.fetchCourses();
                    this.fetchEnrolledCourses();
                    this.fetchPermissions();
                })
                .catch(error => {
                    console.error('Error fetching user data:', error.response ? error.response : error);
                    this.$router.push('/login');
                });
        },
        goToCourse(courseId) {
            console.log(`Navegando al curso con ID: ${courseId}`);
            this.$router.push(`/courses/${courseId}`);
        },
        goToAdminPanel() {
            console.log('Navegando al Panel de Administración');
            this.$router.push('/admin');
        },
        createCourse() {
            console.log('Navegando a la creación de un nuevo curso');
            this.$router.push('/courses/create');
        },
        async deleteCourse(courseId) {
            console.log(`Intentando eliminar el curso con ID: ${courseId}`);
            const result = await this.alertEliminar(`¿Estás seguro de que deseas eliminar el curso?`);
            if(result.isConfirmed){
                console.log('Elimino');
                axios.delete(`/api/courses/${courseId}`)
                    .then(response => {
                        this.fetchCourses();
                        this.toast.success('El curso eliminado con éxito');
                    })
                    .catch(error => {
                        console.error('Error deleting course:', error.response ? error.response : error);
                        this.toast.error('Hubo un error al eliminar el curso', error.response);
                    });
            }
        },
        logout() {
            console.log('Cerrando sesión');
            axios.post('/logout')
                .then(() => {
                    localStorage.removeItem('authToken');
                    this.toast.info('Sesión cerrada, redirigiendo a la página de inicio de sesión');
                    this.$router.push('/login');
                })
                .catch(error => {
                    console.error('Error logging out:', error.response ? error.response : error);
                });
        },
        openChangePasswordDialog() {
            console.log('Abriendo diálogo para cambiar contraseña');
            this.changePasswordDialog = true;
        },
        closeChangePasswordDialog() {
            console.log('Cerrando diálogo de cambio de contraseña');
            this.changePasswordDialog = false;
            this.currentPassword = '';
            this.newPassword = '';
            this.confirmPassword = '';
        },
        submitChangePassword() {
            console.log('Intentando cambiar contraseña');
            axios.post('/api/change-password', {
                current_password: this.currentPassword,
                new_password: this.newPassword,
                new_password_confirmation: this.confirmPassword
            })
                .then(response => {
                    console.log('Contraseña cambiada exitosamente');
                    this.toast.success('Se ha actualizado la contraseña correctamente');
                    this.feedbackMessage = 'Contraseña cambiada con éxito.';
                    this.feedbackDialog = true;
                    this.closeChangePasswordDialog();
                })
                .catch(error => {
                    console.error('Error al cambiar la contraseña:', error.response ? error.response : error);
                    if (error.response && error.response.data.errors) {
                        this.feedbackMessage = error.response.data.errors.new_password ? error.response.data.errors.new_password[0] : 'Error desconocido';
                    } else {
                        this.feedbackMessage = 'Error al cambiar la contraseña.';
                    }
                    this.feedbackDialog = true;
                });
        },
        changeView(view) {
            console.log(`Cambiando vista a: ${view}`);
            this.bottomNav = view;
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
            const colorMap = {
                'Disponible': 'light-green lighten-1',
                'En curso': 'amber lighten-1',
                'Finalizado': 'light-blue lighten-1'
            };
            const color = colorMap[status] || 'grey lighten-1';
            console.log(`Estado del curso: ${status}, Color asignado: ${color}`);
            return color;
        },
        alertEliminar(mensaje) {
            return this.$swal({
                title: "¿Estás seguro?",
                text: mensaje,
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "Sí, deseo eliminar",
                cancelButtonText: "Cancelar",
                customClass: {
                    confirmButton: 'btn text-white',
                    cancelButton: 'btn text-white'
                },
                // buttonsStyling: false
            });
        }
    },
    created() {
        console.log('Componente creado, obteniendo datos del usuario');
        this.getUserId();
    }
};
</script>
