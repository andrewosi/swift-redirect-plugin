export const INavigationRoute = {
  name: '',
  displayName: '',
  meta: { icon: '' },
  children: [],
}

export default {
  root: {
    name: '/',
    displayName: 'navigationRoutes.home',
  },
  routes: [
    {
      name: 'dashboard',
      displayName: 'menu.dashboard',
      meta: {
        icon: 'home',
      },
    },
    {
      name: 'logs',
      displayName: 'menu.logs',
      meta: {
        icon: 'list',
      },
    },
    {
      name: '404',
      displayName: 'menu.404',
      meta: {
        icon: 'warning',
      },
    },
    {
      name: 'settings',
      displayName: 'menu.settings',
      meta: {
        icon: 'settings',
      },
    },
  ],
}
