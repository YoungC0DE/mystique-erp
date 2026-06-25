import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import { router } from './router'
import { i18n } from './i18n'
import { setUnauthorizedHandler } from './services/http'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(i18n)

// Quando o refresh falha, derruba a sessão e volta ao login.
setUnauthorizedHandler(() => {
  const auth = useAuthStore()
  auth.reset()
  router.push({ name: 'login' })
})

app.mount('#app')
