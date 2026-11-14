import { createRouter, createWebHistory } from 'vue-router'
import Login from '../Login.vue'
import Dayoff from '../Dayoff.vue'
import Admin from '../Admin.vue'
import HomeView from '../views/HomeView.vue'

const routes = [
  {
    path: '/',
    name: 'welcome',
    component: HomeView
  },
  {
    path: '/login',
    name: 'login',
    component: Login
  },
  {
    path: '/dayoff',
    name: 'dayoff',
    component: Dayoff
  },
  {
    path: '/admin',
    name: 'admin',
    component: Admin
  },
  {
    path: '/home',
    name: 'home',
    component: HomeView
  },
  {
    path: '/about',
    name: 'about',
    // route level code-splitting
    // this generates a separate chunk (about.[hash].js) for this route
    // which is lazy-loaded when the route is visited.
    component: () => import(/* webpackChunkName: "about" */ '../views/AboutView.vue')
  }
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

router.beforeEach((to) => {
  let user = null
  try {
    user = JSON.parse(localStorage.getItem('non-user') || 'null')
  } catch {
    localStorage.removeItem('non-user')
  }

  if (['/dayoff', '/admin'].includes(to.path) && !user) {
    return { name: 'login' }
  }
  if (to.path === '/admin' && user?.email?.toLowerCase() !== 'suratan999@gmail.com') {
    return { name: 'dayoff' }
  }
})

export default router
