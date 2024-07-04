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
        icon: 'ios-home',
      },
    },
    {
      name: 'logs',
      displayName: 'menu.logs',
      meta: {
        icon: 'ios-list',
      },
    },
    {
      name: '404',
      displayName: 'menu.404',
      meta: {
        icon: 'ios-warning',
      },
    },
    {
      name: 'settings',
      displayName: 'menu.settings',
      meta: {
        icon: 'ios-settings',
      },
    },
  ],
}
