import { createRouter, createWebHashHistory } from 'vue-router'
import AppLayout from '../layouts/AppLayout.vue'

const routes = [
  {
    name: 'swift-redirect-root',
    path: '/',
    component: AppLayout,
    redirect: { name: 'dashboard' },
    children: [
      {
        name: 'dashboard',
        path: 'dashboard',
        meta: { icon: 'ios-home' },
        component: () => import('../pages/admin/dashboard/Dashboard.vue'),
      },
      {
        name: 'logs',
        path: 'logs',
        meta: { icon: 'ios-list' },
        component: () => import('../pages/admin/dashboard/Logs.vue'),
      },
      {
        name: 'settings',
        path: 'settings',
        meta: { icon: 'ios-settings' },
        component: () => import('../pages/admin/dashboard/Settings.vue'),
      },
      {
        name: '404',
        path: '404',
        meta: { icon: 'ios-warning' },
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
