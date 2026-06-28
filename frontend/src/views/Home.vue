<template>
  <div class="home-container">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <h1>Temukan Barang Impian Anda di Platform Lelang Real-time</h1>
        <p>Bidding interaktif, transparan, aman, dan instan dengan sistem pembaruan harga seketika.</p>
        <div class="hero-actions" v-if="!authStore.isAuthenticated">
          <router-link to="/register" class="btn btn-primary">Daftar Sekarang</router-link>
          <router-link to="/login" class="btn btn-secondary">Masuk Akun</router-link>
        </div>
        <div class="hero-actions" v-else>
          <router-link to="/seller/dashboard" class="btn btn-primary">Mulai Jual Barang</router-link>
        </div>
      </div>
      <div class="hero-glow"></div>
    </div>

    <!-- Filters and Search -->
    <div class="filter-bar glass">
      <div class="search-wrapper">
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Cari nama lelang..." 
          class="form-control search-input"
        />
      </div>
      <div class="tab-filters">
        <button 
          v-for="tab in tabs" 
          :key="tab.value"
          @click="activeTab = tab.value"
          class="filter-tab"
          :class="{ active: activeTab === tab.value }"
        >
          {{ tab.name }}
        </button>
      </div>
    </div>

    <!-- Auction List Grid -->
    <div v-if="loading" class="loading-state">
      <div class="spinner-lg"></div>
      <p>Memuat daftar lelang...</p>
    </div>

    <div v-else-if="filteredAuctions.length === 0" class="empty-state glass-card">
      <h3>Tidak ada lelang yang ditemukan</h3>
      <p>Silakan periksa kata kunci Anda atau coba filter status yang berbeda.</p>
    </div>

    <div v-else class="auction-grid grid-cols-3">
      <div 
        v-for="auction in filteredAuctions" 
        :key="auction.id" 
        class="glass-card auction-card"
      >
        <div class="card-image-wrapper">
          <img 
            :src="auction.image_url || 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=600&auto=format&fit=crop'" 
            alt="Auction Image" 
            class="auction-image"
          />
          <span class="status-badge badge" :class="`badge-${auction.status}`">
            {{ auction.status === 'active' ? 'Aktif' : 'Terjadwal' }}
          </span>
        </div>

        <div class="card-body">
          <h3 class="auction-title">{{ auction.title }}</h3>
          <p class="auction-desc">{{ truncate(auction.description, 80) }}</p>
          
          <div class="price-info">
            <div class="price-block">
              <span class="price-label">Harga Saat Ini</span>
              <span class="price-value">Rp {{ formatNumber(auction.current_price) }}</span>
            </div>
            <div class="increment-block">
              <span class="price-label">Kelipatan</span>
              <span class="increment-value">+Rp {{ formatNumber(auction.bid_increment) }}</span>
            </div>
          </div>

          <div class="countdown-wrapper">
            <AuctionCountdown 
              :starts-at="auction.starts_at" 
              :ends-at="auction.ends_at" 
              :status="auction.status"
              @countdown-ended="handleCountdownEnded(auction)"
            />
          </div>
          
          <router-link 
            :to="`/auction/${auction.id}`" 
            class="btn btn-primary btn-full"
          >
            {{ auction.status === 'active' ? 'Ikuti Bidding' : 'Lihat Detail' }}
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../utils/api';
import { useAuthStore } from '../stores/auth';
import AuctionCountdown from '../components/AuctionCountdown.vue';

const authStore = useAuthStore();

const auctions = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const activeTab = ref('all');

const tabs = [
  { name: 'Semua Lelang', value: 'all' },
  { name: 'Lelang Aktif', value: 'active' },
  { name: 'Segera Dimulai', value: 'scheduled' },
];

const fetchAuctions = async () => {
  loading.value = true;
  try {
    const response = await api.get('/auctions');
    auctions.value = response.data.auctions;
  } catch (error) {
    console.error('Error fetching auctions:', error);
  } finally {
    loading.value = false;
  }
};

const formatNumber = (num) => {
  return Number(num).toLocaleString('id-ID');
};

const truncate = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

// Filtered and searched auctions
const filteredAuctions = computed(() => {
  return auctions.value.filter((auction) => {
    // Search query match
    const matchesSearch = auction.title.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    // Status tab match
    if (activeTab.value === 'all') {
      return matchesSearch;
    }
    return matchesSearch && auction.status === activeTab.value;
  });
});

const handleCountdownEnded = (auction) => {
  // Refresh auction list to get updated statuses from backend scheduler
  fetchAuctions();
};

onMounted(() => {
  fetchAuctions();
});
</script>

<style scoped>
.home-container {
  padding: 40px 0;
}

/* Hero Section */
.hero-section {
  position: relative;
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
  border: 1px solid var(--glass-border);
  border-radius: 24px;
  padding: 60px 40px;
  margin-bottom: 40px;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 650px;
}

.hero-content h1 {
  font-size: 2.5rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 16px;
  background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 50%, #6366f1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-content p {
  color: var(--text-secondary);
  font-size: 1.1rem;
  margin-bottom: 24px;
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  gap: 16px;
}

.hero-glow {
  position: absolute;
  top: -50%;
  right: -20%;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%);
  z-index: 1;
  pointer-events: none;
}

/* Filters Bar */
.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-radius: 16px;
  margin-bottom: 30px;
  gap: 20px;
}

@media (max-width: 768px) {
  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }
}

.search-wrapper {
  flex-grow: 1;
  max-width: 400px;
}

.search-input {
  background-color: rgba(15, 23, 42, 0.4);
}

.tab-filters {
  display: flex;
  background-color: rgba(15, 23, 42, 0.4);
  padding: 4px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
}

.filter-tab {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-secondary);
  background: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-tab:hover {
  color: var(--text-primary);
}

.filter-tab.active {
  background-color: var(--accent-primary);
  color: white;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}

/* Grid and Cards */
.auction-grid {
  margin-bottom: 40px;
}

.auction-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 0;
  overflow: hidden;
}

.card-image-wrapper {
  position: relative;
  height: 200px;
  overflow: hidden;
  background-color: #1e293b;
}

.auction-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.auction-card:hover .auction-image {
  transform: scale(1.05);
}

.status-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  backdrop-filter: blur(8px);
}

.card-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.auction-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 8px;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.auction-desc {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-bottom: 16px;
  height: 40px;
  overflow: hidden;
  line-height: 1.4;
}

.price-info {
  display: flex;
  justify-content: space-between;
  background: rgba(15, 23, 42, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  padding: 10px 14px;
  margin-bottom: 16px;
}

.price-block, .increment-block {
  display: flex;
  flex-direction: column;
}

.price-label {
  font-size: 0.7rem;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-bottom: 2px;
  font-weight: 500;
}

.price-value {
  font-size: 1.05rem;
  font-weight: 700;
  color: #34d399;
}

.increment-value {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--accent-primary);
}

.countdown-wrapper {
  margin-bottom: 20px;
}

.btn-full {
  width: 100%;
  margin-top: auto;
}

/* States */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 0;
  gap: 16px;
}

.spinner-lg {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(99, 102, 241, 0.1);
  border-radius: 50%;
  border-top-color: var(--accent-primary);
  animation: spin 1s linear infinite;
}

.empty-state {
  text-align: center;
  padding: 60px 40px;
  color: var(--text-secondary);
}

.empty-state h3 {
  color: var(--text-primary);
  margin-bottom: 8px;
  font-size: 1.4rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
