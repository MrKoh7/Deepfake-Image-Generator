import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import Generated from '../views/Generated.vue'
import Training from '../views/Training.vue'

const routes = [
  { path: '/login', name: 'Login', component: Login, meta: { guest: true } },
  { path: '/register', name: 'Register', component: Register, meta: { guest: true } },
  { path: '/', name: 'Generated', component: Generated, meta: { requiresAuth: true } },
  { path: '/training', name: 'Training', component: Training, meta: { requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const user = JSON.parse(localStorage.getItem('user'))

  if (to.meta.requiresAuth && !user) {
    return next('/login')  // force login
  }

  if (to.meta.guest && user) {
    return next('/') // logged-in users can’t go back to login/register
  }

  next()
})

export default router
