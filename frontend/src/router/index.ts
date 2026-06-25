import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

import { useAuthStore } from '@/stores/auth';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/components/login/LoginLayout.vue'),
    meta: { guest: true },
    children: [
      {
        path: '',
        name: 'login',
        component: () => import('@/pages/LoginPage.vue'),
      },
    ],
  },
  {
    path: '/entrar',
    redirect: { name: 'login' },
  },
  {
    path: '/login',
    redirect: { name: 'login' },
  },
  {
    path: '/documentacao',
    redirect: { name: 'login' },
  },
  {
    path: '/registrar',
    redirect: { name: 'login' },
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/pages/DashboardPage.vue'),
      },
      {
        path: 'perfil',
        name: 'profile',
        component: () => import('@/pages/ProfilePage.vue'),
      },
      {
        path: 'configuracoes',
        name: 'settings',
        component: () => import('@/pages/SettingsPage.vue'),
        meta: { admin: true },
      },
      {
        path: 'users',
        name: 'users',
        component: () => import('@/pages/users/UsersPage.vue'),
        meta: { permission: 'read' },
      },
      {
        path: 'roles',
        name: 'roles',
        component: () => import('@/pages/roles/RolesPage.vue'),
        meta: { permission: 'read' },
      },
      {
        path: 'modules',
        name: 'modules',
        component: () => import('@/pages/modules/ModulesAdminPage.vue'),
        meta: { permission: 'read' },
      },
      {
        path: 'reports',
        name: 'reports',
        component: () => import('@/pages/reports/ReportsPage.vue'),
        meta: { permission: 'read' },
      },
      {
        path: 'm/:slug',
        name: 'module',
        component: () => import('@/pages/modules/ModuleBoardPage.vue'),
        props: true,
      },
      {
        path: 'm/:slug/config',
        name: 'module-config',
        component: () => import('@/pages/modules/ModuleConfigPage.vue'),
        props: true,
        meta: { permission: 'update' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'login' },
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.hydrate();

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  if (to.meta.admin && !auth.isAdmin) {
    return { name: 'dashboard' };
  }

  if (to.meta.permission && !auth.can(to.meta.permission as string)) {
    return { name: 'dashboard' };
  }

  return true;
});
