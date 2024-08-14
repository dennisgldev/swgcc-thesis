<template>    <v-app>
        <!-- Header -->
        <v-app-bar app color="white" elevation="3">
            <v-toolbar-title class="headline">SWGCC</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn text @click="createCourse" v-if="isTeacher">Crear Curso</v-btn>
            <v-btn text @click="logout">Cerrar Sesión</v-btn>
        </v-app-bar>

        <!-- Loader -->
        <v-overlay :value="loading" opacity="0.8">
            <v-progress-circular indeterminate size="64">
            </v-progress-circular>
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
                        cols="12" sm="6" md="4"
                    >
                        <v-card
                            @click="goToCourse(course.id)"
                            class="course-card"
                            hover
                            elevation="2"
                            :color="getCourseColor(course.enrollment_status)"
                        >
                            <v-img
                                :src="course.cover_image ? `/uploads/${course.cover_image}` : '/images/default-cover.jpg'"
                                height="150px"
                            ></v-img>
                            <v-card-title>{{ course.title }}</v-card-title>
                            <v-card-subtitle>{{ course.instructor ? course.instructor.name : 'Instructor desconocido' }}</v-card-subtitle>
                            <v-card-text>
                                <span>Status: {{ course.enrollment_status }}</span>
                            </v-card-text>
                            <v-card-actions>
                                <v-btn text @click.stop="deleteCourse(course.id)" v-if="isTeacher">Eliminar</v-btn>
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
            <v-btn @click="changeView('myCourses')">
                <span>Mis Cursos</span>
            </v-btn>
            <v-btn @click="changeView('reports')">
                <span>Reportes</span>
            </v-btn>
        </v-bottom-navigation>

        <!-- Footer -->
        <v-footer app color="white" flat>
            <v-col class="text-center">
                &copy; 2024 SWGCC
            </v-col>
        </v-footer>
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
        getCourseColor(status) {
            console.log('Getting color for status:', status);
            switch (status) {
                case 'Disponible':
                    return 'light-green lighten-4';
                case 'En curso':
                    return 'amber lighten-4';
                case 'Finalizado':
                    return 'light-blue lighten-4';
                default:
                    return 'grey lighten-4';
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
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

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

v-toolbar-title {
  font-family: 'Roboto', sans-serif;
  font-weight: 700;
}

v-card-title {
  font-family: 'Roboto', sans-serif;
  font-weight: 700;
  font-size: 1.2rem;
}

v-app-bar {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
