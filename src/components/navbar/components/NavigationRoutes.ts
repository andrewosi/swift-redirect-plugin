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
        icon: 'fa-solid fa-link',
      },
    },
    {
      name: 'logs',
      displayName: 'menu.logs',
      meta: {
        icon: 'fa-regular fa-clipboard',
      },
    },
    {
      name: '404',
      displayName: 'menu.404',
      meta: {
        icon: 'fa-solid fa-ban',
      },
    },
    {
      name: 'settings',
      displayName: 'menu.settings',
      meta: {
        icon: 'fa-solid fa-gears',
      },
    },
  ],
}
