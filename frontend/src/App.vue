<template>
  <div class="app-layout">
    <!-- Navbar -->
    <nav class="navbar glass">
      <div class="container nav-content">
        <router-link to="/" class="logo">
          <span class="logo-icon">⚡</span>
          <span class="logo-text">Lelang<span class="logo-bold">APP</span></span>
        </router-link>

        <div class="nav-links">
          <router-link to="/" class="nav-item" active-class="active-nav">Beranda</router-link>
          <router-link v-if="authStore.isAuthenticated" to="/seller/dashboard" class="nav-item" active-class="active-nav">Dashboard Penjual</router-link>
        </div>

        <div class="auth-section">
          <template v-if="authStore.isAuthenticated">
            <div class="user-profile">
              <span class="welcome-text">Halo, <strong>{{ authStore.user?.name }}</strong></span>
              <button @click="handleLogout" class="btn btn-secondary btn-sm">Keluar</button>
            </div>
          </template>
          <template v-else>
            <div class="auth-buttons">
              <router-link to="/login" class="nav-item">Masuk</router-link>
              <router-link to="/register" class="btn btn-primary btn-sm">Daftar</router-link>
            </div>
          </template>
        </div>
      </div>
    </nav>

    <!-- Main Content Area -->
    <main class="main-content container">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="footer">
      <div class="container footer-content">
        <p>&copy; 2026 LelangAPP. Hak Cipta Dilindungi.</p>
        <p>UAS MATA KULIAH PEMROGRAMAN BERBASIS WEB</p>
      </div>
    </footer>

    <!-- Global Toasts -->
    <div class="toast-container">
      <div 
        v-for="toast in notificationStore.toasts" 
        :key="toast.id" 
        class="toast"
        :class="`toast-${toast.type}`"
      >
        <span class="toast-icon">
          <span v-if="toast.type === 'success'">✔️</span>
          <span v-else-if="toast.type === 'error'">⚠️</span>
          <span v-else>ℹ️</span>
        </span>
        <div class="toast-body">
          <p>{{ toast.message }}</p>
        </div>
        <button @click="notificationStore.removeToast(toast.id)" class="toast-close">&times;</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useNotificationStore } from './stores/notification';
import echo from './utils/echo';

const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

let privateChannel = null;

const handleLogout = async () => {
  try {
    await authStore.logout();
    notificationStore.addToast('Anda berhasil keluar.', 'info');
    router.push({ name: 'Home' });
  } catch (error) {
    console.error('Logout error:', error);
  }
};

const subscribePrivateChannel = () => {
  if (authStore.isAuthenticated && authStore.user) {
    const channelName = `App.Models.User.${authStore.user.id}`;
    console.log(`Subscribing to user private channel: ${channelName}`);
    
    // Subscribe to outbid notifications
    privateChannel = echo.private(channelName)
      .listen('OutbidNotification', (e) => {
        console.log('Outbid notification received:', e);
        // Show outbid notification toast
        notificationStore.addToast(e.message, 'error', 8000);
      });
  }
};

const unsubscribePrivateChannel = () => {
  if (privateChannel && authStore.user) {
    echo.leave(`App.Models.User.${authStore.user.id}`);
    privateChannel = null;
  }
};

// Re-evaluate authorization channels on status change
watch(() => authStore.isAuthenticated, (newVal) => {
  if (newVal) {
    subscribePrivateChannel();
  } else {
    unsubscribePrivateChannel();
  }
});

onMounted(() => {
  subscribePrivateChannel();
});

onUnmounted(() => {
  unsubscribePrivateChannel();
});
</script>

<style scoped>
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  border-bottom: 1px solid var(--glass-border);
  height: 70px;
}

.nav-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 100%;
}

.logo {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--text-primary);
}

.logo-icon {
  font-size: 1.5rem;
}

.logo-bold {
  color: var(--accent-primary);
}

.nav-links {
  display: flex;
  gap: 24px;
}

.nav-item {
  color: var(--text-secondary);
  font-weight: 600;
  font-size: 0.95rem;
  padding: 6px 12px;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.nav-item:hover {
  color: var(--text-primary);
  background-color: rgba(255, 255, 255, 0.05);
}

.active-nav {
  color: var(--text-primary);
  background-color: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.2);
}

.auth-section {
  display: flex;
  align-items: center;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 16px;
}

.welcome-text {
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.auth-buttons {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-sm {
  padding: 6px 14px;
  font-size: 0.85rem;
  border-radius: 8px;
}

.main-content {
  flex-grow: 1;
}

.footer {
  background-color: rgba(15, 23, 42, 0.6);
  border-top: 1px solid var(--border-color);
  padding: 20px 0;
  margin-top: 60px;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
  color: var(--text-muted);
}

@media (max-width: 640px) {
  .footer-content {
    flex-direction: column;
    gap: 8px;
    text-align: center;
  }
}

/* Toast Close Button */
.toast-close {
  background: none;
  border: none;
  color: inherit;
  font-size: 1.2rem;
  cursor: pointer;
  opacity: 0.5;
  transition: opacity 0.2s;
  align-self: center;
  padding-left: 12px;
}

.toast-close:hover {
  opacity: 1;
}
</style>
