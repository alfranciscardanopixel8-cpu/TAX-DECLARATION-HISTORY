import { createRouter, createWebHistory } from 'vue-router';
import { fetchMe, getStoredToken } from '../services/api';

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/LoginPage.vue'),
    meta: { guest: true, title: 'Sign in' }
  },
  {
    path: '/workspace',
    component: () => import('../layouts/WorkspaceLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: { name: 'workspace-dashboard' }
      },
      {
        path: 'dashboard',
        name: 'workspace-dashboard',
        component: () => import('../pages/workspace/WorkspaceDashboardPage.vue'),
        meta: { title: 'Dashboard' }
      },
      {
        path: 'records',
        name: 'workspace-records',
        component: () => import('../pages/SearchPage.vue'),
        meta: { title: 'Property & TD Search' }
      },
      {
        path: 'activity',
        name: 'workspace-activity',
        component: () => import('../pages/workspace/WorkspaceActivityPage.vue'),
        meta: { title: 'Audit Logs' }
      },
      {
        path: 'staff',
        name: 'workspace-staff',
        component: () => import('../pages/workspace/WorkspaceStaffPage.vue'),
        meta: { title: 'Staff Accounts', requiresAdmin: true }
      },
      {
        path: 'security',
        name: 'workspace-security',
        component: () => import('../pages/workspace/WorkspaceSecurityPage.vue'),
        meta: { title: 'Security & Roles', requiresAdmin: true }
      }
    ]
  },
  {
    path: '/',
    redirect: '/workspace/dashboard'
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/workspace/dashboard'
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

async function resolveUser() {
  if (!getStoredToken()) {
    return null;
  }

  return fetchMe();
}

router.beforeEach(async (to) => {
  const token = getStoredToken();

  if (to.meta.requiresAuth && !token) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  if (to.meta.guest && token) {
    return { name: 'workspace-dashboard' };
  }

  if (!to.meta.requiresAuth) {
    return true;
  }

  const user = await resolveUser();

  if (!user) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  if (to.meta.requiresAdmin && !user.can_administer) {
    return { name: 'workspace-dashboard' };
  }

  return true;
});

export default router;
