import { createRouter, createWebHistory } from 'vue-router';
import Home from '../views/Home.vue';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import SellerDashboard from '../views/SellerDashboard.vue';
import AuctionDetail from '../views/AuctionDetail.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { guestOnly: true },
  },
  {
    path: '/seller/dashboard',
    name: 'SellerDashboard',
    component: SellerDashboard,
    meta: { requiresAuth: true },
  },
  {
    path: '/auction/:id',
    name: 'AuctionDetail',
    component: AuctionDetail,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const token = localStorage.getItem('token');
  
  if (token && !authStore.isAuthenticated) {
    authStore.setAuth(token, JSON.parse(localStorage.getItem('user')));
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' });
  } else if (to.meta.guestOnly && authStore.isAuthenticated) {
    next({ name: 'Home' });
  } else {
    next();
  }
});

export default router;
