<template>
  <div class="auth-container">
    <div class="glass-card auth-card animate-fade-in">
      <div class="auth-header">
        <h2>Daftar Akun Baru</h2>
        <p>Bergabunglah dengan ribuan penawar dan penjual lelang daring</p>
      </div>

      <div v-if="error" class="error-alert">
        {{ error }}
      </div>

      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label class="form-label" for="name">Nama Lengkap</label>
          <input
            type="text"
            id="name"
            v-model="name"
            class="form-control"
            placeholder="John Doe"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Alamat Email</label>
          <input
            type="email"
            id="email"
            v-model="email"
            class="form-control"
            placeholder="nama@email.com"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Kata Sandi</label>
          <input
            type="password"
            id="password"
            v-model="password"
            class="form-control"
            placeholder="Min. 8 karakter"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi</label>
          <input
            type="password"
            id="password_confirmation"
            v-model="passwordConfirmation"
            class="form-control"
            placeholder="••••••••"
            required
            :disabled="loading"
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          {{ loading ? 'Sedang Mendaftar...' : 'Daftar Akun' }}
        </button>
      </form>

      <div class="auth-footer">
        <p>Sudah punya akun? <router-link to="/login" class="auth-link">Masuk</router-link></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useNotificationStore } from '../stores/notification';

const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const error = ref(null);

const handleRegister = async () => {
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Kata sandi dan konfirmasi kata sandi tidak cocok.';
    return;
  }

  loading.value = true;
  error.value = null;
  try {
    await authStore.register(
      name.value,
      email.value,
      password.value,
      passwordConfirmation.value
    );
    notificationStore.addToast('Pendaftaran berhasil! Selamat datang.', 'success');
    router.push({ name: 'Home' });
  } catch (err) {
    error.value = err.response?.data?.message || 'Registrasi gagal. Silakan periksa kembali formulir Anda.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 120px);
  padding: 24px;
}

.auth-card {
  width: 100%;
  max-width: 440px;
}

.auth-header {
  text-align: center;
  margin-bottom: 24px;
}

.auth-header h2 {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 8px;
  background: linear-gradient(135deg, #a5b4fc 0%, #6366f1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.auth-header p {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.btn-block {
  width: 100%;
  margin-top: 10px;
}

.auth-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.auth-link {
  color: var(--accent-primary);
  font-weight: 600;
}

.auth-link:hover {
  color: #818cf8;
  text-decoration: underline;
}

.error-alert {
  background-color: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #f87171;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 0.9rem;
}

.spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
