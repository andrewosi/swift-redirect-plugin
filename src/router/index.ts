import { createRouter, createWebHashHistory } from 'vue-router'

import AppLayout from '../layouts/AppLayout.vue'

const routes = [
  {
    name: 'dashboard',
    path: '/',
    component: AppLayout,
    redirect: 'dashboard',
    children: [
      {
        name: 'dashboard',
        path: 'dashboard',
        component: () => import('../pages/admin/dashboard/Dashboard.vue'),
      },
      {
        name: 'logs',
        path: 'logs',
        component: () => import('../pages/admin/dashboard/Logs.vue'),
      },
      {
        name: 'settings',
        path: 'settings',
        component: () => import('../pages/admin/dashboard/Settings.vue'),
      },
      {
        name: '404',
        path: '404',
        component: () => import('../pages/admin/dashboard/404.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

export default router
