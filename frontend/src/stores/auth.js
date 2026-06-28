import { defineStore } from 'pinia';
import axios from 'axios';

// Set up base Axios configuration
axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
    loading: false,
    error: null,
  }),
  getters: {
    currentUser: (state) => state.user,
    authToken: (state) => state.token,
  },
  actions: {
    // Register
    async register(name, email, password, password_confirmation) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.post('/register', {
          name,
          email,
          password,
          password_confirmation,
        });
        
        const { access_token, user } = response.data;
        this.setAuth(access_token, user);
        return response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Registrasi gagal. Silakan coba lagi.';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    // Login
    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.post('/login', { email, password });
        const { access_token, user } = response.data;
        this.setAuth(access_token, user);
        return response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Login gagal. Email atau password salah.';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    // Logout
    async logout() {
      this.loading = true;
      try {
        if (this.token) {
          await axios.post('/logout', {}, {
            headers: { Authorization: `Bearer ${this.token}` }
          });
        }
      } catch (err) {
        console.error('Logout error:', err);
      } finally {
        this.clearAuth();
        this.loading = false;
      }
    },

    // Get current user details
    async fetchUser() {
      if (!this.token) return;
      try {
        const response = await axios.get('/user', {
          headers: { Authorization: `Bearer ${this.token}` }
        });
        this.user = response.data.user;
        localStorage.setItem('user', JSON.stringify(this.user));
      } catch (err) {
        console.error('Error fetching user:', err);
        if (err.response?.status === 401) {
          this.clearAuth();
        }
      }
    },

    setAuth(token, user) {
      this.token = token;
      this.user = user;
      this.isAuthenticated = true;
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
    },

    clearAuth() {
      this.token = null;
      this.user = null;
      this.isAuthenticated = false;
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  }
});
