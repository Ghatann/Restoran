import { createRouter, createWebHistory } from '@ionic/vue-router';
import LoginPage from '@/views/LoginPage.vue';
import HomePage from '../views/HomePage.vue'
import MenuPage from '../views/MenuPage.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPage
  },
  {
    path: '/home',
    component: HomePage
  },
  {
    path: '/menu',
    name: 'Menu',
    component: MenuPage
  },
  {
    path: '/order',
    name: 'Order',
    component: () => import('../views/OrderPage.vue')
  },
  {
    path: '/meja',
    name: 'Meja',
    component: () => import('../views/MejaPage.vue')
  },
  {
    path: '/pembayaran',
    name: 'Pembayaran',
    component: () => import('../views/PembayaranPage.vue')
  },
  {
    path: '/chef',
    name: 'Chef',
    component: () => import('../views/ChefPage.vue')
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
