import { createApp } from 'vue'
import i18n from './i18n'
import { createVuestic, createIconsConfig } from 'vuestic-ui'
import 'vuestic-ui/css'
import { createGtm } from '@gtm-support/vue-gtm'

import router from './router'
import vuesticGlobalConfig from './services/vuestic-ui/global-config'
import App from './App.vue'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

const app = createApp(App)

app.use(router)
app.use(i18n)
app.use(createVuestic({ config: vuesticGlobalConfig }))
app.use(Toast, {
  transition: 'Vue-Toastification__slideBlurred',
  maxToasts: 5,
  newestOnTop: false,
})

if (import.meta.env.VITE_APP_GTM_ENABLED) {
  app.use(
    createGtm({
      id: import.meta.env.VITE_APP_GTM_KEY,
      debug: false,
      vueRouter: router,
    }),
  )
}

app.mount('#app-swift-redirect-app')
