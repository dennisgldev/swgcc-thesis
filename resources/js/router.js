import { createRouter, createWebHistory } from 'vue-router';
import Home from './components/Home.vue';
import Login from './components/Login.vue';
import AdminDashboard from './components/admin/AdminDashboard.vue';
import UserManagement from './components/admin/UserManagement.vue';
import RoleManagement from './components/admin/RoleManagement.vue';
import UserCreate from './components/admin/UserCreate.vue';
import UserEdit from './components/admin/UserEdit.vue';
import CourseList from './components/CourseList.vue';
import CourseDetail from './components/CourseDetail.vue';
import CourseForm from './components/CourseForm.vue';
import LessonModal from './components/LessonModal.vue';

const routes = [
  { path: '/', component: Home },
  { path: '/login', component: Login },
  { path: '/courses', component: CourseList }, // Lista de cursos
  { path: '/courses/:id', component: CourseDetail, props: true }, // Detalle del curso
  { path: '/courses/create', component: CourseForm }, // Crear curso
  { path: '/courses/:id/edit', component: CourseForm, props: true }, // Editar curso
  {
    path: '/admin',
    component: AdminDashboard,
    children: [
      {
        path: 'users',
        component: UserManagement,
      },
      {
        path: 'users/create',
        component: UserCreate,
      },
      {
        path: 'users/:userId/edit',
        component: UserEdit,
      },
      {
        path: 'roles',
        component: RoleManagement,
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
